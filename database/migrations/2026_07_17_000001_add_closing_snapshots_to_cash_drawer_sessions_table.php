<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_drawer_sessions', function (Blueprint $table) {
            $table->decimal('cash_sales_total', 10, 2)->nullable()->after('closing_balance');
            $table->integer('cash_sales_count_total')->nullable()->after('cash_sales_total');
            $table->decimal('cash_refunds_total', 10, 2)->nullable()->after('cash_sales_count_total');
            $table->decimal('cash_in_total', 10, 2)->nullable()->after('cash_refunds_total');
            $table->decimal('cash_out_total', 10, 2)->nullable()->after('cash_in_total');
            $table->decimal('expected_balance_total', 10, 2)->nullable()->after('cash_out_total');
            $table->decimal('difference_total', 10, 2)->nullable()->after('expected_balance_total');
        });
    }

    public function down(): void
    {
        Schema::table('cash_drawer_sessions', function (Blueprint $table) {
            $table->dropColumn([
                'cash_sales_total',
                'cash_sales_count_total',
                'cash_refunds_total',
                'cash_in_total',
                'cash_out_total',
                'expected_balance_total',
                'difference_total',
            ]);
        });
    }
};
