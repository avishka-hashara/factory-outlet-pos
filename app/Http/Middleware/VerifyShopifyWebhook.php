<?php

namespace App\Http\Middleware;

use App\Services\ShopifyService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyShopifyWebhook
{
    /**
     * Verify that the webhook request is authentically from Shopify
     * by validating the HMAC signature.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Integration disabled → webhooks are dormant. Reject before any
        // signature/config work so no disabled path can throw.
        if (!ShopifyService::isEnabled()) {
            return response()->json(['error' => 'Shopify integration is disabled'], 404);
        }

        $hmacHeader = $request->header('X-Shopify-Hmac-Sha256');
        $secret = config('services.shopify.api_secret');

        if (!$hmacHeader || !$secret) {
            Log::warning('Shopify webhook rejected: Missing HMAC header or API secret');
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $calculatedHmac = base64_encode(
            hash_hmac('sha256', $request->getContent(), $secret, true)
        );

        if (!hash_equals($calculatedHmac, $hmacHeader)) {
            Log::warning('Shopify webhook rejected: HMAC mismatch', [
                'expected' => $calculatedHmac,
                'received' => $hmacHeader,
            ]);
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
