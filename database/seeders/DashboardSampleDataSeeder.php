<?php

namespace Database\Seeders;

use App\Models\CashDrawerSession;
use App\Models\CashDrawerTransaction;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DashboardSampleDataSeeder extends Seeder
{
    public function run(): void
    {
        // Flush dashboard cache keys so fresh seed displays immediately
        Cache::flush();

        $admin = User::where('role', 'Admin')->first() ?? User::first();
        if (!$admin) {
            $admin = User::create([
                'name' => 'Admin',
                'email' => 'admin@possystem.lk',
                'password' => bcrypt('password123'),
                'role' => 'Admin',
                'status' => 'Active',
            ]);
        }

        // Clean existing test sales/drawers to avoid unique key collisions
        SaleItem::query()->delete();
        Sale::query()->delete();
        CashDrawerTransaction::query()->delete();
        CashDrawerSession::query()->delete();

        // =========================================================
        // 1. SEED PRODUCTS
        // =========================================================
        $productsData = [
            [
                'name' => 'Sony WH-1000XM5 Headphones',
                'sku' => 'SNY-WH5-BLK',
                'category' => 'Electronics',
                'price' => 42500,
                'stock' => 2, // Critical Low
                'image_url' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=200&auto=format&fit=crop&q=80',
            ],
            [
                'name' => 'Keychron K2 Pro Keyboard',
                'sku' => 'KCH-K2P-BRN',
                'category' => 'Electronics',
                'price' => 28000,
                'stock' => 0, // Out of stock
                'image_url' => 'https://images.unsplash.com/photo-1587829741301-dc798b83add3?w=200&auto=format&fit=crop&q=80',
            ],
            [
                'name' => 'Logitech MX Master 3S Mouse',
                'sku' => 'LOG-MX3S-GRY',
                'category' => 'Electronics',
                'price' => 18500,
                'stock' => 3, // Critical Low
                'image_url' => 'https://images.unsplash.com/photo-1615663245857-ac93bb7c39e7?w=200&auto=format&fit=crop&q=80',
            ],
            [
                'name' => 'Classic Pima Cotton T-Shirt',
                'sku' => 'TEE-PIMA-BLK',
                'category' => 'Men',
                'price' => 2450,
                'stock' => 48,
                'image_url' => 'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?w=200&auto=format&fit=crop&q=80',
            ],
            [
                'name' => 'Slim Fit Linen Button-Down Shirt',
                'sku' => 'SHIRT-LIN-WHT',
                'category' => 'Men',
                'price' => 4800,
                'stock' => 25,
                'image_url' => 'https://images.unsplash.com/photo-1596755094514-f87e34085b2c?w=200&auto=format&fit=crop&q=80',
            ],
            [
                'name' => 'Relaxed Fit Denim Jeans',
                'sku' => 'JNS-REL-BLU',
                'category' => 'Men',
                'price' => 6500,
                'stock' => 19,
                'image_url' => 'https://images.unsplash.com/photo-1542272604-787c3835535d?w=200&auto=format&fit=crop&q=80',
            ],
            [
                'name' => 'Oversized French Terry Hoodie',
                'sku' => 'HD-FT-CHA',
                'category' => 'Unisex',
                'price' => 5900,
                'stock' => 16,
                'image_url' => 'https://images.unsplash.com/photo-1556905055-8f358a7a47b2?w=200&auto=format&fit=crop&q=80',
            ],
            [
                'name' => 'Everyday Minimal Chino Trousers',
                'sku' => 'CHN-MIN-NAV',
                'category' => 'Men',
                'price' => 4200,
                'stock' => 24,
                'image_url' => 'https://images.unsplash.com/photo-1473966968600-fa801b869a1a?w=200&auto=format&fit=crop&q=80',
            ],
            [
                'name' => 'Premium Italian Leather Belt',
                'sku' => 'ACC-BELT-BRN',
                'category' => 'Accessories',
                'price' => 3200,
                'stock' => 1, // Critical Low
                'image_url' => 'https://images.unsplash.com/photo-1624222247344-550fb60583dc?w=200&auto=format&fit=crop&q=80',
            ],
            [
                'name' => 'Water-Resistant Commuter Backpack',
                'sku' => 'BAG-COM-BLK',
                'category' => 'Accessories',
                'price' => 8900,
                'stock' => 4, // Critical Low
                'image_url' => 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=200&auto=format&fit=crop&q=80',
            ],
        ];

        $createdProducts = [];
        foreach ($productsData as $pData) {
            $prod = Product::updateOrCreate(
                ['sku' => $pData['sku']],
                $pData
            );
            $createdProducts[] = $prod;
        }

        // =========================================================
        // 2. SEED CUSTOMERS
        // =========================================================
        $customersData = [
            ['name' => 'John Doe', 'phone' => '0771234567', 'email' => 'john.d@gmail.com'],
            ['name' => 'Sarah Silva', 'phone' => '0772345678', 'email' => 'sarah.s@gmail.com'],
            ['name' => 'Kasun Perera', 'phone' => '0773456789', 'email' => 'kasun.p@gmail.com'],
            ['name' => 'Anura Fernando', 'phone' => '0774567890', 'email' => 'anura.f@gmail.com'],
            ['name' => 'Malini Senanayake', 'phone' => '0775678901', 'email' => 'malini.s@gmail.com'],
        ];

        $createdCustomers = [];
        foreach ($customersData as $cData) {
            $cust = Customer::updateOrCreate(
                ['phone' => $cData['phone']],
                $cData
            );
            $createdCustomers[] = $cust;
        }

        // =========================================================
        // 3. SEED HISTORICAL SALES (Last 30 Days)
        // =========================================================
        $now = Carbon::now();
        $counter = 1000;

        // Seed 30 days of past data
        for ($dayOffset = 30; $dayOffset >= 2; $dayOffset--) {
            $dayDate = $now->copy()->subDays($dayOffset);
            $dailyTxCount = rand(2, 5);

            for ($t = 0; $t < $dailyTxCount; $t++) {
                $counter++;
                $hour = rand(9, 20);
                $minute = rand(5, 55);
                $saleTime = $dayDate->copy()->setHour($hour)->setMinute($minute)->setSecond(rand(0, 59));

                $cust = rand(0, 1) ? $createdCustomers[array_rand($createdCustomers)] : null;
                $paymentMethods = ['cash', 'card', 'koko', 'mintpay', 'bank'];
                $payMethod = $paymentMethods[array_rand($paymentMethods)];

                // Pick 1-3 random products
                $itemCount = rand(1, 3);
                $subtotal = 0;
                $lineItems = [];

                for ($i = 0; $i < $itemCount; $i++) {
                    $prod = $createdProducts[array_rand($createdProducts)];
                    $qty = rand(1, 2);
                    $price = (float) $prod->price;
                    $lineTotal = $price * $qty;
                    $subtotal += $lineTotal;

                    $lineItems[] = [
                        'product_id' => $prod->id,
                        'qty' => $qty,
                        'price' => $price,
                        'item_label' => $prod->name,
                    ];
                }

                $discount = rand(0, 10) > 7 ? round($subtotal * 0.05, 2) : 0;
                $total = max(0, $subtotal - $discount);

                $sale = Sale::create([
                    'user_id' => $admin->id,
                    'customer_id' => $cust?->id,
                    'invoice_number' => sprintf('INV-%05d', $counter),
                    'bill_token' => Str::random(12),
                    'subtotal' => $subtotal,
                    'discount_type' => $discount > 0 ? 'fixed' : null,
                    'discount_value' => $discount,
                    'discount_amount' => $discount,
                    'total' => $total,
                    'payment_method' => $payMethod,
                    'amount_received' => $total,
                    'change_amount' => 0,
                    'has_return' => false,
                    'created_at' => $saleTime,
                    'updated_at' => $saleTime,
                ]);

                foreach ($lineItems as $item) {
                    SaleItem::create([
                        'sale_id' => $sale->id,
                        'product_id' => $item['product_id'],
                        'qty' => $item['qty'],
                        'price' => $item['price'],
                        'item_label' => $item['item_label'],
                    ]);
                }
            }
        }

        // =========================================================
        // 4. SEED YESTERDAY SALES (For comparison trend & vs yday KPI)
        // =========================================================
        $yday = $now->copy()->subDay();
        $ydayTxTimes = [
            ['hour' => 9, 'minute' => 15, 'method' => 'cash', 'prods' => [3, 7]],
            ['hour' => 10, 'minute' => 45, 'method' => 'card', 'prods' => [4, 5]],
            ['hour' => 12, 'minute' => 30, 'method' => 'koko', 'prods' => [0]],
            ['hour' => 14, 'minute' => 10, 'method' => 'mintpay', 'prods' => [6]],
            ['hour' => 16, 'minute' => 20, 'method' => 'cash', 'prods' => [3, 8]],
            ['hour' => 18, 'minute' => 50, 'method' => 'card', 'prods' => [5, 9]],
            ['hour' => 20, 'minute' => 15, 'method' => 'cash', 'prods' => [4]],
        ];

        foreach ($ydayTxTimes as $tx) {
            $counter++;
            $saleTime = $yday->copy()->setHour($tx['hour'])->setMinute($tx['minute'])->setSecond(15);
            $subtotal = 0;
            $lineItems = [];

            foreach ($tx['prods'] as $pIdx) {
                $prod = $createdProducts[$pIdx];
                $qty = 1;
                $price = (float) $prod->price;
                $subtotal += $price * $qty;
                $lineItems[] = ['product_id' => $prod->id, 'qty' => $qty, 'price' => $price, 'item_label' => $prod->name];
            }

            $sale = Sale::create([
                'user_id' => $admin->id,
                'customer_id' => rand(0, 1) ? $createdCustomers[array_rand($createdCustomers)]->id : null,
                'invoice_number' => sprintf('INV-%05d', $counter),
                'bill_token' => Str::random(12),
                'subtotal' => $subtotal,
                'total' => $subtotal,
                'payment_method' => $tx['method'],
                'amount_received' => $subtotal,
                'change_amount' => 0,
                'has_return' => false,
                'created_at' => $saleTime,
                'updated_at' => $saleTime,
            ]);

            foreach ($lineItems as $item) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'qty' => $item['qty'],
                    'price' => $item['price'],
                    'item_label' => $item['item_label'],
                ]);
            }
        }

        // =========================================================
        // 5. SEED TODAY SALES (Matching mock values & distribution)
        // =========================================================
        $today = $now->copy();
        $todayTx = [
            ['hour' => 8, 'minute' => 45, 'method' => 'cash', 'cust' => null, 'prods' => [3, 8], 'total' => 5650],
            ['hour' => 10, 'minute' => 15, 'method' => 'koko', 'cust' => 2, 'prods' => [4, 7], 'total' => 9000],
            ['hour' => 11, 'minute' => 30, 'method' => 'card', 'cust' => 1, 'prods' => [5, 6], 'total' => 12400],
            ['hour' => 12, 'minute' => 45, 'method' => 'cash', 'cust' => null, 'prods' => [9], 'total' => 8900],
            ['hour' => 13, 'minute' => 30, 'method' => 'card', 'cust' => null, 'prods' => [3], 'total' => 2450],
            ['hour' => 13, 'minute' => 52, 'method' => 'card', 'cust' => null, 'prods' => [5, 6, 9], 'total' => 21300],
            ['hour' => 14, 'minute' => 15, 'method' => 'koko', 'cust' => 0, 'prods' => [0], 'total' => 42500],
            ['hour' => 14, 'minute' => 28, 'method' => 'cash', 'cust' => null, 'prods' => [4, 7, 8], 'total' => 12200],
            ['hour' => 15, 'minute' => 10, 'method' => 'mintpay', 'cust' => 3, 'prods' => [6], 'total' => 5900],
            ['hour' => 16, 'minute' => 30, 'method' => 'koko', 'cust' => 4, 'prods' => [5], 'total' => 6500],
        ];

        foreach ($todayTx as $tx) {
            $counter++;
            $saleTime = $today->copy()->setHour($tx['hour'])->setMinute($tx['minute'])->setSecond(rand(10, 50));
            $cust = isset($tx['cust']) && isset($createdCustomers[$tx['cust']]) ? $createdCustomers[$tx['cust']] : null;
            $subtotal = 0;
            $lineItems = [];

            foreach ($tx['prods'] as $pIdx) {
                $prod = $createdProducts[$pIdx];
                $qty = 1;
                $price = (float) $prod->price;
                $subtotal += $price * $qty;
                $lineItems[] = ['product_id' => $prod->id, 'qty' => $qty, 'price' => $price, 'item_label' => $prod->name];
            }

            $sale = Sale::create([
                'user_id' => $admin->id,
                'customer_id' => $cust?->id,
                'invoice_number' => sprintf('INV-%05d', $counter),
                'bill_token' => Str::random(12),
                'subtotal' => $subtotal,
                'total' => $tx['total'] ?? $subtotal,
                'payment_method' => $tx['method'],
                'amount_received' => $tx['total'] ?? $subtotal,
                'change_amount' => 0,
                'has_return' => false,
                'created_at' => $saleTime,
                'updated_at' => $saleTime,
            ]);

            foreach ($lineItems as $item) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'qty' => $item['qty'],
                    'price' => $item['price'],
                    'item_label' => $item['item_label'],
                ]);
            }
        }

        // =========================================================
        // 6. SEED CASH DRAWER OPEN SHIFT
        // =========================================================
        $session = CashDrawerSession::create([
            'user_id' => $admin->id,
            'opening_balance' => 10000.00,
            'status' => 'open',
            'opened_at' => $now->copy()->startOfDay()->setHour(8)->setMinute(0),
            'notes' => 'Morning shift open with standard Rs. 10,000 float',
        ]);

        // Cash in transaction (Float addition)
        CashDrawerTransaction::create([
            'session_id' => $session->id,
            'user_id' => $admin->id,
            'type' => 'cash_in',
            'amount' => 5000.00,
            'description' => 'Midday change top-up float',
            'created_at' => $now->copy()->startOfDay()->setHour(11)->setMinute(30),
        ]);

        // Cash out transaction (Petty cash payout)
        CashDrawerTransaction::create([
            'session_id' => $session->id,
            'user_id' => $admin->id,
            'type' => 'cash_out',
            'amount' => 1500.00,
            'description' => 'Store cleaning supplies purchase',
            'created_at' => $now->copy()->startOfDay()->setHour(13)->setMinute(0),
        ]);
    }
}
