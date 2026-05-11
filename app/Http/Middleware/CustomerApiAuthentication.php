<?php

namespace App\Http\Middleware;

use App\Models\CustomerApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CustomerApiAuthentication
{
    public function handle(Request $request, Closure $next): Response
    {
        $bearer = $request->bearerToken();
        if ($bearer === null || $bearer === '') {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $hash = hash('sha256', $bearer);
        $key = CustomerApiKey::query()->where('token', $hash)->first();
        if ($key === null) {
            return response()->json(['message' => 'Invalid API token.'], 401);
        }

        if ($key->isExpired()) {
            return response()->json(['message' => 'API token expired.'], 401);
        }

        $businessPartner = $key->businessPartner;
        if (
            $businessPartner === null
            || $businessPartner->partner_type !== 'customer'
            || $businessPartner->status !== 'active'
        ) {
            return response()->json(['message' => 'Invalid API token.'], 401);
        }

        $key->forceFill(['last_used_at' => now()])->saveQuietly();

        $request->attributes->set('api_business_partner', $businessPartner);

        return $next($request);
    }
}
