<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Customer name is no longer mandatory — the phone number identifies the
     * row, so a blank name is fine. Existing names are untouched.
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('name')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Backfill any NULL names so the NOT NULL constraint can be restored
        // without failing on rows created while names were optional.
        \Illuminate\Support\Facades\DB::table('customers')
            ->whereNull('name')
            ->update(['name' => '']);

        Schema::table('customers', function (Blueprint $table) {
            $table->string('name')->nullable(false)->change();
        });
    }
};
