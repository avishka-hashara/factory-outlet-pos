<?php

namespace App\Services;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Race-safe invoice number generation.
 *
 * Preserves the existing customer-facing format exactly:
 *   MM-DD-NN  (e.g. "07-16-05")
 * where NN is a daily sequence starting at 01, zero-padded to a minimum of two
 * digits (three digits from 100 onward), resetting each calendar day.
 *
 * The old implementation derived the sequence from COUNT(*) of today's sales —
 * a check-then-write race (two concurrent checkouts read the same count) that
 * also reissued numbers after a sale was deleted. This service instead takes
 * the sequence from a per-day counter row under SELECT ... FOR UPDATE, so
 * concurrent checkouts serialize on the row lock and each gets a distinct
 * number. Deleted sales no longer cause reuse because the counter only moves
 * forward.
 */
class InvoiceNumberService
{
    /**
     * Generate the next invoice number.
     *
     * MUST be called inside the DB transaction that creates the sale: the
     * row lock is what serializes concurrent checkouts, and a rolled-back sale
     * releases its number's counter increment with it.
     */
    public function next(): string
    {
        $now = now();
        $today = $now->toDateString();
        $prefix = $now->format('m').'-'.$now->format('d').'-';

        // Ensure today's counter row exists (INSERT IGNORE — race-safe: if two
        // firsts collide, one insert wins and the other is a no-op).
        DB::table('invoice_counters')->insertOrIgnore([
            'counter_date' => $today,
            'last_sequence' => 0,
        ]);

        // Lock the row: concurrent checkouts queue here until commit/rollback.
        $counter = (int) DB::table('invoice_counters')
            ->where('counter_date', $today)
            ->lockForUpdate()
            ->value('last_sequence');

        // Floor by what is already committed under this prefix, so the counter
        // self-heals: mid-day deploys continue from the real max instead of 01,
        // manually inserted rows are never collided with, and a retry after a
        // unique violation sees the winning row and moves past it. The LIKE is
        // deliberately year-agnostic: the format carries no year, so prior
        // years' rows under the same MM-DD also count as taken.
        $dataMax = DB::table('sales')
            ->where('invoice_number', 'like', $prefix.'%')
            ->pluck('invoice_number')
            ->map(function ($number) use ($prefix) {
                $suffix = substr($number, strlen($prefix));

                return preg_match('/^\d+$/', $suffix) ? (int) $suffix : 0;
            })
            ->max() ?? 0;

        $sequence = max($counter, $dataMax) + 1;

        DB::table('invoice_counters')
            ->where('counter_date', $today)
            ->update(['last_sequence' => $sequence]);

        return $prefix.str_pad($sequence, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Whether a QueryException is specifically a duplicate-key violation on
     * the sales.invoice_number unique index (and not some other constraint).
     */
    public static function isDuplicateInvoiceNumber(QueryException $e): bool
    {
        $message = $e->getMessage();

        $isDuplicateKey = str_contains($message, 'Duplicate entry')       // MySQL 1062
            || str_contains($message, 'UNIQUE constraint failed');        // SQLite

        return $isDuplicateKey
            && (str_contains($message, 'sales_invoice_number_unique')
                || str_contains($message, 'sales.invoice_number'));
    }
}
