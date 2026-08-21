<?php

use App\Services\Sms\SriLankanPhone;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * DB-level guarantee that one phone number == one customer, so the SMS
     * auto-link flow can never race two rows into existence for the same number.
     *
     * Live-deploy safe (same pattern as sales.invoice_number): before adding the
     * index we scan for customers that would collide — either exact duplicate
     * numbers, or the SAME number stored in different formats (0771234567 vs
     * 94771234567 vs +94771234567), which all canonicalise to one value. If any
     * are found, the index is NOT added; every colliding group is reported
     * (console + log) with its numbers and customer IDs and the migration
     * completes WITHOUT crashing or deleting anything. Merge them manually, then
     * re-run this migration (or run the ALTER printed in the warning).
     *
     * NULL / blank phones (walk-ins) are ignored and never collide.
     */
    public function up(): void
    {
        $rows = DB::table('customers')
            ->select('id', 'phone')
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->orderBy('id')
            ->get();

        // Group customers by canonical phone. Non-mobile values fall back to the
        // trimmed raw string so exact landline duplicates are still detected.
        $byCanonical = [];
        foreach ($rows as $row) {
            $canonical = SriLankanPhone::tryNormalizeLocal($row->phone) ?? trim((string) $row->phone);
            if ($canonical === '') {
                continue;
            }
            $byCanonical[$canonical][] = $row;
        }

        $collisions = array_filter($byCanonical, fn ($group) => count($group) > 1);

        if (!empty($collisions)) {
            $lines = [];
            foreach ($collisions as $canonical => $group) {
                $detail = collect($group)
                    ->map(fn ($r) => "id {$r->id} (\"{$r->phone}\")")
                    ->implode(', ');
                $lines[] = "  {$canonical} x" . count($group) . " → {$detail}";
            }

            $message = 'customers.phone UNIQUE index NOT added — these numbers resolve to the same customer and must be merged manually first:'
                . PHP_EOL . implode(PHP_EOL, $lines) . PHP_EOL
                . 'After merging run: ALTER TABLE customers ADD UNIQUE customers_phone_unique (phone);';

            Log::warning($message);
            echo PHP_EOL . 'WARNING: ' . $message . PHP_EOL;

            return; // deploy continues; historical rows untouched
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->unique('phone', 'customers_phone_unique');
        });
    }

    public function down(): void
    {
        if (collect(Schema::getIndexes('customers'))->contains(fn ($ix) => $ix['name'] === 'customers_phone_unique')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropUnique('customers_phone_unique');
            });
        }
    }
};
