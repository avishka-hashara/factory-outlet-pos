<?php

namespace Database\Factories;

use App\Models\GiftCard;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GiftCard>
 */
class GiftCardFactory extends Factory
{
    protected $model = GiftCard::class;

    public function definition(): array
    {
        return [
            'card_number' => 'GC'.fake()->unique()->numerify('########'),
            'barcode' => fake()->unique()->numerify('#############'),
            'value' => '5000.00',
            'issued_date' => null,
            'expiry_date' => null,
            'status' => GiftCard::STATUS_AVAILABLE,
        ];
    }

    public function sold(): static
    {
        return $this->state(fn () => [
            'status' => GiftCard::STATUS_SOLD,
            'issued_at' => now(),
        ]);
    }

    public function gifted(): static
    {
        return $this->state(fn () => [
            'status' => GiftCard::STATUS_GIFTED,
            'issued_at' => now(),
        ]);
    }

    public function redeemed(): static
    {
        return $this->state(fn () => [
            'status' => GiftCard::STATUS_REDEEMED,
            'issued_at' => now()->subDay(),
            'redeemed_at' => now(),
        ]);
    }
}
