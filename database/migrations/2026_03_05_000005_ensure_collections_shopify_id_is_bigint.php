<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL: keep the original raw statements so production behaviour is unchanged.
        // Other drivers (e.g. SQLite in the test suite) can't parse MySQL DDL, so use
        // the schema builder there (it rebuilds the table and preserves the unique index).
        if (DB::getDriverName() !== 'mysql') {
            Schema::table('collections', function (Blueprint $table) {
                $table->string('shopify_id')->nullable()->change();
            });

            return;
        }

        // Drop the unique index first (it may be named differently on some hosts)
        try {
            DB::statement('ALTER TABLE `collections` DROP INDEX `collections_shopify_id_unique`');
        } catch (\Exception $e) {
            // Index may not exist or have a different name — safe to ignore
        }

        // Convert shopify_id from integer to string to avoid numeric overflow with large Shopify IDs
        DB::statement('ALTER TABLE `collections` MODIFY `shopify_id` VARCHAR(255) NULL');

        // Re-add unique index
        DB::statement('ALTER TABLE `collections` ADD UNIQUE INDEX `collections_shopify_id_unique` (`shopify_id`)');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            Schema::table('collections', function (Blueprint $table) {
                $table->unsignedBigInteger('shopify_id')->nullable()->change();
            });

            return;
        }

        try {
            DB::statement('ALTER TABLE `collections` DROP INDEX `collections_shopify_id_unique`');
        } catch (\Exception $e) {}

        DB::statement('ALTER TABLE `collections` MODIFY `shopify_id` BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE `collections` ADD UNIQUE INDEX `collections_shopify_id_unique` (`shopify_id`)');
    }
};
