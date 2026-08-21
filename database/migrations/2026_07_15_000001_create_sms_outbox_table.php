<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_outbox', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('sales')->cascadeOnDelete();
            $table->string('purpose', 40)->default('bill');
            $table->string('to_number', 15);
            $table->string('body', 480);
            $table->string('status', 10)->default('pending')->index();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->string('provider_message_id')->nullable();
            $table->json('provider_response')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('sent_at')->nullable();

            // Idempotency: one SMS per invoice per purpose
            $table->unique(['invoice_id', 'purpose']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_outbox');
    }
};
