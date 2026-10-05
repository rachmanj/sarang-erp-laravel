<?php

namespace App\Support;

use Illuminate\Http\Request;

final class TrustedProxyClientIp
{
    public const CF_CONNECTING_IP_HEADER = 'CF-Connecting-IP';

    public static function apply(Request $request): void
    {
        if (! $request->isFromTrustedProxy()) {
            return;
        }

        $candidate = $request->headers->get(self::CF_CONNECTING_IP_HEADER);
        if (! is_string($candidate) || $candidate === '') {
            return;
        }

        $ip = filter_var($candidate, FILTER_VALIDATE_IP);
        if ($ip === false) {
            return;
        }

        $request->headers->set('X-Forwarded-For', $ip);
    }
}
