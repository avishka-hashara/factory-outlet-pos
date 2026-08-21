<?php

namespace App\Services;

use App\Models\Sale;
use Illuminate\Database\QueryException;

/**
 * Makes POS checkout idempotent: one purchase can only ever create one sale.
 *
 * The POS generates a UUID when a cart is finalised and sends it with the
 * checkout request. A double-clicked payment button, or a retry after a
 * dropped connection, sends the SAME key — so the server can tell "this is the
 * same purchase again" apart from "this is a second, genuine purchase".
 *
 * Two layers, in this order:
 *   1. Lookup — if a sale already carries the key, that sale is returned and no
 *      new work happens at all (no invoice number consumed, no gift card
 *      redeemed again, no stock decremented twice).
 *   2. The sales.idempotency_key UNIQUE index — the authority. When two
 *      identical requests race, both may pass the lookup, but only one INSERT
 *      can survive. The loser's whole transaction rolls back and the controller
 *      re-runs the lookup, which now finds the winner.
 *
 * Layer 2 is why the frontend guard alone is not enough: it is the only thing
 * that holds when the two requests arrive on different PHP workers.
 */
class CheckoutIdempotencyService
{
    /**
     * Normalise the client-supplied key. Blank/absent means "no idempotency" —
     * checkout still works (older cached page, API client), it just loses the
     * duplicate protection.
     */
    public function keyFrom(mixed $raw): ?string
    {
        $key = trim((string) ($raw ?? ''));

        return $key === '' ? null : $key;
    }

    /**
     * The sale already recorded under this key, if any.
     */
    public function findSale(?string $key): ?Sale
    {
        if ($key === null) {
            return null;
        }

        return Sale::with('customer')->where('idempotency_key', $key)->first();
    }

    /**
     * Whether a QueryException is specifically the sales.idempotency_key unique
     * violation — i.e. a concurrent identical checkout beat us to the INSERT —
     * and not some other constraint (invoice_number, bill_token, ...).
     */
    public static function isDuplicateKey(QueryException $e): bool
    {
        $message = $e->getMessage();

        $isDuplicateKey = str_contains($message, 'Duplicate entry')       // MySQL 1062
            || str_contains($message, 'UNIQUE constraint failed');        // SQLite

        return $isDuplicateKey
            && (str_contains($message, 'sales_idempotency_key_unique')
                || str_contains($message, 'sales.idempotency_key'));
    }
}
