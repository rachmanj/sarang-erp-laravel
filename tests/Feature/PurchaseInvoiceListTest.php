<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseInvoiceListTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed();
        $user = User::factory()->create();
        $user->givePermissionTo('ap.invoices.view');
        $this->actingAs($user);
    }

    public function test_purchase_invoice_data_includes_sum_total_and_sum_amount_after_vat(): void
    {
        $response = $this->getJson('/purchase-invoices/data?'.http_build_query([
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'order' => [['column' => 0, 'dir' => 'desc']],
            'columns' => [
                ['data' => 'date', 'name' => 'pi.date', 'searchable' => true, 'orderable' => true],
                ['data' => 'invoice_no', 'name' => 'pi.invoice_no', 'searchable' => true, 'orderable' => true],
                ['data' => 'vendor', 'name' => 'v.name', 'searchable' => true, 'orderable' => false],
                ['data' => 'total_amount', 'name' => 'pi.total_amount', 'searchable' => false, 'orderable' => false],
                ['data' => 'total_vat', 'name' => 'total_vat', 'searchable' => false, 'orderable' => false],
                ['data' => 'total_amount_after_vat', 'name' => 'total_amount_after_vat', 'searchable' => false, 'orderable' => false],
                ['data' => 'status', 'name' => 'pi.status', 'searchable' => true, 'orderable' => true],
                ['data' => 'actions', 'name' => 'actions', 'searchable' => false, 'orderable' => false],
            ],
        ]));

        $response->assertOk();
        $response->assertJsonStructure([
            'data',
            'recordsTotal',
            'recordsFiltered',
            'sum_total_amount',
            'sum_amount_after_vat',
        ]);
    }

    public function test_purchase_invoice_export_returns_excel_file(): void
    {
        $response = $this->get('/purchase-invoices/export');

        $response->assertOk();
        $this->assertStringContainsString(
            'spreadsheet',
            strtolower((string) $response->headers->get('Content-Type'))
        );
    }
}
