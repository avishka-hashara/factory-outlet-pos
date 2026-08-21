<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->json('permissions'); // array of page keys
            $table->boolean('is_system')->default(false); // system roles can't be deleted
            $table->timestamps();
        });

        // Seed default roles
        DB::table('roles')->insert([
            [
                'name'        => 'Cashier',
                'description' => 'Access to POS Billing only',
                'permissions' => json_encode(['dashboard', 'pos']),
                'is_system'   => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'name'        => 'Sales Manager',
                'description' => 'POS, Sales History, Returns & Reports',
                'permissions' => json_encode(['dashboard', 'pos', 'pos_log', 'sales', 'returns', 'reports']),
                'is_system'   => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'name'        => 'Store Manager',
                'description' => 'Full access except Staff & Settings',
                'permissions' => json_encode(['dashboard', 'pos', 'pos_log', 'products', 'sales', 'returns', 'reports']),
                'is_system'   => false,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
