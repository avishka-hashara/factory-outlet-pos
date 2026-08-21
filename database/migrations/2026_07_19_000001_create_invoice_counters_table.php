<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per calendar day. The daily invoice sequence is taken from this
     * row with a locked atomic increment (SELECT ... FOR UPDATE) inside the
     * sale transaction, replacing the racy COUNT(*)+1 generation.
     */
    public function up(): void
    {
        Schema::create('invoice_counters', function (Blueprint $table) {
            $table->date('counter_date')->primary();
            $table->unsignedInteger('last_sequence')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_counters');
    }
};
