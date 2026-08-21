<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    /**
     * Products are NOT seeded here.
     * All products must be imported from Shopify via the sync endpoint:
     *   POST /shopify/sync
     * or from the Settings → Shopify Sync page.
     */
    public function run(): void
    {
        $this->command->info('No demo products seeded. Use Shopify Sync to import products.');
    }
}
