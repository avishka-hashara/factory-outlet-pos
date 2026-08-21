<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            // Random token for the public /b/{token} bill view — never expose the sale ID
            $table->string('bill_token', 32)->nullable()->unique()->after('invoice_number');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropUnique(['bill_token']);
            $table->dropColumn('bill_token');
        });
    }
};
