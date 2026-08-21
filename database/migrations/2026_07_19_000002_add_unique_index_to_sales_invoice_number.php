<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * DB-level safety net: the database itself rejects duplicate invoice
     * numbers, even if some future code path is buggy.
     *
     * Live-deploy safe: if historical duplicates already exist, the index
     * cannot be created — the migration then REPORTS every duplicate (console
     * + log) and completes WITHOUT crashing, so the deploy is not blocked.
     * After the duplicates are corrected manually, add the index with the SQL
     * printed in the warning (or re-run this migration via migrate:refresh on
     * this file). No historical rows are altered or deleted here.
     */
    public function up(): void
    {
        $duplicates = DB::table('sales')
            ->select('invoice_number', DB::raw('COUNT(*) as occurrences'))
            ->whereNotNull('invoice_number')
            ->groupBy('invoice_number')
            ->having('occurrences', '>', 1)
            ->orderBy('invoice_number')
            ->get();

        if ($duplicates->isNotEmpty()) {
            $lines = $duplicates->map(function ($dup) {
                $ids = DB::table('sales')
                    ->where('invoice_number', $dup->invoice_number)
                    ->orderBy('id')
                    ->pluck('id')
                    ->implode(', ');

                return "  invoice_number \"{$dup->invoice_number}\" x{$dup->occurrences} (sale ids: {$ids})";
            })->implode(PHP_EOL);

            $message = 'sales.invoice_number UNIQUE index NOT added — existing duplicates must be corrected manually first:'
                .PHP_EOL.$lines.PHP_EOL
                .'After cleanup run: ALTER TABLE sales ADD UNIQUE sales_invoice_number_unique (invoice_number);';

            Log::warning($message);
            echo PHP_EOL.'WARNING: '.$message.PHP_EOL;

            return; // deploy continues; historical rows untouched
        }

        Schema::table('sales', function (Blueprint $table) {
            $table->unique('invoice_number', 'sales_invoice_number_unique');
        });
    }

    public function down(): void
    {
        if (collect(Schema::getIndexes('sales'))->contains(fn ($ix) => $ix['name'] === 'sales_invoice_number_unique')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->dropUnique('sales_invoice_number_unique');
            });
        }
    }
};
