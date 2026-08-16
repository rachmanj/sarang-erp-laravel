<?php

namespace App\Providers;

use App\Services\WhatsApp\FonnteGateway;
use App\Services\WhatsApp\WhatsAppGatewayInterface;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Support\ServiceProvider;

class WhatsAppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(WhatsAppGatewayInterface::class, FonnteGateway::class);
        $this->app->singleton(WhatsAppService::class);
    }

    public function boot(): void
    {
        //
    }
}
