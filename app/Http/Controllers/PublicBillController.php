<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Setting;

/**
 * Public, read-only bill view — /b/{token}.
 *
 * Looked up by random token only (never the sale ID), no auth, and shows
 * nothing beyond what is on the printed receipt.
 */
class PublicBillController extends Controller
{
    public function show(string $token)
    {
        abort_if(strlen($token) < 8, 404);

        $sale = Sale::with(['items.product', 'items.variant'])
            ->where('bill_token', $token)
            ->first();

        abort_if(!$sale, 404);

        if ($sale->billLinkExpired()) {
            return response()->view('bill.expired', [], 410);
        }

        $settings = Setting::getAllSettings();

        return view('bill.show', compact('sale', 'settings'));
    }
}
