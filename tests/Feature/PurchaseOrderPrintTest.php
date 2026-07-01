<?php

namespace Tests\Feature;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PurchaseOrderPrintTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $user = User::factory()->create();
        $user->givePermissionTo('purchase-orders.view');
        $this->actingAs($user);
    }

    public function test_pt_csj_dotmatrix_print_shows_line_dpp_and_tax_summary(): void
    {
        $po = $this->createPurchaseOrderWithLine();

        $response = $this->get(route('purchase-orders.print', [
            'id' => $po->id,
            'layout' => 'pt_csj_dotmatrix',
        ]));

        $response->assertOk();
        $response->assertSee('Total Amount (DPP)', false);
        $response->assertSee('Total VAT', false);
        $response->assertSee('Total WTax', false);
        $response->assertSee('Total Due', false);
        $response->assertSee('32.736.096,00', false);
        $response->assertSee('36.337.066,56', false);
        $response->assertDontSee('>DPP</th>', false);
    }

    public function test_pt_csj_a4_print_shows_line_dpp_and_tax_summary(): void
    {
        $po = $this->createPurchaseOrderWithLine();

        $response = $this->get(route('purchase-orders.print', [
            'id' => $po->id,
            'layout' => 'pt_csj',
        ]));

        $response->assertOk();
        $response->assertSee('Total Amount (DPP)', false);
        $response->assertSee('32.736.096,00', false);
        $response->assertSee('36.337.066,56', false);
        $response->assertDontSee('>DPP</th>', false);
    }

    private function createPurchaseOrderWithLine(): PurchaseOrder
    {
        $currencyId = (int) DB::table('currencies')->value('id');
        $warehouseId = (int) Warehouse::query()->value('id');
        $vendorId = (int) DB::table('business_partners')->where('partner_type', 'supplier')->orderBy('id')->value('id');
        $entityId = (int) DB::table('company_entities')->where('code', '71')->value('id');
        $accountId = (int) DB::table('accounts')->orderBy('id')->value('id');

        $po = PurchaseOrder::query()->create([
            'order_no' => 'T-PRINT-'.uniqid(),
            'date' => now()->toDateString(),
            'business_partner_id' => $vendorId,
            'company_entity_id' => $entityId,
            'warehouse_id' => $warehouseId,
            'currency_id' => $currencyId,
            'order_type' => 'item',
            'status' => 'ordered',
            'approval_status' => 'approved',
            'total_amount' => 36337066.56,
        ]);

        PurchaseOrderLine::query()->create([
            'order_id' => $po->id,
            'account_id' => $accountId,
            'qty' => 16,
            'unit_price' => 2046006,
            'net_amount' => 32736096,
            'vat_rate' => 11,
            'wtax_rate' => 0,
            'amount' => 36337066.56,
            'status' => 'pending',
        ]);

        return $po;
    }
}
