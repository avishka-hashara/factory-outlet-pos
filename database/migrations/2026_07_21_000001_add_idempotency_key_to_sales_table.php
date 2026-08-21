<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * DB-level guarantee that one checkout attempt == one sale.
     *
     * The POS sends a per-purchase idempotency key with the checkout request; a
     * double-click or a retry reuses the SAME key. This unique index is what
     * makes that safe under real concurrency: if two identical requests race,
     * only one INSERT can succeed — the loser is rejected by the database and
     * the controller returns the winning sale instead of creating a second one.
     *
     * The column is nullable and NULLs never collide in MySQL or SQLite, so
     * every historical sale (and any client that does not send a key) is
     * unaffected and the index can always be created.
     *
     * Live-deploy safe (same pattern as sales.invoice_number and
     * customers.phone): before touching anything we scan the existing sales for
     * rows that look like the double-submit duplicates this fix prevents — same
     * timestamp to the second, same total, same payment method and the same
     * item lines. They are REPORTED (console + log) for the owner to review and
     * merge. Nothing historical is altered or deleted here.
     */
    public function up(): void
    {
        $this->reportHistoricalDuplicates();

        if (!Schema::hasColumn('sales', 'idempotency_key')) {
            Schema::table('sales', function (Blueprint $table) {
                // UUID from the POS (36 chars); 64 leaves room for other schemes.
                $table->string('idempotency_key', 64)->nullable()->after('bill_token');
            });
        }

        if ($this->hasIndex('sales_idempotency_key_unique')) {
            return;
        }

        // Defensive: only possible if keys were written before the index existed.
        $duplicateKeys = DB::table('sales')
            ->select('idempotency_key', DB::raw('COUNT(*) as occurrences'))
            ->whereNotNull('idempotency_key')
            ->groupBy('idempotency_key')
            ->having('occurrences', '>', 1)
            ->pluck('occurrences', 'idempotency_key');

        if ($duplicateKeys->isNotEmpty()) {
            $lines = $duplicateKeys->map(function ($count, $key) {
                $ids = DB::table('sales')->where('idempotency_key', $key)->orderBy('id')->pluck('id')->implode(', ');

                return "  idempotency_key \"{$key}\" x{$count} (sale ids: {$ids})";
            })->implode(PHP_EOL);

            $this->warn('sales.idempotency_key UNIQUE index NOT added — these sales already share a key and must be reviewed manually first:'
                .PHP_EOL.$lines.PHP_EOL
                .'After cleanup run: ALTER TABLE sales ADD UNIQUE sales_idempotency_key_unique (idempotency_key);');

            return; // deploy continues; historical rows untouched
        }

        Schema::table('sales', function (Blueprint $table) {
            $table->unique('idempotency_key', 'sales_idempotency_key_unique');
        });
    }

    public function down(): void
    {
        if ($this->hasIndex('sales_idempotency_key_unique')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->dropUnique('sales_idempotency_key_unique');
            });
        }

        if (Schema::hasColumn('sales', 'idempotency_key')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->dropColumn('idempotency_key');
            });
        }
    }

    /**
     * Report — never touch — sales that look like the same purchase recorded
     * twice by a double-submitted checkout.
     */
    private function reportHistoricalDuplicates(): void
    {
        $candidates = DB::table('sales')
            ->select('created_at', 'total', 'payment_method', DB::raw('COUNT(*) as occurrences'))
            ->whereNotNull('created_at')
            ->groupBy('created_at', 'total', 'payment_method')
            ->having('occurrences', '>', 1)
            ->get();

        if ($candidates->isEmpty()) {
            return;
        }

        $groups = [];

        foreach ($candidates as $candidate) {
            $sales = DB::table('sales')
                ->where('created_at', $candidate->created_at)
                ->where('total', $candidate->total)
                ->where('payment_method', $candidate->payment_method)
                ->orderBy('id')
                ->get(['id', 'invoice_number', 'total', 'payment_method', 'created_at']);

            // Same second + same amount + same method is already suspicious;
            // require the item lines to match too before calling it a duplicate.
            $bySignature = [];
            foreach ($sales as $sale) {
                $bySignature[$this->itemSignature($sale->id)][] = $sale;
            }

            foreach ($bySignature as $group) {
                if (count($group) > 1) {
                    $groups[] = $group;
                }
            }
        }

        if (empty($groups)) {
            return;
        }

        $lines = [];
        foreach ($groups as $group) {
            $first = $group[0];
            $detail = collect($group)
                ->map(fn ($s) => "#{$s->invoice_number} (sale id {$s->id})")
                ->implode(' , ');
            $lines[] = "  {$first->created_at} | {$first->payment_method} | Rs. {$first->total} | x".count($group)." → {$detail}";
        }

        $this->warn('Possible DUPLICATE SALES found (same timestamp, items, amount and payment method) — '
            .'these predate the checkout idempotency fix and are NOT modified or deleted by this migration. '
            .'Please review and merge/void them manually:'
            .PHP_EOL.implode(PHP_EOL, $lines));
    }

    /**
     * A stable fingerprint of a sale's lines, so two sales are only reported as
     * duplicates when they contain exactly the same items.
     */
    private function itemSignature(int $saleId): string
    {
        return DB::table('sale_items')
            ->where('sale_id', $saleId)
            ->orderBy('product_id')
            ->orderBy('gift_card_id')
            ->orderBy('qty')
            ->get(['product_id', 'product_variant_id', 'gift_card_id', 'qty', 'price'])
            ->map(fn ($i) => implode(':', [
                $i->product_id ?? '-',
                $i->product_variant_id ?? '-',
                $i->gift_card_id ?? '-',
                $i->qty,
                $i->price,
            ]))
            ->sort()
            ->implode('|');
    }

    private function hasIndex(string $name): bool
    {
        return collect(Schema::getIndexes('sales'))->contains(fn ($ix) => $ix['name'] === $name);
    }

    private function warn(string $message): void
    {
        Log::warning($message);
        echo PHP_EOL.'WARNING: '.$message.PHP_EOL;
    }
};
