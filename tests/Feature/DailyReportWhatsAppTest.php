<?php

namespace Tests\Feature;

use App\Jobs\SendWhatsAppMessage;
use App\Models\Accounting\SalesInvoice;
use App\Models\PurchaseOrder;
use App\Services\Reports\ReportService;
use App\Services\WhatsApp\DailyReportService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DailyReportWhatsAppTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        Config::set('whatsapp.module_enabled', true);
        Config::set('whatsapp.expiry', null);
        Config::set('whatsapp.owner_phone', '628222222222');

        Bus::fake();
    }

    public function test_daily_report_service_returns_expected_metrics(): void
    {
        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();
        $overdueDate = now()->subDays(10)->toDateString();

        $baselineSalesToday = (float) SalesInvoice::query()
            ->where('status', 'posted')
            ->whereDate('date', $today)
            ->sum('total_amount');
        $baselinePendingPo = PurchaseOrder::query()->pending()->count();
        $baselineOverdue = app(DailyReportService::class)->buildDailyReport()['overdue_invoice_count'];

        $customerId = (int) DB::table('business_partners')->where('partner_type', 'customer')->value('id');
        $entityId = (int) DB::table('company_entities')->where('code', '71')->value('id');
        $currencyId = (int) DB::table('currencies')->value('id');

        $this->createPostedSalesInvoice([
            'invoice_no' => 'DR-SI-TODAY-1-'.uniqid(),
            'date' => $today,
            'total_amount' => 100000,
        ]);
        $this->createPostedSalesInvoice([
            'invoice_no' => 'DR-SI-TODAY-2-'.uniqid(),
            'date' => $today,
            'total_amount' => 200000,
        ]);
        $this->createPostedSalesInvoice([
            'invoice_no' => 'DR-SI-YDAY-'.uniqid(),
            'date' => $yesterday,
            'total_amount' => 999999,
        ]);

        $this->createPostedSalesInvoice([
            'invoice_no' => 'DR-SI-OVERDUE-OUT-'.uniqid(),
            'date' => $overdueDate,
            'due_date' => $overdueDate,
            'total_amount' => 500000,
            'business_partner_id' => $customerId,
            'company_entity_id' => $entityId,
            'currency_id' => $currencyId,
        ]);
        $overduePaid = $this->createPostedSalesInvoice([
            'invoice_no' => 'DR-SI-OVERDUE-PAID-'.uniqid(),
            'date' => $overdueDate,
            'due_date' => $overdueDate,
            'total_amount' => 300000,
            'business_partner_id' => $customerId,
            'company_entity_id' => $entityId,
            'currency_id' => $currencyId,
        ]);
        DB::table('sales_receipt_allocations')->insert([
            'receipt_id' => $this->createSalesReceiptId($customerId, $entityId, $currencyId),
            'invoice_id' => $overduePaid->id,
            'amount' => 300000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        PurchaseOrder::withoutEvents(fn () => $this->createPendingPurchaseOrder());
        PurchaseOrder::withoutEvents(fn () => $this->createPendingPurchaseOrder());

        $cashPrefixes = config('cash_flow.account_prefixes.cash_and_bank', ['1.1.1']);
        $expectedCashBalance = app(ReportService::class)->balanceSheetDisplayTotalForPrefixes($today, $cashPrefixes, true);

        $report = app(DailyReportService::class)->buildDailyReport();

        $this->assertArrayHasKey('sales_today', $report);
        $this->assertArrayHasKey('cash_bank_balance', $report);
        $this->assertArrayHasKey('po_pending_count', $report);
        $this->assertArrayHasKey('overdue_invoice_count', $report);

        $this->assertEqualsWithDelta($baselineSalesToday + 300000, $report['sales_today'], 0.01);
        $this->assertEqualsWithDelta($expectedCashBalance, $report['cash_bank_balance'], 0.01);
        $this->assertSame($baselinePendingPo + 2, $report['po_pending_count']);
        $this->assertSame($baselineOverdue + 1, $report['overdue_invoice_count']);
    }

    public function test_send_daily_report_command_dispatches_whatsapp_message_when_enabled(): void
    {
        $this->createPostedSalesInvoice([
            'invoice_no' => 'DR-CMD-SI-'.uniqid(),
            'date' => now()->toDateString(),
            'total_amount' => 75000,
        ]);

        Artisan::call('whatsapp:send-daily-report');

        Bus::assertDispatched(SendWhatsAppMessage::class, function (SendWhatsAppMessage $job) {
            return $job->to === '628222222222'
                && $job->messageType === 'daily_report'
                && str_contains($job->message, 'Laporan Harian')
                && str_contains($job->message, 'Penjualan hari ini:')
                && str_contains($job->message, 'Saldo Kas/Bank:')
                && str_contains($job->message, 'PO menunggu persetujuan:')
                && str_contains($job->message, 'Invoice jatuh tempo:');
        });
    }

    public function test_send_daily_report_command_skips_when_module_disabled(): void
    {
        Config::set('whatsapp.module_enabled', false);

        Artisan::call('whatsapp:send-daily-report');

        Bus::assertNothingDispatched();
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function createPostedSalesInvoice(array $overrides = []): SalesInvoice
    {
        $customerId = (int) DB::table('business_partners')->where('partner_type', 'customer')->value('id');
        $entityId = (int) DB::table('company_entities')->where('code', '71')->value('id');
        $currencyId = (int) DB::table('currencies')->value('id');

        return SalesInvoice::query()->create(array_merge([
            'invoice_no' => 'DR-SI-'.uniqid(),
            'date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'business_partner_id' => $customerId,
            'company_entity_id' => $entityId,
            'currency_id' => $currencyId,
            'exchange_rate' => 1,
            'total_amount' => 100000,
            'status' => 'posted',
            'closure_status' => 'open',
            'posted_at' => now(),
        ], $overrides));
    }

    protected function createPendingPurchaseOrder(): PurchaseOrder
    {
        $currencyId = (int) DB::table('currencies')->value('id');
        $warehouseId = (int) DB::table('warehouses')->value('id');
        $vendorId = (int) DB::table('business_partners')->where('partner_type', 'supplier')->value('id');
        $entityId = (int) DB::table('company_entities')->where('code', '71')->value('id');

        return PurchaseOrder::query()->create([
            'order_no' => 'DR-PO-'.uniqid(),
            'date' => now()->toDateString(),
            'business_partner_id' => $vendorId,
            'company_entity_id' => $entityId,
            'warehouse_id' => $warehouseId,
            'currency_id' => $currencyId,
            'order_type' => 'item',
            'status' => 'draft',
            'approval_status' => 'pending',
            'total_amount' => 100,
        ]);
    }

    protected function createSalesReceiptId(int $customerId, int $entityId, int $currencyId): int
    {
        return (int) DB::table('sales_receipts')->insertGetId([
            'receipt_no' => 'DR-SR-'.uniqid(),
            'date' => now()->toDateString(),
            'business_partner_id' => $customerId,
            'company_entity_id' => $entityId,
            'currency_id' => $currencyId,
            'exchange_rate' => 1,
            'total_amount' => 300000,
            'status' => 'posted',
            'posted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
