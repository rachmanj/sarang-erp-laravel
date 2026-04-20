<?php

namespace Tests\Feature;

use App\Models\Accounting\SalesInvoice;
use App\Models\DeliveryOrder;
use App\Models\SalesOrder;
use App\Models\User;
use App\Services\DocumentRelationshipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DocumentRelationshipMapExpansionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * @return array{0: SalesOrder, 1: DeliveryOrder, 2: ?SalesInvoice}
     */
    private function createMinimalSalesChain(bool $withInvoice = false): array
    {
        $bpId = (int) DB::table('business_partners')->value('id');
        $entityId = (int) DB::table('company_entities')->value('id');
        $userId = (int) DB::table('users')->value('id');
        $currencyId = (int) DB::table('currencies')->value('id');
        $whId = (int) DB::table('warehouses')->value('id');

        $this->assertGreaterThan(0, $bpId);
        $this->assertGreaterThan(0, $entityId);

        $so = SalesOrder::query()->create([
            'order_no' => 'T-MAP-SO-'.uniqid(),
            'date' => now()->toDateString(),
            'business_partner_id' => $bpId,
            'company_entity_id' => $entityId,
            'currency_id' => $currencyId,
            'exchange_rate' => 1,
            'warehouse_id' => $whId,
            'status' => 'draft',
            'total_amount' => 0,
            'created_by' => $userId,
        ]);

        $do = DeliveryOrder::query()->create([
            'do_number' => 'T-MAP-DO-'.uniqid(),
            'sales_order_id' => $so->id,
            'business_partner_id' => $bpId,
            'company_entity_id' => $entityId,
            'warehouse_id' => $whId,
            'delivery_address' => 'Test address',
            'planned_delivery_date' => now()->toDateString(),
            'status' => 'completed',
            'approval_status' => 'approved',
            'created_by' => $userId,
        ]);

        $si = null;
        if ($withInvoice) {
            $si = SalesInvoice::query()->create([
                'invoice_no' => 'T-MAP-SI-'.uniqid(),
                'date' => now()->toDateString(),
                'business_partner_id' => $bpId,
                'company_entity_id' => $entityId,
                'currency_id' => $currencyId,
                'exchange_rate' => 1,
                'total_amount' => 100,
                'status' => 'draft',
                'sales_order_id' => $so->id,
                'created_by' => $userId,
            ]);
            $si->deliveryOrders()->sync([$do->id]);
        }

        DB::table('document_relationships')
            ->where('target_document_type', $do->getMorphClass())
            ->where('target_document_id', $do->id)
            ->where('source_document_type', $so->getMorphClass())
            ->delete();

        return [$so, $do, $si];
    }

    private function grantMapPermissions(User $user): void
    {
        foreach ([
            'sales-orders.view',
            'ar.invoices.view',
            'ar.receipts.view',
            'ar.credit-memos.view',
            'ar.quotations.view',
            'purchase-orders.view',
        ] as $perm) {
            Permission::query()->firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
            $user->givePermissionTo($perm);
        }
    }

    public function test_expanded_graph_includes_sales_order_via_delivery_order_fk_when_relationship_row_missing(): void
    {
        [, $do] = $this->createMinimalSalesChain(false);

        $user = User::query()->first();
        $this->assertNotNull($user);
        $this->grantMapPermissions($user);

        /** @var DocumentRelationshipService $service */
        $service = app(DocumentRelationshipService::class);
        $packed = $service->expandSalesRelationshipMapGraph($do, $user);

        $classes = collect($packed['models'])->map(fn ($m) => $m::class)->unique()->values()->all();
        $this->assertContains(SalesOrder::class, $classes, 'SO should be linked via sales_order_id FK enrichment.');
    }

    public function test_expanded_graph_api_returns_multiple_nodes_when_invoice_linked(): void
    {
        [, $do] = $this->createMinimalSalesChain(true);

        $user = User::query()->first();
        $this->assertNotNull($user);
        $this->grantMapPermissions($user);
        $this->actingAs($user);

        $response = $this->getJson('/api/documents/delivery-orders/'.$do->id.'/relationship-map');
        $response->assertOk();
        $response->assertJsonPath('success', true);
        $types = collect($response->json('mermaid.nodes'))->pluck('type')->unique()->sort()->values()->all();
        $this->assertContains('Delivery Order', $types);
        $this->assertContains('Sales Invoice', $types);
        $this->assertContains('Sales Order', $types);
    }

    public function test_legacy_map_query_returns_ok(): void
    {
        [, $do] = $this->createMinimalSalesChain(false);

        $user = User::query()->first();
        $this->actingAs($user);

        $response = $this->getJson('/api/documents/delivery-orders/'.$do->id.'/relationship-map?legacy_map=1');
        $response->assertOk();
        $response->assertJsonPath('success', true);
    }
}
