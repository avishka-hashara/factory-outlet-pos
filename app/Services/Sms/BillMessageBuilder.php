<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Builds the SMS bill body.
 *
 * LAYOUT (specified by the shop owner — match exactly, blank lines included)
 *
 *     Thank you for your purchase at {shop_name}.
 *
 *     Bill: #{invoice_no}
 *     Total Paid: Rs. {total}
 *     You saved: Rs. {discount}          <- only when discount > 0
 *
 *     Download your invoice:
 *     {bill_url}
 *
 *     Wishing you many memorable moments with your new jewelry.
 *
 * The blank lines are real characters (\n\n) and count against the budget.
 * The Bill/Total/saved lines form ONE block, so omitting the discount leaves
 * no orphaned blank line behind.
 *
 * The message may span up to SMS_MAX_SEGMENTS GSM-7 segments (default 2). It
 * is only trimmed if it would exceed that ceiling — never at a hardcoded 160.
 *
 * GSM-7 SEGMENT BUDGET
 *   A single-segment SMS holds 160 septets. Once a message spans multiple
 *   segments, each carries a 7-septet concatenation (UDH) header, leaving
 *   153 usable septets per segment. So the character budget is:
 *       1 segment  = 160
 *       2 segments = 306   (153 * 2)
 *       3 segments = 459   (153 * 3)
 *   ...and the number of segments a message of length L needs is:
 *       L <= 160 : 1
 *       L  > 160 : ceil(L / 153)
 *
 * NON-GSM-7 PROTECTION (do not weaken)
 *   Every text input is transliterated to ASCII and stripped to the
 *   single-septet GSM 03.38 set (see toGsm7()). A single char outside that
 *   set — an emoji, a Sinhala/Tamil letter, or even an ASCII char from the
 *   GSM *extended* table ({ } [ ] \ ^ | ~) — would flip the whole SMS to
 *   UCS-2 (70/67 per segment) or cost 2 septets, silently breaking every
 *   budget figure above. After toGsm7(), septets == strlen($bytes).
 */
class BillMessageBuilder
{
    /** Usable septets in a single-segment message. */
    public const SINGLE_SEGMENT_CHARS = 160;

    /** Usable septets per segment once a message is concatenated (UDH costs 7). */
    public const MULTI_SEGMENT_CHARS = 153;

    /** Single-septet GSM 03.38 characters that are also ASCII. */
    private const GSM7_SAFE = '/[^A-Za-z0-9 \r\n@$_!"#%&\'()*+,\-.\/:;<=>?]/';

    public static function build(
        string $invoiceNo,
        float $discount,
        float $total,
        string $billUrl,
        ?string $shopName = null,
    ): string {
        $shop = self::inlineText($shopName ?? (string) config('app.name', 'Jewellery POS'));
        $invoice = self::inlineText($invoiceNo);
        $url = self::inlineText($billUrl);
        $hasDiscount = $discount > 0;

        $money = fn (float $amount): string => 'Rs. ' . number_format($amount, 2);

        $compose = function (bool $withGreeting, bool $withClosing) use (
            $shop, $invoice, $discount, $total, $url, $hasDiscount, $money
        ): string {
            $blocks = [];

            if ($withGreeting) {
                $blocks[] = $shop !== ''
                    ? "Thank you for your purchase at {$shop}."
                    : 'Thank you for your purchase.';
            }

            // One block: no blank line between these, so a missing discount
            // line leaves no gap behind.
            $detail = [
                "Bill: #{$invoice}",
                'Total Paid: ' . $money($total),
            ];
            if ($hasDiscount) {
                $detail[] = 'You saved: ' . $money($discount);
            }
            $blocks[] = implode("\n", $detail);

            $blocks[] = "Download your invoice:\n{$url}";

            if ($withClosing) {
                $blocks[] = 'Wishing you many memorable moments with your new jewelry.';
            }

            // A blank line separates each block.
            return implode("\n\n", $blocks);
        };

        $maxSegments = self::maxSegments();
        $maxChars = self::maxChars($maxSegments);

        $message = $compose(true, true);

        if (strlen($message) <= $maxChars) {
            return $message;
        }

        // Over budget — degrade gracefully, dropping the least-valuable blocks
        // first (never the bill no., total, saving or URL): the closing
        // sentiment, then the greeting.
        $overLength = strlen($message);

        $message = $compose(true, false);
        if (strlen($message) > $maxChars) {
            $message = $compose(false, false);
        }
        // Last resort: hard cap so we can never emit more than the ceiling.
        if (strlen($message) > $maxChars) {
            $message = substr($message, 0, $maxChars);
        }

        Log::warning('SMS bill body exceeded the segment budget; trimmed to fit', [
            'invoice_no' => $invoiceNo,
            'original_chars' => $overLength,
            'original_segments' => self::segmentsFor($overLength),
            'max_segments' => $maxSegments,
            'max_chars' => $maxChars,
            'final_chars' => strlen($message),
            'final_segments' => self::segmentsFor(strlen($message)),
        ]);

        assert(strlen($message) <= $maxChars);

        return $message;
    }

    /**
     * Number of GSM-7 segments a message of the given septet length needs.
     */
    public static function segmentsFor(int $length): int
    {
        if ($length <= 0) {
            return 0;
        }
        if ($length <= self::SINGLE_SEGMENT_CHARS) {
            return 1;
        }

        return (int) ceil($length / self::MULTI_SEGMENT_CHARS);
    }

    /**
     * Character budget for a given number of segments.
     * 1 → 160; N>1 → N * 153.
     */
    public static function maxChars(int $maxSegments): int
    {
        $maxSegments = max(1, $maxSegments);

        return $maxSegments === 1
            ? self::SINGLE_SEGMENT_CHARS
            : $maxSegments * self::MULTI_SEGMENT_CHARS;
    }

    /**
     * Configured ceiling (SMS_MAX_SEGMENTS), never below 1.
     */
    public static function maxSegments(): int
    {
        return max(1, (int) config('services.sms.max_segments', 2));
    }

    /**
     * GSM-7 clean a value that must occupy a single line.
     *
     * GSM-7 legitimately permits \r\n, so a stray newline in APP_NAME or an
     * invoice number would survive toGsm7() and silently corrupt the owner's
     * exact block layout. Fold any newline into a space instead.
     */
    private static function inlineText(string $text): string
    {
        $clean = preg_replace('/[\r\n]+/', ' ', self::toGsm7($text)) ?? '';

        return trim(preg_replace('/ {2,}/', ' ', $clean) ?? '');
    }

    /**
     * Transliterate to ASCII, then drop anything outside the single-septet
     * GSM-7 set.
     */
    public static function toGsm7(string $text): string
    {
        $ascii = Str::ascii($text);
        $clean = preg_replace(self::GSM7_SAFE, '', $ascii) ?? '';

        // Collapse whitespace runs left behind by stripped characters
        return preg_replace('/ {2,}/', ' ', $clean) ?? '';
    }
}
