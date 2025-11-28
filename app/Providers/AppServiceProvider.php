<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Observers\AuditLogObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
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
        if (!config('audit-log.enabled')) {
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
