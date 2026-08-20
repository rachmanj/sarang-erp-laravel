<?php

namespace Tests\Feature;

use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderLine;
use App\Models\InventoryItem;
use App\Models\ProductCategory;
use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RepairParentAccountPostingsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed();
    }

    private function accountId(string $code): int
    {
        return (int) DB::table('accounts')->where('code', $code)->value('id');
    }

    /**
     * @return array{journalLineId: int, revenueParentId: int, toolsRevenueId: int}
     */
    private function seedMisPostedRevenueJournalLine(): array
    {
        $toolsCategory = ProductCategory::query()->where('code', 'TOOLS')->firstOrFail();
        $warehouse = Warehouse::query()->firstOrFail();
        $currencyId = (int) DB::table('currencies')->value('id');
        $entityId = (int) DB::table('company_entities')->where('code', '71')->value('id');
        $customerId = (int) DB::table('business_partners')->where('partner_type', 'customer')->value('id');
        $userId = (int) DB::table('users')->orderBy('id')->value('id');

        $revenueParentId = $this->accountId('4.1.1');
        $toolsRevenueId = $this->accountId('4.1.1.11');

        $item = InventoryItem::query()->create([
            'code' => 'T-REPAIR-PARENT-'.uniqid(),
            'name' => 'Repair parent account test item',
            'category_id' => $toolsCategory->id,
            'default_warehouse_id' => $warehouse->id,
            'unit_of_measure' => 'EA',
            'purchase_currency_id' => $currencyId,
            'selling_currency_id' => $currencyId,
            'purchase_price' => 5000,
            'selling_price' => 10000,
            'valuation_method' => 'fifo',
            'item_type' => 'item',
            'is_active' => true,
        ]);

        $so = SalesOrder::query()->create([
            'order_no' => 'T-REPAIR-SO-'.uniqid(),
            'date' => now()->toDateString(),
            'business_partner_id' => $customerId,
            'company_entity_id' => $entityId,
            'currency_id' => $currencyId,
            'exchange_rate' => 1,
            'warehouse_id' => $warehouse->id,
            'status' => 'approved',
            'total_amount' => 20000,
            'created_by' => $userId,
        ]);

        $soLine = SalesOrderLine::query()->create([
            'order_id' => $so->id,
            'account_id' => $revenueParentId,
            'inventory_item_id' => $item->id,
            'item_code' => $item->code,
            'item_name' => $item->name,
            'qty' => 2,
            'delivered_qty' => 0,
            'pending_qty' => 2,
            'unit_price' => 10000,
            'amount' => 20000,
        ]);

        $do = DeliveryOrder::query()->create([
            'do_number' => 'T-REPAIR-DO-'.uniqid(),
            'sales_order_id' => $so->id,
            'business_partner_id' => $customerId,
            'company_entity_id' => $entityId,
            'warehouse_id' => $warehouse->id,
            'delivery_address' => 'Test address',
            'planned_delivery_date' => now()->toDateString(),
            'actual_delivery_date' => now()->toDateString(),
            'status' => 'delivered',
            'approval_status' => 'approved',
            'created_by' => $userId,
        ]);

        DeliveryOrderLine::query()->create([
            'delivery_order_id' => $do->id,
            'sales_order_line_id' => $soLine->id,
            'inventory_item_id' => $item->id,
            'account_id' => $revenueParentId,
            'item_code' => $item->code,
            'item_name' => $item->name,
            'ordered_qty' => 2,
            'delivered_qty' => 2,
            'unit_price' => 10000,
            'amount' => 20000,
            'status' => 'delivered',
        ]);

        $journalId = DB::table('journals')->insertGetId([
            'date' => now()->toDateString(),
            'description' => "Revenue Recognition - DO {$do->do_number}",
            'source_type' => DeliveryOrder::class,
            'source_id' => $do->id,
            'posted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $journalLineId = DB::table('journal_lines')->insertGetId([
            'journal_id' => $journalId,
            'account_id' => $revenueParentId,
            'debit' => 0,
            'credit' => 20000,
            'memo' => "Revenue from DO {$do->do_number} - {$item->name}",
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'journalLineId' => $journalLineId,
            'revenueParentId' => $revenueParentId,
            'toolsRevenueId' => $toolsRevenueId,
        ];
    }

    public function test_command_moves_journal_line_from_revenue_parent_to_category_leaf(): void
    {
        $ctx = $this->seedMisPostedRevenueJournalLine();

        $exitCode = Artisan::call('accounts:repair-parent-account-postings');

        $this->assertSame(0, $exitCode);

        $accountId = (int) DB::table('journal_lines')
            ->where('id', $ctx['journalLineId'])
            ->value('account_id');

        $this->assertSame($ctx['toolsRevenueId'], $accountId);
    }

    public function test_dry_run_does_not_change_journal_line_account(): void
    {
        $ctx = $this->seedMisPostedRevenueJournalLine();

        Artisan::call('accounts:repair-parent-account-postings', ['--dry-run' => true]);

        $accountId = (int) DB::table('journal_lines')
            ->where('id', $ctx['journalLineId'])
            ->value('account_id');

        $this->assertSame($ctx['revenueParentId'], $accountId);
    }

    public function test_second_run_is_idempotent(): void
    {
        $ctx = $this->seedMisPostedRevenueJournalLine();

        Artisan::call('accounts:repair-parent-account-postings');
        Artisan::call('accounts:repair-parent-account-postings');

        $accountId = (int) DB::table('journal_lines')
            ->where('id', $ctx['journalLineId'])
            ->value('account_id');

        $this->assertSame($ctx['toolsRevenueId'], $accountId);
    }
}
