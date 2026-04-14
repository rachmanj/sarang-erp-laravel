<?php

namespace App\Providers;

use App\Models\AssistantConversation;
use App\Observers\AuditLogObserver;
use App\Services\Assistant\DomainAssistantOpenRouterClient;
use App\Services\Help\HelpOpenRouterClient;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
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

        $this->app->singleton(DomainAssistantOpenRouterClient::class, function () {
            return new DomainAssistantOpenRouterClient(
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
        Paginator::useBootstrapFour();

        Route::bind('conversation', function (string $value) {
            if (! Auth::check()) {
                abort(404);
            }

            return AssistantConversation::query()
                ->where('user_id', Auth::id())
                ->whereKey($value)
                ->firstOrFail();
        });

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
