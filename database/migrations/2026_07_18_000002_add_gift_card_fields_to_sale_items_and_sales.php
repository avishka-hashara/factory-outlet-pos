<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A sold gift card appears on the bill as a sale line that is not a
     * product: product_id becomes nullable and the line carries gift_card_id
     * plus a printable item_label instead.
     */
    public function up(): void
    {
        // sale_items.product_id has no FK constraint in the original migration,
        // so a plain column change is safe on every driver.
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE `sale_items` MODIFY `product_id` BIGINT UNSIGNED NULL');
        } else {
            Schema::table('sale_items', function (Blueprint $table) {
                $table->foreignId('product_id')->nullable()->change();
            });
        }

        Schema::table('sale_items', function (Blueprint $table) {
            $table->foreignId('gift_card_id')->nullable()->constrained('gift_cards')->nullOnDelete();
            $table->string('item_label')->nullable();
        });

        Schema::table('sales', function (Blueprint $table) {
            // Full card value deducted from this sale's payable at redemption.
            $table->decimal('gift_card_redemption_amount', 12, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('gift_card_redemption_amount');
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('gift_card_id');
            $table->dropColumn('item_label');
        });

        DB::table('sale_items')->whereNull('product_id')->delete();

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE `sale_items` MODIFY `product_id` BIGINT UNSIGNED NOT NULL');
        } else {
            Schema::table('sale_items', function (Blueprint $table) {
                $table->foreignId('product_id')->nullable(false)->change();
            });
        }
    }
};
