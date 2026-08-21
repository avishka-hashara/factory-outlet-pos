<?php

namespace App\Jobs;

use App\Models\SmsOutbox;
use App\Services\Sms\SmsGateway;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Sends one sms_outbox row via the SMS gateway.
 *
 * Up to 3 attempts with exponential backoff (30s, 120s). Permanent gateway
 * failures (invalid number, insufficient credit, bad credentials) stop
 * retrying immediately. Every attempt and full provider response is logged
 * and persisted on the outbox row.
 */
class SendInvoiceSms implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public int $outboxId)
    {
    }

    /** @return array<int> */
    public function backoff(): array
    {
        return [30, 120];
    }

    public function handle(SmsGateway $gateway): void
    {
        $outbox = SmsOutbox::find($this->outboxId);

        if (!$outbox || $outbox->status === SmsOutbox::STATUS_SENT) {
            return;
        }

        $outbox->increment('attempts');

        Log::info('SMS send attempt', [
            'outbox_id' => $outbox->id,
            'invoice_id' => $outbox->invoice_id,
            'to' => $outbox->to_number,
            'attempt' => $this->attempts(),
        ]);

        $result = $gateway->send($outbox->to_number, $outbox->body);

        if ($result->success) {
            $outbox->update([
                'status' => SmsOutbox::STATUS_SENT,
                'provider_message_id' => $result->providerMessageId,
                'provider_response' => $result->providerResponse,
                'error' => null,
                'sent_at' => now(),
            ]);

            Log::info('SMS sent', [
                'outbox_id' => $outbox->id,
                'invoice_id' => $outbox->invoice_id,
                'provider_message_id' => $result->providerMessageId,
                'provider_response' => $result->providerResponse,
            ]);

            return;
        }

        $outbox->update([
            'provider_response' => $result->providerResponse,
            'error' => $result->error,
        ]);

        Log::error('SMS send failed', [
            'outbox_id' => $outbox->id,
            'invoice_id' => $outbox->invoice_id,
            'attempt' => $this->attempts(),
            'retryable' => $result->retryable,
            'error' => $result->error,
            'provider_response' => $result->providerResponse,
        ]);

        if (!$result->retryable) {
            $outbox->update(['status' => SmsOutbox::STATUS_FAILED]);
            $this->fail(new \RuntimeException($result->error));

            return;
        }

        if ($this->attempts() >= $this->tries) {
            $outbox->update(['status' => SmsOutbox::STATUS_FAILED]);
        }

        throw new \RuntimeException('SMS send failed (attempt ' . $this->attempts() . '): ' . $result->error);
    }

    /**
     * Safety net: if the job dies outside handle() (serialisation, crash,
     * timeout), never leave the row stuck on "pending".
     */
    public function failed(?\Throwable $e): void
    {
        SmsOutbox::where('id', $this->outboxId)
            ->where('status', SmsOutbox::STATUS_PENDING)
            ->update([
                'status' => SmsOutbox::STATUS_FAILED,
                'error' => $e?->getMessage() ?? 'Job failed',
            ]);
    }
}
