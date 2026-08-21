<?php

use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Services\InvoiceNumberService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

function invCashier(): User
{
    return User::factory()->create(['role' => 'Staff', 'status' => 'Active']);
}

function invProduct(int $stock = 50): Product
{
    return Product::create([
        'name' => 'Inv Test Chain',
        'sku' => 'INV' . uniqid(),
        'price' => '1000',
        'stock' => $stock,
    ]);
}

function invCheckout(User $user, Product $product): \Illuminate\Testing\TestResponse
{
    return test()->actingAs($user)->postJson('/pos/checkout', [
        'items' => [['product_id' => $product->id, 'variant_id' => null, 'quantity' => 1, 'price' => 1000]],
        'payment_method' => 'cash',
        'subtotal' => 1000,
        'total' => 1000,
        'amount_received' => 1000,
        'change_amount' => 0,
    ]);
}

/*
|--------------------------------------------------------------------------
| Concurrency: two simultaneous generations get different numbers
|--------------------------------------------------------------------------
*/

it('gives two concurrent sale creations different invoice numbers', function () {
    // The old code derived the sequence from COUNT(*) of today's sales, so two
    // checkouts whose generation ran before either sale row was committed
    // computed the SAME number. Reproduce exactly that interleaving: generate
    // twice with no sale committed in between — the old code returns identical
    // values here; the counter-based generator must not.
    $service = new InvoiceNumberService;

    $first = DB::transaction(fn () => $service->next());
    $second = DB::transaction(fn () => $service->next());

    expect($first)->not->toBe($second);

    // And end-to-end: back-to-back checkouts stay distinct
    $user = invCashier();
    $product = invProduct();
    invCheckout($user, $product)->assertOk()->assertJson(['success' => true]);
    invCheckout($user, $product)->assertOk()->assertJson(['success' => true]);

    $numbers = Sale::pluck('invoice_number');
    expect($numbers->count())->toBe($numbers->unique()->count());
});

it('does not reissue an invoice number after a sale is deleted', function () {
    // Second real-world duplicate path of the old COUNT(*)+1 code: deleting a
    // sale shrank the count, so the next sale re-used an existing number.
    $user = invCashier();
    $product = invProduct();

    invCheckout($user, $product)->assertOk(); // -01
    invCheckout($user, $product)->assertOk(); // -02

    $first = Sale::orderBy('id')->first();
    $first->items()->delete();
    $first->delete();

    invCheckout($user, $product)->assertOk(); // old code: -02 (duplicate!); fixed: -03

    $prefix = now()->format('m') . '-' . now()->format('d') . '-';
    $numbers = Sale::pluck('invoice_number');
    expect($numbers->count())->toBe($numbers->unique()->count())
        ->and($numbers)->toContain($prefix . '03')
        ->and($numbers->filter(fn ($n) => $n === $prefix . '02')->count())->toBe(1);
});

/*
|--------------------------------------------------------------------------
| DB safety net: unique index holds
|--------------------------------------------------------------------------
*/

it('rejects a duplicate invoice number at the database level', function () {
    $user = invCashier();
    Sale::create(['user_id' => $user->id, 'invoice_number' => '07-16-05', 'total' => 100, 'payment_method' => 'cash']);

    expect(fn () => Sale::create([
        'user_id' => $user->id,
        'invoice_number' => '07-16-05',
        'total' => 200,
        'payment_method' => 'cash',
    ]))->toThrow(QueryException::class);

    expect(Sale::where('invoice_number', '07-16-05')->count())->toBe(1);
});

it('identifies invoice-number duplicate-key errors for the checkout retry', function () {
    $user = invCashier();
    Sale::create(['user_id' => $user->id, 'invoice_number' => '07-16-07', 'total' => 100, 'payment_method' => 'cash']);

    try {
        Sale::create(['user_id' => $user->id, 'invoice_number' => '07-16-07', 'total' => 100, 'payment_method' => 'cash']);
        $this->fail('Expected a duplicate-key QueryException');
    } catch (QueryException $e) {
        expect(InvoiceNumberService::isDuplicateInvoiceNumber($e))->toBeTrue();
    }

    // Other unique violations must NOT trigger the invoice retry path
    Sale::create(['user_id' => $user->id, 'invoice_number' => '07-16-08', 'total' => 100, 'payment_method' => 'cash'])
        ->forceFill(['bill_token' => 'tok-duplicate'])->save();
    try {
        Sale::create(['user_id' => $user->id, 'invoice_number' => '07-16-09', 'total' => 100, 'payment_method' => 'cash'])
            ->forceFill(['bill_token' => 'tok-duplicate'])->save();
        $this->fail('Expected a duplicate-key QueryException on bill_token');
    } catch (QueryException $e) {
        expect(InvoiceNumberService::isDuplicateInvoiceNumber($e))->toBeFalse();
    }
});

/*
|--------------------------------------------------------------------------
| Format & sequencing preserved: MM-DD-NN, daily reset, 2-digit padding
|--------------------------------------------------------------------------
*/

it('keeps the MM-DD-sequence format with daily reset and zero padding', function () {
    $user = invCashier();
    $product = invProduct();
    $service = new InvoiceNumberService;

    invCheckout($user, $product)->assertOk();
    invCheckout($user, $product)->assertOk();

    $prefix = now()->format('m') . '-' . now()->format('d') . '-';
    expect(Sale::orderBy('id')->pluck('invoice_number')->all())
        ->toBe([$prefix . '01', $prefix . '02']);

    // Every number matches the exact shape (e.g. "07-16-05")
    Sale::pluck('invoice_number')->each(
        fn ($n) => expect($n)->toMatch('/^\d{2}-\d{2}-\d{2,}$/')
    );

    // Sequence grows past 99 without truncation, same as the old str_pad rule
    DB::table('invoice_counters')->where('counter_date', now()->toDateString())->update(['last_sequence' => 99]);
    expect(DB::transaction(fn () => $service->next()))->toBe($prefix . '100');

    // Daily reset: tomorrow starts again at 01
    $this->travelTo(now()->addDay()->startOfDay()->addHours(10));
    $tomorrowPrefix = now()->format('m') . '-' . now()->format('d') . '-';
    expect(DB::transaction(fn () => $service->next()))->toBe($tomorrowPrefix . '01');
    $this->travelBack();
});

it('continues from existing data when the counter row is missing (mid-day deploy)', function () {
    // Live deploy scenario: sales exist for today (made by the old code) but
    // the counter table is empty. The first generated number must continue
    // the day's sequence, not restart at 01.
    $user = invCashier();
    $prefix = now()->format('m') . '-' . now()->format('d') . '-';
    Sale::create(['user_id' => $user->id, 'invoice_number' => $prefix . '37', 'total' => 100, 'payment_method' => 'cash']);

    $service = new InvoiceNumberService;
    expect(DB::transaction(fn () => $service->next()))->toBe($prefix . '38');
});
