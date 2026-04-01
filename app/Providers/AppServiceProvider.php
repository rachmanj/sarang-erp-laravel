<?php

namespace App\Providers;

use App\Observers\AuditLogObserver;
use App\Services\Help\HelpOpenRouterClient;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(HelpOpenRouterClient::class, function () {
            return new HelpOpenRouterClient(
                config('services.openrouter.api_key'),
                (string) config('services.openrouter.site_url', config('app.url')),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerAuditLogObservers();
    }

    /**
     * Register audit log observers for all critical models.
     */
    protected function registerAuditLogObservers(): void
    {
        if (! config('audit-log.enabled')) {
            return;
        }

        $models = config('audit-log.observed_models', []);

        foreach ($models as $model) {
            if (class_exists($model)) {
                $model::observe(AuditLogObserver::class);
            }
        }
    }
}
