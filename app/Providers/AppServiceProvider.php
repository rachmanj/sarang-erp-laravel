<?php

namespace App\Providers;

use App\Models\Accounting\CashExpense;
use App\Models\Accounting\Journal;
use App\Models\Accounting\PurchaseInvoice;
use App\Models\Accounting\PurchasePayment;
use App\Models\Accounting\SalesInvoice;
use App\Models\Accounting\SalesReceipt;
use App\Models\AssistantConversation;
use App\Models\DeliveryOrder;
use App\Models\GoodsReceiptPO;
use App\Models\GRGIHeader;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Observers\AccountingStartDateObserver;
use App\Observers\AuditLogObserver;
use App\Services\Assistant\DomainAssistantOpenRouterClient;
use App\Services\Bank\BankReconciliationOpenRouterClient;
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

        $this->app->singleton(BankReconciliationOpenRouterClient::class, function () {
            return new BankReconciliationOpenRouterClient(
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
        $this->registerAccountingStartDateGuard();
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

    protected function registerAccountingStartDateGuard(): void
    {
        $models = [
            PurchaseInvoice::class,
            SalesInvoice::class,
            Journal::class,
            PurchasePayment::class,
            SalesReceipt::class,
            CashExpense::class,
            GRGIHeader::class,
            GoodsReceiptPO::class,
            DeliveryOrder::class,
            PurchaseOrder::class,
            SalesOrder::class,
        ];

        foreach ($models as $model) {
            $model::observe(AccountingStartDateObserver::class);
        }
    }
}
