<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->index('created_at', 'sales_created_at_idx');
            $table->index('payment_method', 'sales_payment_method_idx');
            $table->index(['created_at', 'payment_method'], 'sales_created_payment_idx');
        });

        Schema::table('returns', function (Blueprint $table) {
            $table->index(['status', 'created_at'], 'returns_status_created_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex('sales_created_at_idx');
            $table->dropIndex('sales_payment_method_idx');
            $table->dropIndex('sales_created_payment_idx');
        });

        Schema::table('returns', function (Blueprint $table) {
            $table->dropIndex('returns_status_created_idx');
        });
    }
};
