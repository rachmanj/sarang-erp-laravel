<?php

namespace App\Http\Middleware;

use App\Support\TrustedProxyClientIp;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTrustedProxyClientIp
{
    public function handle(Request $request, Closure $next): Response
    {
        TrustedProxyClientIp::apply($request);

        return $next($request);
    }
}
