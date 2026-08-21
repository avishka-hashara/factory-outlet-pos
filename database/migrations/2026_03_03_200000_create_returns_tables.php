<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users'); // cashier who processed return
            $table->decimal('total_refunded', 12, 2)->default(0);
            $table->enum('refund_method', ['cash', 'card', 'store_credit', 'bank'])->default('cash');
            $table->enum('reason', ['customer_request', 'damaged', 'wrong_item', 'quality_issue', 'other'])->default('customer_request');
            $table->text('notes')->nullable();
            $table->enum('status', ['completed', 'cancelled'])->default('completed');
            $table->timestamps();
        });

        Schema::create('return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_id')->constrained('returns')->onDelete('cascade');
            $table->foreignId('sale_item_id')->constrained('sale_items')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->unsignedInteger('qty');
            $table->decimal('price', 10, 2); // unit price at time of return
            $table->enum('condition', ['good', 'damaged', 'other'])->default('good');
            $table->boolean('restock')->default(true); // add back to inventory?
            $table->text('condition_notes')->nullable();
            $table->timestamps();
        });

        // Track returns on the sales table for fast querying
        Schema::table('sales', function (Blueprint $table) {
            $table->boolean('has_return')->default(false)->after('payment_method');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('has_return');
        });
        Schema::dropIfExists('return_items');
        Schema::dropIfExists('returns');
    }
};
