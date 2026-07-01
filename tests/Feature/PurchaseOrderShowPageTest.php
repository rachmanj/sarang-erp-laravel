<?php

namespace Tests\Feature;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PurchaseOrderShowPageTest extends TestCase
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

    public function test_draft_purchase_order_show_page_includes_edit_link(): void
    {
        $currencyId = (int) DB::table('currencies')->value('id');
        $warehouseId = (int) Warehouse::query()->value('id');
        $vendorId = (int) DB::table('business_partners')->where('partner_type', 'supplier')->orderBy('id')->value('id');
        $entityId = (int) DB::table('company_entities')->where('code', '71')->value('id');

        $po = PurchaseOrder::query()->create([
            'order_no' => 'T-SHOW-EDIT-'.uniqid(),
            'date' => now()->toDateString(),
            'business_partner_id' => $vendorId,
            'company_entity_id' => $entityId,
            'warehouse_id' => $warehouseId,
            'currency_id' => $currencyId,
            'order_type' => 'item',
            'status' => 'draft',
            'approval_status' => 'pending',
            'total_amount' => 0,
        ]);

        $response = $this->get(route('purchase-orders.show', $po->id));

        $response->assertOk();
        $response->assertSee(route('purchase-orders.edit', $po->id), false);
    }

    public function test_non_draft_purchase_order_show_page_omits_edit_link(): void
    {
        $currencyId = (int) DB::table('currencies')->value('id');
        $warehouseId = (int) Warehouse::query()->value('id');
        $vendorId = (int) DB::table('business_partners')->where('partner_type', 'supplier')->orderBy('id')->value('id');
        $entityId = (int) DB::table('company_entities')->where('code', '71')->value('id');

        $po = PurchaseOrder::query()->create([
            'order_no' => 'T-SHOW-NOEDIT-'.uniqid(),
            'date' => now()->toDateString(),
            'business_partner_id' => $vendorId,
            'company_entity_id' => $entityId,
            'warehouse_id' => $warehouseId,
            'currency_id' => $currencyId,
            'order_type' => 'item',
            'status' => 'ordered',
            'approval_status' => 'approved',
            'total_amount' => 0,
        ]);

        $response = $this->get(route('purchase-orders.show', $po->id));

        $response->assertOk();
        $response->assertDontSee(route('purchase-orders.edit', $po->id));
    }

    public function test_purchase_order_show_page_displays_tax_summary_footer(): void
    {
        $currencyId = (int) DB::table('currencies')->value('id');
        $warehouseId = (int) Warehouse::query()->value('id');
        $vendorId = (int) DB::table('business_partners')->where('partner_type', 'supplier')->orderBy('id')->value('id');
        $entityId = (int) DB::table('company_entities')->where('code', '71')->value('id');
        $accountId = (int) DB::table('accounts')->orderBy('id')->value('id');

        $po = PurchaseOrder::query()->create([
            'order_no' => 'T-SHOW-FOOTER-'.uniqid(),
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

        $response = $this->get(route('purchase-orders.show', $po->id));

        $response->assertOk();
        $response->assertSee('>DPP<', false);
        $response->assertSee('Total Amount (DPP)', false);
        $response->assertSee('Total VAT', false);
        $response->assertSee('Total WTax', false);
        $response->assertSee('Total Due', false);
        $response->assertSee('32,736,096.00', false);
        $response->assertSee('36,337,066.56', false);
    }
}
