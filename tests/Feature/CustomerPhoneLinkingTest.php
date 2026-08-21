<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CustomerPhoneLinkingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Never hit the real SMS gateway in these tests.
        config(['services.sms.driver' => 'log']);
    }

    private function cashier(): User
    {
        return User::factory()->create(['role' => 'Admin', 'status' => 'Active']);
    }

    private function makeSale(array $overrides = []): Sale
    {
        return Sale::create(array_merge([
            'user_id'        => User::factory()->create(['status' => 'Active'])->id,
            'invoice_number' => '07-20-' . str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT),
            'total'          => 12500.00,
            'payment_method' => 'cash',
        ], $overrides));
    }

    private function sendSms(User $user, Sale $sale, string $phone)
    {
        return $this->actingAs($user)
            ->postJson("/api/invoices/{$sale->id}/send-sms", ['phone' => $phone]);
    }

    // CHANGE 1 -------------------------------------------------------------

    public function test_customer_saves_with_no_name_phone_only(): void
    {
        $response = $this->actingAs($this->cashier())
            ->postJson(route('customers.store'), ['phone' => '0771234567']);

        $response->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseCount('customers', 1);
        $customer = Customer::first();
        $this->assertNull($customer->name);
        $this->assertSame('0771234567', $customer->phone);
    }

    // CHANGE 2 -------------------------------------------------------------

    public function test_sms_to_new_number_creates_one_customer_and_attaches_the_sale(): void
    {
        Queue::fake();
        $user = $this->cashier();
        $sale = $this->makeSale();

        $this->sendSms($user, $sale, '0771234567')->assertOk();

        $this->assertDatabaseCount('customers', 1);
        $customer = Customer::first();
        $this->assertSame('0771234567', $customer->phone);
        $this->assertNull($customer->name);
        $this->assertSame($customer->id, $sale->fresh()->customer_id);
    }

    public function test_sms_to_existing_number_attaches_without_duplicate_and_keeps_name(): void
    {
        Queue::fake();
        $user = $this->cashier();
        $existing = Customer::create(['name' => 'Nimal', 'phone' => '0771234567']);
        $sale = $this->makeSale();

        // Same person, different input format.
        $this->sendSms($user, $sale, '+94771234567')->assertOk();

        $this->assertDatabaseCount('customers', 1);
        $this->assertSame($existing->id, $sale->fresh()->customer_id);
        $this->assertSame('Nimal', $existing->fresh()->name); // untouched
    }

    public function test_all_phone_formats_resolve_to_the_same_customer(): void
    {
        $a = Customer::findOrCreateByPhone('0771234567');
        $b = Customer::findOrCreateByPhone('94771234567');
        $c = Customer::findOrCreateByPhone('+94771234567');

        $this->assertNotNull($a);
        $this->assertSame($a->id, $b->id);
        $this->assertSame($a->id, $c->id);
        $this->assertDatabaseCount('customers', 1);
        $this->assertSame('0771234567', $a->phone);
    }

    public function test_concurrent_sends_to_a_new_number_create_only_one_customer(): void
    {
        Queue::fake();
        $user = $this->cashier();

        // Two independent sales, both SMS'd to the same brand-new number.
        $saleA = $this->makeSale();
        $saleB = $this->makeSale();

        $this->sendSms($user, $saleA, '0771234567')->assertOk();
        $this->sendSms($user, $saleB, '94771234567')->assertOk(); // same person, other format

        // Exactly one customer, both sales under it.
        $this->assertDatabaseCount('customers', 1);
        $customer = Customer::first();
        $this->assertSame($customer->id, $saleA->fresh()->customer_id);
        $this->assertSame($customer->id, $saleB->fresh()->customer_id);

        // DB-level guarantee that the real race can never double-insert.
        $this->expectException(QueryException::class);
        Customer::withoutEvents(fn () => Customer::query()->insert([
            'phone'      => '0771234567',
            'created_at' => now(),
            'updated_at' => now(),
        ]));
    }
}
