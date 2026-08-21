<?php

namespace App\Services\Sms;

/**
 * Normalises Sri Lankan mobile numbers to international format (94XXXXXXXXX).
 *
 * Accepted inputs (spaces, dashes, dots and parentheses are ignored):
 *   0771234567, +94771234567, 94771234567, 0094771234567, 771234567, 077-123 4567
 *
 * Only mobile prefixes are accepted — landlines are rejected.
 */
class SriLankanPhone
{
    /** Valid digits after the leading 7 of the subscriber number (7X). */
    private const MOBILE_PREFIXES = ['70', '71', '72', '74', '75', '76', '77', '78'];

    /**
     * Normalise to 94XXXXXXXXX, or null when the input is not a valid
     * Sri Lankan mobile number.
     */
    public static function tryNormalize(?string $input): ?string
    {
        if ($input === null) {
            return null;
        }

        // Keep digits only ("+94 77-123.4567" → "94771234567")
        $digits = preg_replace('/\D+/', '', $input);

        if ($digits === '') {
            return null;
        }

        // Strip the international / trunk prefix down to the 9-digit subscriber number
        if (str_starts_with($digits, '0094')) {
            $subscriber = substr($digits, 4);
        } elseif (str_starts_with($digits, '94') && strlen($digits) === 11) {
            $subscriber = substr($digits, 2);
        } elseif (str_starts_with($digits, '0') && strlen($digits) === 10) {
            $subscriber = substr($digits, 1);
        } else {
            $subscriber = $digits;
        }

        if (strlen($subscriber) !== 9) {
            return null;
        }

        if (!in_array(substr($subscriber, 0, 2), self::MOBILE_PREFIXES, true)) {
            return null;
        }

        return '94' . $subscriber;
    }

    /**
     * Canonical format used for storing/matching CUSTOMER phone numbers: the
     * local leading-zero mobile format (0771234567), derived from the same
     * normalisation as tryNormalize() (94XXXXXXXXX → 0XXXXXXXXX). This is the
     * format the customers table already uses for hand-entered numbers, so a
     * number saved from POS and one linked from an SMS bill resolve to ONE row.
     *
     * Returns null when the input is not a valid Sri Lankan mobile number.
     */
    public static function tryNormalizeLocal(?string $input): ?string
    {
        $normalized = self::tryNormalize($input);

        return $normalized === null ? null : '0' . substr($normalized, 2);
    }

    /**
     * Normalise or throw. Use tryNormalize() when a null result is acceptable.
     */
    public static function normalize(string $input): string
    {
        $normalized = self::tryNormalize($input);

        if ($normalized === null) {
            throw new \InvalidArgumentException("Not a valid Sri Lankan mobile number: {$input}");
        }

        return $normalized;
    }

    public static function isValid(?string $input): bool
    {
        return self::tryNormalize($input) !== null;
    }

    /**
     * Mask a normalised number for display: 94771234567 → 9477•••4567
     */
    public static function mask(string $normalized): string
    {
        if (strlen($normalized) < 8) {
            return str_repeat('•', strlen($normalized));
        }

        return substr($normalized, 0, 4) . '•••' . substr($normalized, -4);
    }
}
