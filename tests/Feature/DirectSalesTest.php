<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\ProductCategory;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DirectSalesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        Permission::findOrCreate('ar.invoices.view');
        Permission::findOrCreate('ar.invoices.create');
        Permission::findOrCreate('ar.invoices.post');

        $user = User::factory()->create();
        $user->givePermissionTo(['ar.invoices.view', 'ar.invoices.create', 'ar.invoices.post']);
        $this->actingAs($user);
    }

    /**
     * @return array{item: InventoryItem, revenueAccountId: int, customerId: int, entityId: int, cashAccountId: int}
     */
    private function createStockedItem(int $stockQty = 10): array
    {
        $warehouse = Warehouse::query()->firstOrFail();
        $category = ProductCategory::query()->firstOrFail();
        $currencyId = (int) DB::table('currencies')->value('id');
        $userId = (int) DB::table('users')->value('id');

        $item = InventoryItem::query()->create([
            'code' => 'DS-'.uniqid(),
            'name' => 'Direct Sale Test Item',
            'category_id' => $category->id,
            'default_warehouse_id' => $warehouse->id,
            'unit_of_measure' => 'pcs',
            'purchase_currency_id' => $currencyId,
            'selling_currency_id' => $currencyId,
            'purchase_price' => 5000,
            'selling_price' => 10000,
            'valuation_method' => 'fifo',
            'item_type' => 'item',
            'is_active' => true,
        ]);

        InventoryTransaction::query()->create([
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'transaction_type' => 'purchase',
            'quantity' => $stockQty,
            'unit_cost' => 5000,
            'total_cost' => $stockQty * 5000,
            'transaction_date' => now()->toDateString(),
            'created_by' => $userId,
        ]);

        return [
            'item' => $item,
            'revenueAccountId' => (int) DB::table('accounts')->where('code', '4.1.1.01')->value('id'),
            'customerId' => (int) DB::table('business_partners')->where('partner_type', 'customer')->value('id'),
            'entityId' => (int) DB::table('company_entities')->where('code', '71')->value('id'),
            'cashAccountId' => (int) DB::table('accounts')->where('code', '1.1.1.01')->value('id'),
        ];
    }

    private function createDirectSaleInvoice(array $overrides = []): int
    {
        $ctx = $this->createStockedItem();
        $payload = array_merge([
            'date' => now()->toDateString(),
            'business_partner_id' => $ctx['customerId'],
            'company_entity_id' => $ctx['entityId'],
            'is_direct_sale' => 1,
            'payment_method' => 'credit',
            'description' => 'Direct sale test',
            'lines' => [
                [
                    'account_id' => $ctx['revenueAccountId'],
                    'inventory_item_id' => $ctx['item']->id,
                    'item_code' => $ctx['item']->code,
                    'item_name' => $ctx['item']->name,
                    'description' => $ctx['item']->name,
                    'qty' => 2,
                    'unit_price' => 10000,
                ],
            ],
        ], $overrides);

        $resp = $this->post('/sales-invoices', $payload);
        $resp->assertRedirect();

        return (int) preg_replace('/[^0-9]/', '', (string) last(explode('/', (string) $resp->headers->get('Location'))));
    }

    public function test_credit_direct_sale_posts_balanced_journal_and_reduces_stock(): void
    {
        $invoiceId = $this->createDirectSaleInvoice();

        $this->assertDatabaseHas('sales_invoices', [
            'id' => $invoiceId,
            'is_direct_sale' => 1,
            'payment_method' => 'credit',
            'status' => 'draft',
        ]);

        $itemId = (int) DB::table('sales_invoice_lines')->where('invoice_id', $invoiceId)->value('inventory_item_id');
        $stockBefore = (int) InventoryItem::query()->findOrFail($itemId)->current_stock;

        $postResp = $this->post('/sales-invoices/'.$invoiceId.'/post');
        $postResp->assertRedirect();
        $postResp->assertSessionHasNoErrors();

        $this->assertDatabaseHas('sales_invoices', ['id' => $invoiceId, 'status' => 'posted']);

        $jid = (int) DB::table('journals')->where(['source_type' => 'sales_invoice', 'source_id' => $invoiceId])->value('id');
        $this->assertGreaterThan(0, $jid);

        $sum = DB::table('journal_lines')->where('journal_id', $jid)->selectRaw('SUM(debit) as d, SUM(credit) as c')->first();
        $this->assertEqualsWithDelta((float) $sum->d, (float) $sum->c, 0.01);

        $revenueCredit = (float) DB::table('journal_lines as jl')
            ->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->where('jl.journal_id', $jid)
            ->where('a.code', '4.1.1.01')
            ->value('jl.credit');
        $this->assertGreaterThan(0, $revenueCredit);

        $cogsDebit = (float) DB::table('journal_lines as jl')
            ->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->where('jl.journal_id', $jid)
            ->where('a.code', '5.1.01')
            ->value('jl.debit');
        $this->assertGreaterThan(0, $cogsDebit);

        $this->assertDatabaseHas('inventory_transactions', [
            'item_id' => $itemId,
            'transaction_type' => 'sale',
            'reference_type' => 'sales_invoice_line',
        ]);

        $stockAfter = (int) InventoryItem::query()->findOrFail($itemId)->fresh()->current_stock;
        $this->assertSame($stockBefore - 2, $stockAfter);
    }

    public function test_cash_direct_sale_auto_posts_sales_receipt_and_settles_invoice(): void
    {
        $ctx = $this->createStockedItem();
        $invoiceId = $this->createDirectSaleInvoice([
            'payment_method' => 'cash',
            'cash_account_id' => $ctx['cashAccountId'],
        ]);

        $this->post('/sales-invoices/'.$invoiceId.'/post')->assertRedirect();

        $allocation = DB::table('sales_receipt_allocations')->where('invoice_id', $invoiceId)->first();
        $this->assertNotNull($allocation);

        $receiptId = (int) $allocation->receipt_id;
        $this->assertDatabaseHas('sales_receipts', [
            'id' => $receiptId,
            'status' => 'posted',
        ]);

        $receiptJournalId = (int) DB::table('journals')
            ->where(['source_type' => 'sales_receipt', 'source_id' => $receiptId])
            ->value('id');
        $this->assertGreaterThan(0, $receiptJournalId);

        $invoiceTotal = (float) DB::table('sales_invoices')->where('id', $invoiceId)->value('total_amount');
        $this->assertEqualsWithDelta($invoiceTotal, (float) $allocation->amount, 0.01);
    }

    public function test_direct_sale_post_fails_when_stock_is_insufficient(): void
    {
        $ctx = $this->createStockedItem(stockQty: 1);
        $invoiceId = $this->createDirectSaleInvoice([
            'lines' => [
                [
                    'account_id' => $ctx['revenueAccountId'],
                    'inventory_item_id' => $ctx['item']->id,
                    'item_code' => $ctx['item']->code,
                    'item_name' => $ctx['item']->name,
                    'description' => $ctx['item']->name,
                    'qty' => 5,
                    'unit_price' => 10000,
                ],
            ],
        ]);

        $postResp = $this->post('/sales-invoices/'.$invoiceId.'/post');
        $postResp->assertRedirect();
        $postResp->assertSessionHas('error');

        $this->assertDatabaseHas('sales_invoices', ['id' => $invoiceId, 'status' => 'draft']);
        $this->assertSame(0, DB::table('journals')->where(['source_type' => 'sales_invoice', 'source_id' => $invoiceId])->count());
    }
}
