<?php

namespace Tests\Feature;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderApproval;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PurchaseOrderApprovalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function createMinimalPurchaseOrder(array $overrides = []): PurchaseOrder
    {
        $currencyId = (int) DB::table('currencies')->value('id');
        $warehouseId = (int) Warehouse::query()->value('id');
        $vendorId = (int) DB::table('business_partners')->where('partner_type', 'supplier')->orderBy('id')->value('id');
        $entityId = (int) DB::table('company_entities')->where('code', '71')->value('id');

        return PurchaseOrder::query()->create(array_merge([
            'order_no' => 'T-APR-WF-'.uniqid(),
            'date' => now()->toDateString(),
            'business_partner_id' => $vendorId,
            'company_entity_id' => $entityId,
            'warehouse_id' => $warehouseId,
            'currency_id' => $currencyId,
            'order_type' => 'item',
            'status' => 'draft',
            'approval_status' => 'pending',
            'total_amount' => 100,
        ], $overrides));
    }

    public function test_superadmin_cannot_finalize_while_other_approver_pending(): void
    {
        $this->seed();

        $superadmin = User::where('username', 'superadmin')->firstOrFail();

        $manager = User::factory()->create([
            'username' => 'manager-test-'.uniqid(),
            'email' => 'manager-test-'.uniqid().'@example.test',
        ]);
        $manager->assignRole('logistic');

        $po = $this->createMinimalPurchaseOrder();

        PurchaseOrderApproval::create([
            'purchase_order_id' => $po->id,
            'user_id' => $superadmin->id,
            'approval_level' => 'officer',
            'status' => 'approved',
            'approved_at' => now(),
        ]);
        PurchaseOrderApproval::create([
            'purchase_order_id' => $po->id,
            'user_id' => $manager->id,
            'approval_level' => 'manager',
            'status' => 'pending',
        ]);

        $this->actingAs($superadmin);

        $response = $this->post(route('purchase-orders.approve', $po->id));

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $po->refresh();
        $this->assertSame('draft', $po->status);
    }

    public function test_user_can_complete_pending_step_when_po_marked_ordered(): void
    {
        $this->seed();

        $superadmin = User::where('username', 'superadmin')->firstOrFail();

        $manager = User::factory()->create([
            'username' => 'ryoga-like-'.uniqid(),
            'email' => 'ryoga-like-'.uniqid().'@example.test',
        ]);
        $manager->assignRole('logistic');

        $po = $this->createMinimalPurchaseOrder([
            'status' => 'ordered',
            'approval_status' => 'approved',
            'approved_by' => $superadmin->id,
            'approved_at' => now(),
        ]);

        PurchaseOrderApproval::create([
            'purchase_order_id' => $po->id,
            'user_id' => $superadmin->id,
            'approval_level' => 'officer',
            'status' => 'approved',
            'approved_at' => now(),
        ]);
        PurchaseOrderApproval::create([
            'purchase_order_id' => $po->id,
            'user_id' => $manager->id,
            'approval_level' => 'manager',
            'status' => 'pending',
        ]);

        $this->actingAs($manager);

        $showResponse = $this->get(route('purchase-orders.show', $po->id));
        $showResponse->assertOk();
        $showResponse->assertSee(route('purchase-orders.approve', $po->id), false);

        $approveResponse = $this->post(route('purchase-orders.approve', $po->id));
        $approveResponse->assertRedirect();
        $approveResponse->assertSessionHas('success');

        $this->assertSame(
            0,
            PurchaseOrderApproval::where('purchase_order_id', $po->id)->where('status', 'pending')->count()
        );
    }
}
