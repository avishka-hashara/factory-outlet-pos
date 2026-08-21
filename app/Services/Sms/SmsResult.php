<?php

namespace App\Services\Sms;

/**
 * Typed result of an SMS gateway send attempt.
 */
final class SmsResult
{
    private function __construct(
        public readonly bool $success,
        public readonly ?string $providerMessageId,
        public readonly ?array $providerResponse,
        public readonly ?string $error,
        public readonly bool $retryable,
        /** The outgoing request as sent, with the API key redacted. */
        public readonly ?array $request = null,
    ) {
    }

    public static function ok(?string $providerMessageId, ?array $providerResponse, ?array $request = null): self
    {
        return new self(true, $providerMessageId, $providerResponse, null, false, $request);
    }

    /**
     * @param bool $retryable false for permanent failures (invalid number,
     *                        insufficient credit, bad credentials) that a
     *                        retry can never fix.
     */
    public static function failed(string $error, ?array $providerResponse = null, bool $retryable = true, ?array $request = null): self
    {
        return new self(false, null, $providerResponse, $error, $retryable, $request);
    }
}
