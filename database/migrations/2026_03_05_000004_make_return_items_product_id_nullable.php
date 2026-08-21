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
        // Other drivers (e.g. SQLite used in the test suite) can't parse MySQL's
        // "DROP FOREIGN KEY / MODIFY" syntax, so use the schema builder there.
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE `return_items` DROP FOREIGN KEY `return_items_product_id_foreign`');
            DB::statement('ALTER TABLE `return_items` MODIFY `product_id` BIGINT UNSIGNED NULL');
            DB::statement('ALTER TABLE `return_items` ADD CONSTRAINT `return_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE SET NULL');

            return;
        }

        Schema::table('return_items', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE `return_items` DROP FOREIGN KEY `return_items_product_id_foreign`');
            DB::table('return_items')->whereNull('product_id')->delete();
            DB::statement('ALTER TABLE `return_items` MODIFY `product_id` BIGINT UNSIGNED NOT NULL');
            DB::statement('ALTER TABLE `return_items` ADD CONSTRAINT `return_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`)');

            return;
        }

        DB::table('return_items')->whereNull('product_id')->delete();

        Schema::table('return_items', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable(false)->change();
        });
    }
};
