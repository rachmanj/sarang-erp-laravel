<?php

use App\Console\Commands\PollWhatsAppMessages;
use App\Console\Commands\SendDailyReportWhatsApp;
use App\Console\Commands\ServeCommand;
use App\Http\Middleware\ResolveTrustedProxyClientIp;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        ServeCommand::class,
        PollWhatsAppMessages::class,
        SendDailyReportWhatsApp::class,
    ])
    ->withMiddleware(function (Middleware $middleware) {
        // cloudflared reaches the app over the Docker bridge (e.g. 172.20.0.1); 172.16.0.0/12 is the default bridge range.
        $middleware->trustProxies(at: [
            '127.0.0.1',
            '172.16.0.0/12',
        ]);

        $middleware->append(ResolveTrustedProxyClientIp::class);

        // Register Spatie Permission middleware aliases
        $middleware->alias([
            'customer.api' => \App\Http\Middleware\CustomerApiAuthentication::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
