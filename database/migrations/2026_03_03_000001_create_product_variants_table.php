<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('color');
            $table->string('color_code')->nullable(); // hex code e.g. #FFD700
            $table->string('sku')->nullable();
            $table->decimal('price', 10, 2)->nullable(); // null = use product price
            $table->integer('stock')->default(0);
            $table->string('shopify_variant_id')->nullable();
            $table->string('inventory_item_id')->nullable();
            $table->string('shopify_location_id')->nullable();
            $table->string('image_url')->nullable();
            $table->timestamps();
        });

        // Add product_variant_id to sale_items
        Schema::table('sale_items', function (Blueprint $table) {
            $table->foreignId('product_variant_id')->nullable()->after('product_id');
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn('product_variant_id');
        });

        Schema::dropIfExists('product_variants');
    }
};
