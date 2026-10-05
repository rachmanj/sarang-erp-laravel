<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class TrustedProxyClientIpTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('web')->get('/__test/trusted-proxy-client-ip', function (Request $request) {
            return response()->json([
                'ip' => $request->ip(),
                'secure' => $request->isSecure(),
            ]);
        });
    }

    public function test_trusted_proxy_peer_uses_cf_connecting_ip(): void
    {
        $response = $this->withServerVariables([
            'REMOTE_ADDR' => '172.20.0.1',
        ])->withHeaders([
            'CF-Connecting-IP' => '182.8.178.63',
        ])->get('/__test/trusted-proxy-client-ip');

        $response->assertOk();
        $response->assertJson([
            'ip' => '182.8.178.63',
        ]);
    }

    public function test_untrusted_peer_ignores_spoofed_cf_connecting_ip(): void
    {
        $response = $this->withServerVariables([
            'REMOTE_ADDR' => '8.8.8.8',
        ])->withHeaders([
            'CF-Connecting-IP' => '182.8.178.63',
        ])->get('/__test/trusted-proxy-client-ip');

        $response->assertOk();
        $response->assertJson([
            'ip' => '8.8.8.8',
        ]);
    }

    public function test_trusted_proxy_ignores_invalid_cf_connecting_ip(): void
    {
        $response = $this->withServerVariables([
            'REMOTE_ADDR' => '172.20.0.1',
        ])->withHeaders([
            'CF-Connecting-IP' => 'bukan-ip',
        ])->get('/__test/trusted-proxy-client-ip');

        $response->assertOk();
        $response->assertJson([
            'ip' => '172.20.0.1',
        ]);
    }

    public function test_trusted_proxy_honors_x_forwarded_proto_for_https(): void
    {
        $response = $this->withServerVariables([
            'REMOTE_ADDR' => '172.20.0.1',
        ])->withHeaders([
            'X-Forwarded-Proto' => 'https',
        ])->get('/__test/trusted-proxy-client-ip');

        $response->assertOk();
        $response->assertJson([
            'secure' => true,
        ]);
    }
}
