<?php

use App\Models\User;

it('renders the POS modals including the gift card popups', function () {
    $admin = User::factory()->create(['role' => 'Admin', 'status' => 'Active']);

    $html = $this->actingAs($admin)->get('/pos')->getContent();

    expect($html)
        ->toContain('cashPaymentModal')
        ->toContain('giftCardRedeemModal')
        ->toContain('giftCardConfirmModal');
});
