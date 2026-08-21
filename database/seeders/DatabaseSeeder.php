<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Call AdminSeeder
        $this->call([
            AdminSeeder::class,
        ]);

        // Admin users
        User::firstOrCreate(
            ['email' => 'kasun@shop.com'],
            [
                'name' => 'Kasun Bandara',
                'phone' => '+94 77 123 4567',
                'password' => bcrypt('password'),
                'role' => 'Admin',
                'sales_today' => 12,
                'status' => 'Active',
            ]
        );

        User::firstOrCreate(
            ['email' => 'nimali@shop.com'],
            [
                'name' => 'Nimali Perera',
                'phone' => '+94 77 234 5678',
                'password' => bcrypt('password'),
                'role' => 'Admin',
                'sales_today' => 8,
                'status' => 'Active',
            ]
        );

        // Staff users
        User::firstOrCreate(
            ['email' => 'saman@shop.com'],
            [
                'name' => 'Saman Silva',
                'phone' => '+94 77 345 6789',
                'password' => bcrypt('password'),
                'role' => 'Staff',
                'sales_today' => 15,
                'status' => 'Active',
            ]
        );

        User::firstOrCreate(
            ['email' => 'dilini@shop.com'],
            [
                'name' => 'Dilini Fernando',
                'phone' => '+94 77 456 7890',
                'password' => bcrypt('password'),
                'role' => 'Staff',
                'sales_today' => 6,
                'status' => 'Active',
            ]
        );

        User::firstOrCreate(
            ['email' => 'ruwan@shop.com'],
            [
                'name' => 'Ruwan Jayasinghe',
                'phone' => '+94 77 567 8901',
                'password' => bcrypt('password'),
                'role' => 'Staff',
                'sales_today' => 10,
                'status' => 'Active',
            ]
        );

        User::firstOrCreate(
            ['email' => 'chamara@shop.com'],
            [
                'name' => 'Chamara Wijesinghe',
                'phone' => '+94 77 678 9012',
                'password' => bcrypt('password'),
                'role' => 'Staff',
                'sales_today' => 0,
                'status' => 'Inactive',
            ]
        );
    }
}
