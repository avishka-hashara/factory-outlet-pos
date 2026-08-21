<?php

use App\Services\Sms\SriLankanPhone;

test('normalises accepted formats to 94XXXXXXXXX', function (string $input) {
    expect(SriLankanPhone::tryNormalize($input))->toBe('94771234567');
})->with([
    'local with leading zero' => '0771234567',
    'international with plus' => '+94771234567',
    'international without plus' => '94771234567',
    'international 00 prefix' => '0094771234567',
    'bare subscriber number' => '771234567',
    'dashes and spaces' => '077-123 4567',
    'spaces' => '077 123 4567',
    'parentheses and dots' => '(077) 123.4567',
    'plus with spaces' => '+94 77 123 4567',
]);

test('accepts every Sri Lankan mobile prefix', function (string $prefix) {
    expect(SriLankanPhone::tryNormalize("0{$prefix}1234567"))->toBe("94{$prefix}1234567");
})->with(['70', '71', '72', '74', '75', '76', '77', '78']);

test('rejects invalid numbers', function (?string $input) {
    expect(SriLankanPhone::tryNormalize($input))->toBeNull()
        ->and(SriLankanPhone::isValid($input))->toBeFalse();
})->with([
    'Colombo landline' => '0112345678',
    'Kandy landline' => '0812345678',
    'landline in international format' => '+94112345678',
    'unallocated prefix 73' => '0731234567',
    'unallocated prefix 79' => '0791234567',
    'too short' => '07712345',
    'too long' => '077123456789',
    '94 prefix but wrong length' => '9477123456',
    'letters' => 'not-a-number',
    'empty string' => '',
    'null value' => null,
]);

test('normalize throws for invalid input', function () {
    SriLankanPhone::normalize('0112345678');
})->throws(InvalidArgumentException::class);

test('normalize returns the number for valid input', function () {
    expect(SriLankanPhone::normalize('077-123 4567'))->toBe('94771234567');
});

test('masks a normalised number for display', function () {
    expect(SriLankanPhone::mask('94771234567'))->toBe('9477•••4567');
});
