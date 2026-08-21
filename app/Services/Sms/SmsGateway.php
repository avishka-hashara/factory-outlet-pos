<?php

namespace App\Services\Sms;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Wraps the Dialog eSMS "Message via URL" API — the flow the portal's
 * "URL Message Key" credential actually belongs to.
 *
 * ── VERIFIED CONTRACT (probed live against account PV00245309, 2026-07-16;
 *    cross-checked against the MaleeshaUdan/dialog-esms and WordPress
 *    dialog-e-sms wrappers) ─────────────────────────────────────────────
 *
 *   Send:    GET https://e-sms.dialog.lk/api/v1/message-via-url/create/url-campaign
 *              ?esmsqk={URL_MESSAGE_KEY}&list={number}&source_address={mask}&message={text}
 *   Balance: GET https://e-sms.dialog.lk/api/v1/message-via-url/check/balance
 *              ?esmsqk={URL_MESSAGE_KEY}
 *
 *   Responses are HTTP 200 with a PLAIN-TEXT body, not JSON:
 *     "1"            → success               (balance: "1|3437.99")
 *     "2005"         → invalid recipient(s)  (observed live: list=123 → 2005;
 *                       returned before anything is sent or charged)
 *     "2007"         → invalid/unauthorised key (observed live: garbage key → 2007|0)
 *     other "20xx"   → other failures (codes 2001–2011 exist; meanings unverified)
 *
 *   NOTE: the "URL Message Key" is NOT a v2 API token. POSTing it as a
 *   Bearer to /api/v2/sms returns {"comment":"Invalid token signature
 *   detected","errCode":105} — that endpoint expects a different credential.
 *
 * The key travels in the query string, so every log/preview/error path here
 * scrubs it (see scrub()/redactedQuery()). All credentials come from
 * config/services.php → env; server-side only.
 *
 * SMS_DRIVER=log switches to a dry-run driver that writes the message to
 * the application log instead of hitting Dialog (no credit spent).
 */
class SmsGateway
{
    /** Success status code in the plain-text response. */
    private const CODE_SUCCESS = '1';

    /** Invalid recipient number(s) — rejected before any send/charge. */
    private const CODE_INVALID_NUMBER = '2005';

    /** Invalid / unauthorised URL Message Key. */
    private const CODE_INVALID_KEY = '2007';

    private string $driver;
    private string $url;
    private string $balanceUrl;
    private ?string $key;
    private ?string $mask;
    private int $timeout;

    public function __construct()
    {
        $config = config('services.sms', []);

        $this->driver = $config['driver'] ?? 'log';
        $this->url = $config['url'] ?? '';
        $this->balanceUrl = $config['balance_url'] ?? '';
        $this->key = $config['key'] ?? null;
        $this->mask = $config['mask'] ?? null;
        $this->timeout = max(1, (int) ($config['timeout'] ?? 10));
    }

    public function driver(): string
    {
        return $this->driver;
    }

    /**
     * Send one SMS to a normalised number (94XXXXXXXXX).
     */
    public function send(string $toNumber, string $message): SmsResult
    {
        if ($this->driver === 'log') {
            Log::info('[SMS dry-run] message logged instead of sent (SMS_DRIVER=log)', [
                'to' => $toNumber,
                'mask' => $this->mask,
                'length' => strlen($message),
                'message' => $message,
            ]);

            return SmsResult::ok('log-' . uniqid(), ['driver' => 'log', 'dry_run' => true], [
                'driver' => 'log',
                'query' => $this->redactedQuery($this->listFormatLocal($toNumber), $message),
            ]);
        }

        if (empty($this->key)) {
            return SmsResult::failed('SMS_GATEWAY_KEY is not configured.', null, retryable: false);
        }
        if (empty($this->url)) {
            return SmsResult::failed('SMS_GATEWAY_URL is not configured.', null, retryable: false);
        }
        if (empty($this->mask)) {
            return SmsResult::failed('SMS_SENDER_MASK is not configured.', null, retryable: false);
        }

        // Recipient format: the API's own web flow and the published WordPress
        // wrapper use the local 10-digit form (0XXXXXXXXX), so try that first.
        // Code 2005 is PROVEN (live probe) to mean "rejected before send", so
        // if the API wants 94-format instead, one alternate-format retry is
        // safe — it can never double-send.
        $result = $this->attemptSend($this->listFormatLocal($toNumber), $message);

        if (!$result->success && str_starts_with((string) $result->error, '[2005]')) {
            Log::info('SMS list rejected in local format (2005); retrying in 94-format', [
                'to' => $toNumber,
            ]);

            $international = $this->attemptSend($toNumber, $message);

            // If 94-format also fails, report the clearer invalid-number error.
            return $international->success ? $international : $result;
        }

        return $result;
    }

    /**
     * One GET to the url-campaign endpoint with a specific `list` value.
     */
    private function attemptSend(string $list, string $message): SmsResult
    {
        $requestPreview = $this->previewRequest($list, $message);

        // Log every send attempt (key redacted).
        Log::info('SMS gateway request', ['request' => $requestPreview]);

        try {
            $response = Http::timeout($this->timeout)
                ->connectTimeout($this->timeout)
                ->get($this->url, [
                    'esmsqk' => $this->key,
                    'list' => $list,
                    'source_address' => $this->mask,
                    'message' => $message,
                ]);
        } catch (ConnectionException $e) {
            // Exception messages can embed the full URL (incl. the key) — scrub.
            return SmsResult::failed(
                'SMS gateway unreachable: ' . $this->scrub($e->getMessage()),
                null,
                retryable: true,
                request: $requestPreview
            );
        }

        $body = trim($this->scrub($response->body()));
        [$code, $extra] = array_pad(explode('|', $body, 2), 2, null);
        $code = trim((string) $code);

        $providerResponse = [
            'raw' => mb_substr($body, 0, 500),
            'code' => $code,
            'http_status' => $response->status(),
        ];

        Log::info('SMS gateway response', ['provider_response' => $providerResponse]);

        if (!$response->successful() || $body === '') {
            return SmsResult::failed(
                sprintf('Gateway HTTP %d: %s', $response->status(), mb_substr($body, 0, 200)),
                $providerResponse,
                retryable: true,
                request: $requestPreview
            );
        }

        if ($code === self::CODE_SUCCESS) {
            return SmsResult::ok($extra !== null ? trim($extra) : null, $providerResponse, $requestPreview);
        }

        [$error, $retryable] = match ($code) {
            self::CODE_INVALID_NUMBER => ["[2005] Recipient number rejected by the gateway.", false],
            self::CODE_INVALID_KEY => ["[2007] URL Message Key rejected — regenerate it in the eSMS portal and update SMS_GATEWAY_KEY.", false],
            // 2001–2011 exist but are undocumented; keep them retryable so a
            // transient condition isn't made permanent on a guess.
            default => ["[{$code}] Gateway returned failure code {$code}.", true],
        };

        return SmsResult::failed($error, $providerResponse, $retryable, $requestPreview);
    }

    /**
     * Balance check — authenticates with only the key. Costs nothing and
     * sends nothing, so it doubles as a credential validity probe.
     *
     * @return array{success: bool, code: string, balance: ?string, raw: string}
     */
    public function checkBalance(): array
    {
        if ($this->driver === 'log') {
            return ['success' => true, 'code' => 'log', 'balance' => null, 'raw' => 'dry-run'];
        }

        if (empty($this->key) || empty($this->balanceUrl)) {
            return ['success' => false, 'code' => 'config', 'balance' => null, 'raw' => 'key or balance_url not configured'];
        }

        try {
            $response = Http::timeout($this->timeout)
                ->connectTimeout($this->timeout)
                ->get($this->balanceUrl, ['esmsqk' => $this->key]);
        } catch (ConnectionException $e) {
            return ['success' => false, 'code' => 'network', 'balance' => null, 'raw' => $this->scrub($e->getMessage())];
        }

        $body = trim($this->scrub($response->body()));
        [$code, $balance] = array_pad(explode('|', $body, 2), 2, null);

        return [
            'success' => trim((string) $code) === self::CODE_SUCCESS,
            'code' => trim((string) $code),
            'balance' => $balance !== null ? trim($balance) : null,
            'raw' => mb_substr($body, 0, 200),
        ];
    }

    /**
     * A redacted preview of the outgoing request — safe to print/log.
     * Never contains the URL Message Key.
     */
    public function previewRequest(string $list, string $message): array
    {
        return [
            'method' => 'GET',
            'url' => $this->url,
            'query' => $this->redactedQuery($list, $message),
        ];
    }

    /**
     * 94XXXXXXXXX → 0XXXXXXXXX (the `list` format used by the API's web flow
     * and published wrappers).
     */
    public function listFormatLocal(string $toNumber): string
    {
        return str_starts_with($toNumber, '94') && strlen($toNumber) === 11
            ? '0' . substr($toNumber, 2)
            : $toNumber;
    }

    private function redactedQuery(string $list, string $message): array
    {
        return [
            'esmsqk' => $this->redact($this->key),
            'list' => $list,
            'source_address' => (string) $this->mask,
            'message' => $message,
        ];
    }

    /**
     * Remove the key from any string that might carry it (exception messages
     * embed full URLs including the query string).
     */
    private function scrub(string $text): string
    {
        if (empty($this->key)) {
            return $text;
        }

        return str_replace(
            [$this->key, urlencode($this->key)],
            $this->redact($this->key),
            $text
        );
    }

    private function redact(?string $secret): string
    {
        if (empty($secret)) {
            return '[not set]';
        }

        // Last 4 chars only — identifiable in logs, never reconstructible.
        return '****' . substr($secret, -4);
    }
}
