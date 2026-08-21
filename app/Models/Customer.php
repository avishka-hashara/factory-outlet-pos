<?php

namespace App\Models;

use App\Services\Sms\SriLankanPhone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\QueryException;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'address',
    ];

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * Canonicalise the phone on write so the same number in any format
     * (0771234567 / 94771234567 / +94771234567) is always stored one way and
     * every code path (POS add, POS quick-add, SMS auto-link) maps to ONE row.
     *
     * Valid SL mobiles → local 0XXXXXXXXX. Non-mobile input (landline, foreign)
     * is stored as trimmed text. Blank → null (walk-in; NULLs don't collide on
     * the unique index).
     */
    public function setPhoneAttribute($value): void
    {
        if ($value === null) {
            $this->attributes['phone'] = null;
            return;
        }

        $trimmed = trim((string) $value);
        if ($trimmed === '') {
            $this->attributes['phone'] = null;
            return;
        }

        $this->attributes['phone'] = SriLankanPhone::tryNormalizeLocal($trimmed) ?? $trimmed;
    }

    /**
     * Find the customer for a phone number, creating a minimal (phone-only,
     * name-blank) record if none exists. Phone is the unique key.
     *
     * Race-safe: two concurrent callers for the same NEW number both miss the
     * SELECT, both attempt the INSERT, the DB unique index rejects the loser,
     * and the loser re-fetches the winning row — never a duplicate.
     *
     * Returns null only when the input is not a valid SL mobile (nothing to key
     * on). An existing customer's name/details are never modified here.
     */
    public static function findOrCreateByPhone(?string $rawPhone): ?self
    {
        $canonical = SriLankanPhone::tryNormalizeLocal($rawPhone);
        if ($canonical === null) {
            return null;
        }

        $existing = self::where('phone', $canonical)->first();
        if ($existing) {
            return $existing;
        }

        try {
            return self::create(['phone' => $canonical]);
        } catch (QueryException $e) {
            // Lost the race to the unique index — return the winner.
            $winner = self::where('phone', $canonical)->first();
            if ($winner) {
                return $winner;
            }

            throw $e; // some other constraint — don't swallow it
        }
    }
}
