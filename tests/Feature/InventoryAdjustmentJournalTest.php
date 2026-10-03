<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\ProductCategory;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use ReflectionMethod;
use Tests\TestCase;

class InventoryAdjustmentJournalTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;

    private InventoryService $inventoryService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->actor = User::factory()->create();
        $this->actingAs($this->actor);

        $this->inventoryService = app(InventoryService::class);
    }

    /**
     * @return array{item: InventoryItem, warehouse: Warehouse, inventoryAccountId: int, counterAccountId: int}
     */
    private function createItemWithAccounts(): array
    {
        $warehouse = Warehouse::query()->firstOrFail();
        $category = ProductCategory::query()->with('inventoryAccount')->firstOrFail();
        $inventoryAccountId = (int) ($category->getEffectiveAccountByType('inventory')?->id
            ?? DB::table('accounts')->where('code', '1.1.3.01.01')->value('id'));

        $counterAccountId = (int) DB::table('accounts')->where('code', '5.7')->value('id');
        $this->assertGreaterThan(0, $inventoryAccountId);
        $this->assertGreaterThan(0, $counterAccountId);

        $currencyId = (int) DB::table('currencies')->value('id');

        $item = InventoryItem::query()->create([
            'code' => 'T-ADJ-'.uniqid(),
            'name' => 'Adjustment Journal Test Item',
            'category_id' => $category->id,
            'default_warehouse_id' => $warehouse->id,
            'unit_of_measure' => 'pcs',
            'purchase_currency_id' => $currencyId,
            'selling_currency_id' => $currencyId,
            'purchase_price' => 1000,
            'selling_price' => 1200,
            'valuation_method' => 'fifo',
            'item_type' => 'item',
            'is_active' => true,
        ]);

        return [
            'item' => $item,
            'warehouse' => $warehouse,
            'inventoryAccountId' => $inventoryAccountId,
            'counterAccountId' => $counterAccountId,
        ];
    }

    private function journalCountForTransaction(int $transactionId): int
    {
        return (int) DB::table('journals')
            ->where('source_type', 'inventory_adjustment')
            ->where('source_id', $transactionId)
            ->count();
    }

    private function invokePostInventoryAdjustmentJournalAgain(InventoryTransaction $transaction): void
    {
        $method = new ReflectionMethod(InventoryService::class, 'postInventoryAdjustmentJournal');
        $method->setAccessible(true);
        $method->invoke($this->inventoryService, $transaction->fresh(), null);
    }

    public function test_positive_adjustment_posts_balanced_journal_debit_inventory_credit_counter(): void
    {
        ['item' => $item, 'warehouse' => $warehouse, 'inventoryAccountId' => $inventoryAccountId, 'counterAccountId' => $counterAccountId] = $this->createItemWithAccounts();

        $transaction = $this->inventoryService->processAdjustmentTransaction(
            (int) $item->id,
            5,
            1000.0,
            'Increase test',
            $warehouse->id,
            true
        );

        $this->assertSame(1, $this->journalCountForTransaction($transaction->id));

        $journalId = (int) DB::table('journals')
            ->where('source_type', 'inventory_adjustment')
            ->where('source_id', $transaction->id)
            ->value('id');

        $lines = DB::table('journal_lines')->where('journal_id', $journalId)->get();
        $this->assertCount(2, $lines);

        $debitInventory = $lines->firstWhere('account_id', $inventoryAccountId);
        $creditCounter = $lines->firstWhere('account_id', $counterAccountId);

        $this->assertNotNull($debitInventory);
        $this->assertNotNull($creditCounter);
        $this->assertEqualsWithDelta(5000.0, (float) $debitInventory->debit, 0.01);
        $this->assertEqualsWithDelta(0.0, (float) $debitInventory->credit, 0.01);
        $this->assertEqualsWithDelta(5000.0, (float) $creditCounter->credit, 0.01);
        $this->assertEqualsWithDelta(0.0, (float) $creditCounter->debit, 0.01);
    }

    public function test_negative_adjustment_posts_debit_counter_credit_inventory(): void
    {
        ['item' => $item, 'warehouse' => $warehouse, 'inventoryAccountId' => $inventoryAccountId, 'counterAccountId' => $counterAccountId] = $this->createItemWithAccounts();

        $this->inventoryService->processPurchaseTransaction(
            (int) $item->id,
            10,
            800.0,
            'purchase_invoice',
            1,
            'Stock for decrease test',
            $warehouse->id
        );

        $transaction = $this->inventoryService->processAdjustmentTransaction(
            (int) $item->id,
            -3,
            800.0,
            'Decrease test',
            $warehouse->id,
            true
        );

        $journalId = (int) DB::table('journals')
            ->where('source_type', 'inventory_adjustment')
            ->where('source_id', $transaction->id)
            ->value('id');

        $lines = DB::table('journal_lines')->where('journal_id', $journalId)->get();
        $this->assertCount(2, $lines);

        $debitCounter = $lines->firstWhere('account_id', $counterAccountId);
        $creditInventory = $lines->firstWhere('account_id', $inventoryAccountId);

        $this->assertNotNull($debitCounter);
        $this->assertNotNull($creditInventory);
        $this->assertEqualsWithDelta(2400.0, (float) $debitCounter->debit, 0.01);
        $this->assertEqualsWithDelta(2400.0, (float) $creditInventory->credit, 0.01);
    }

    public function test_zero_cost_adjustment_does_not_create_journal(): void
    {
        ['item' => $item, 'warehouse' => $warehouse] = $this->createItemWithAccounts();

        $transaction = $this->inventoryService->processAdjustmentTransaction(
            (int) $item->id,
            2,
            0.0,
            'Zero cost',
            $warehouse->id,
            true
        );

        $this->assertSame(0, $this->journalCountForTransaction($transaction->id));
        $this->assertSame(0, (int) DB::table('journals')->where('source_type', 'inventory_adjustment')->count());
    }

    public function test_adjustment_without_post_journal_flag_does_not_create_journal(): void
    {
        ['item' => $item, 'warehouse' => $warehouse] = $this->createItemWithAccounts();

        $transaction = $this->inventoryService->processAdjustmentTransaction(
            (int) $item->id,
            4,
            500.0,
            'No journal',
            $warehouse->id
        );

        $this->assertSame(0, $this->journalCountForTransaction($transaction->id));
        $this->assertInstanceOf(InventoryTransaction::class, $transaction);
    }

    public function test_reposting_for_same_transaction_does_not_duplicate_journal(): void
    {
        ['item' => $item, 'warehouse' => $warehouse] = $this->createItemWithAccounts();

        $transaction = $this->inventoryService->processAdjustmentTransaction(
            (int) $item->id,
            1,
            100.0,
            'Idempotent',
            $warehouse->id,
            true
        );

        $this->assertSame(1, $this->journalCountForTransaction($transaction->id));

        $this->invokePostInventoryAdjustmentJournalAgain($transaction);

        $this->assertSame(1, $this->journalCountForTransaction($transaction->id));
        $this->assertSame(1, (int) DB::table('journals')->where('source_type', 'inventory_adjustment')->count());
    }
}
