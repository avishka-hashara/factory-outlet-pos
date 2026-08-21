<?php

namespace App\Http\Controllers;

use App\Jobs\SendInvoiceSms;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\SmsOutbox;
use App\Services\Sms\BillMessageBuilder;
use App\Services\Sms\SriLankanPhone;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceSmsController extends Controller
{
    /**
     * POST /api/invoices/{sale}/send-sms
     *
     * Validates the number, writes an idempotent sms_outbox row and
     * dispatches the queued sender. Returns immediately — never blocks
     * checkout on the gateway.
     */
    public function send(Request $request, Sale $sale): JsonResponse
    {
        $data = $request->validate([
            'phone' => 'nullable|string|max:25',
            'resend' => 'nullable|boolean',
        ]);

        // Sales History re-send: the customer lost the SMS, so an already-sent
        // bill may be sent again. Never bypasses the in-flight (pending) guard.
        $resend = (bool) ($data['resend'] ?? false);

        $rawPhone = $data['phone'] ?? $sale->customer?->phone;

        if (!$rawPhone) {
            return response()->json([
                'success' => false,
                'message' => 'No mobile number available. Enter the customer\'s mobile number.',
            ], 422);
        }

        $to = SriLankanPhone::tryNormalize($rawPhone);

        if (!$to) {
            return response()->json([
                'success' => false,
                'message' => 'That is not a valid Sri Lankan mobile number (07X XXX XXXX).',
            ], 422);
        }

        // Auto-save / link the customer by phone — same outcome as selecting a
        // customer before checkout, so this sale shows up under the number on
        // the Customers page. Never creates a duplicate, never overwrites an
        // existing customer's name/details.
        $this->linkSaleToCustomerByPhone($sale, $to);

        $body = $this->buildBody($sale);

        $outbox = SmsOutbox::where('invoice_id', $sale->id)
            ->where('purpose', SmsOutbox::PURPOSE_BILL)
            ->first();

        if ($outbox && $outbox->status === SmsOutbox::STATUS_SENT && !$resend) {
            return $this->statusResponse($outbox, 'Bill was already sent to ' . SriLankanPhone::mask($outbox->to_number) . '.');
        }

        if ($outbox && $outbox->status === SmsOutbox::STATUS_PENDING && !$outbox->isTimedOut()) {
            // Duplicate click — a send is genuinely in flight, don't queue another.
            return $this->statusResponse($outbox, 'Send already in progress.');
        }

        if ($outbox) {
            // failed (retry), timed-out pending (lost job) or deliberate
            // re-send of a sent bill — reset and re-queue.
            // created_at resets the clock; attempts=0 starts a clean send cycle.
            $outbox->update([
                'to_number' => $to,
                'body' => $body,
                'status' => SmsOutbox::STATUS_PENDING,
                'attempts' => 0,
                'error' => null,
                'sent_at' => null,
                'created_at' => now(),
            ]);
        } else {
            try {
                $outbox = SmsOutbox::create([
                    'invoice_id' => $sale->id,
                    'purpose' => SmsOutbox::PURPOSE_BILL,
                    'to_number' => $to,
                    'body' => $body,
                    'status' => SmsOutbox::STATUS_PENDING,
                ]);
            } catch (QueryException $e) {
                // Two rapid clicks raced past the SELECT — the unique index
                // (invoice_id, purpose) caught it. Return the winner's state.
                $outbox = SmsOutbox::where('invoice_id', $sale->id)
                    ->where('purpose', SmsOutbox::PURPOSE_BILL)
                    ->firstOrFail();

                return $this->statusResponse($outbox, 'Send already in progress.');
            }
        }

        SendInvoiceSms::dispatch($outbox->id);

        return $this->statusResponse($outbox, 'Bill SMS queued for ' . SriLankanPhone::mask($to) . '.');
    }

    /**
     * GET /api/invoices/{sale}/sms-status — polled by the POS UI.
     */
    public function status(Sale $sale): JsonResponse
    {
        $outbox = SmsOutbox::where('invoice_id', $sale->id)
            ->where('purpose', SmsOutbox::PURPOSE_BILL)
            ->first();

        if (!$outbox) {
            return response()->json(['success' => true, 'status' => null]);
        }

        // If it has been pending too long, stop spinning and surface a failure
        // with a clear reason (the queue worker is the usual culprit).
        $outbox->resolveIfTimedOut();

        return $this->statusResponse($outbox);
    }

    private function statusResponse(SmsOutbox $outbox, ?string $message = null): JsonResponse
    {
        return response()->json([
            'success' => true,
            'status' => $outbox->status,
            'to_masked' => SriLankanPhone::mask($outbox->to_number),
            'attempts' => $outbox->attempts,
            'error' => $outbox->status === SmsOutbox::STATUS_FAILED ? $outbox->error : null,
            'sent_at' => $outbox->sent_at?->format('h:i A'),
            'message' => $message,
        ]);
    }

    /**
     * Ensure a customer exists for this number and the sale is recorded under
     * it. If the sale is already linked to that same customer, this is a no-op.
     */
    private function linkSaleToCustomerByPhone(Sale $sale, string $normalizedPhone): void
    {
        $customer = Customer::findOrCreateByPhone($normalizedPhone);

        if ($customer && (int) $sale->customer_id !== (int) $customer->id) {
            $sale->customer()->associate($customer)->save();
        }
    }

    private function buildBody(Sale $sale): string
    {
        $invoiceNo = $sale->invoice_number ?: (string) $sale->id;
        $billUrl = url('/b/' . $sale->ensureBillToken()); // url() derives the host from APP_URL

        return BillMessageBuilder::build(
            invoiceNo: $invoiceNo,
            discount: (float) $sale->discount_amount,
            total: (float) $sale->total,
            billUrl: $billUrl,
            // shopName defaults to APP_NAME inside the builder
        );
    }
}
