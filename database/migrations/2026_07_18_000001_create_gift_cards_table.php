<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Each row is ONE unique physical gift card (not inventory quantity).
     */
    public function up(): void
    {
        Schema::create('gift_cards', function (Blueprint $table) {
            $table->id();
            $table->string('card_number')->unique();
            $table->string('barcode')->unique();
            $table->decimal('value', 12, 2);
            $table->date('issued_date')->nullable();
            $table->date('expiry_date')->nullable(); // null = never expires
            $table->enum('status', ['available', 'sold', 'gifted', 'redeemed', 'cancelled'])
                ->default('available')
                ->index();
            $table->foreignId('issued_sale_id')->nullable()->constrained('sales')->nullOnDelete();
            $table->timestamp('issued_at')->nullable();
            $table->foreignId('redeemed_sale_id')->nullable()->constrained('sales')->nullOnDelete();
            $table->timestamp('redeemed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gift_cards');
    }
};
