<?php

namespace App\Console\Commands;

use App\Services\Sms\BillMessageBuilder;
use App\Services\Sms\SmsGateway;
use App\Services\Sms\SriLankanPhone;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * End-to-end SMS pipeline probe.
 *
 *   php artisan sms:test 0771234567
 *
 * Runs a real bill message through normalisation, the GSM-7 builder and the
 * configured driver, printing the normalised number, the final body, its
 * length + segment count, the exact outgoing request (API key redacted), the
 * raw provider response, and a pass/fail verdict.
 *
 * Under SMS_DRIVER=log this spends no credit (dry-run). Under SMS_DRIVER=dialog
 * it performs a real send — use a number you control.
 */
class SmsTest extends Command
{
    protected $signature = 'sms:test {mobile : Sri Lankan mobile number in any format}';

    protected $description = 'Send a test bill SMS through the configured driver (redacts the API key)';

    public function handle(SmsGateway $gateway): int
    {
        $driver = $gateway->driver();

        $this->line('');
        $this->info('SMS pipeline test');
        $this->line('  driver: ' . $driver . ($driver === 'log' ? '  (dry-run — no credit spent)' : '  (LIVE — real send)'));
        $this->line('');

        // ── 1. Normalise the number ──────────────────────────────────────
        $raw = (string) $this->argument('mobile');
        $to = SriLankanPhone::tryNormalize($raw);

        if ($to === null) {
            $this->error("Invalid Sri Lankan mobile number: {$raw}");
            $this->line('Accepted mobile prefixes: 70, 71, 72, 74, 75, 76, 77, 78');

            return self::FAILURE;
        }

        $this->line('<comment>Normalised number:</comment> ' . $to . '  (masked: ' . SriLankanPhone::mask($to) . ')');

        // ── 1b. Credential check via balance — authenticates with only the
        //        key; sends nothing, spends nothing.
        $balance = $gateway->checkBalance();
        if ($driver !== 'log') {
            if ($balance['success']) {
                $this->line('<comment>Key check (balance):</comment> OK — account balance Rs ' . ($balance['balance'] ?? '?'));
            } else {
                $this->error('Key check FAILED — balance endpoint returned: ' . $balance['raw']);
                $this->line($balance['code'] === '2007'
                    ? 'Code 2007 = the URL Message Key was rejected. Regenerate it in the eSMS portal and update SMS_GATEWAY_KEY.'
                    : 'The send below will most likely fail for the same reason.');
            }
        }

        // ── 2. Build a real bill message ─────────────────────────────────
        // Representative discounted sale so the Sub/Disc lines are exercised.
        // Shop name defaults to APP_NAME; URL host derives from APP_URL.
        $billUrl = url('/b/' . Str::random(12));

        $message = BillMessageBuilder::build(
            invoiceNo: 'TEST-' . now()->format('md-His'),
            discount: 2500.00,
            total: 12500.00,
            billUrl: $billUrl,
        );

        $len = strlen($message);
        $segments = BillMessageBuilder::segmentsFor($len);
        $maxSegments = BillMessageBuilder::maxSegments();
        $maxChars = BillMessageBuilder::maxChars($maxSegments);
        $overBudget = $segments > $maxSegments;

        $this->line('');
        $this->line('<comment>Message body:</comment>');
        $this->line('┌' . str_repeat('─', 40));
        foreach (explode("\n", $message) as $line) {
            $this->line('│ ' . $line);
        }
        $this->line('└' . str_repeat('─', 40));
        $this->line(sprintf(
            '<comment>Characters:</comment> %d / %d    <comment>Segments:</comment> %d / %d (ceiling)%s',
            $len,
            $maxChars,
            $segments,
            $maxSegments,
            $overBudget ? '  <fg=red>(OVER BUDGET — trimmed)</>' : ''
        ));

        // ── 3. Show the exact outgoing request (key redacted) ────────────
        $preview = $gateway->previewRequest($gateway->listFormatLocal($to), $message);

        $this->line('');
        $this->line('<comment>Outgoing request (URL Message Key redacted):</comment>');
        $this->line(json_encode($preview, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        // ── 4. Send through the configured driver ────────────────────────
        $this->line('');
        $this->line('Sending…');
        $result = $gateway->send($to, $message);

        // ── 5. Raw provider response ─────────────────────────────────────
        $this->line('');
        $this->line('<comment>Raw provider response:</comment>');
        $this->line(json_encode($result->providerResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        // ── 6. Verdict ───────────────────────────────────────────────────
        $this->line('');
        if ($result->success) {
            $this->info('PASS — ' . ($driver === 'log'
                ? 'message logged (dry-run). Set SMS_DRIVER=dialog for a real send.'
                : 'gateway accepted the send. Provider message id: ' . ($result->providerMessageId ?? 'n/a')));

            return self::SUCCESS;
        }

        $this->error('FAIL — ' . $result->error);
        $this->line($result->retryable ? '(retryable failure)' : '(permanent failure — retrying will not help)');

        return self::FAILURE;
    }
}
