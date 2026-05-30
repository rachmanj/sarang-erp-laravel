<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesInvoiceListTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed();
        $user = User::factory()->create();
        $user->givePermissionTo('ar.invoices.view');
        $this->actingAs($user);
    }

    public function test_sales_invoice_data_includes_sum_total_amount(): void
    {
        $response = $this->getJson('/sales-invoices/data?'.http_build_query([
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'order' => [['column' => 0, 'dir' => 'desc']],
            'columns' => [
                ['data' => 'date', 'name' => 'si.date', 'searchable' => true, 'orderable' => true],
                ['data' => 'invoice_no', 'name' => 'si.invoice_no', 'searchable' => true, 'orderable' => true],
                ['data' => 'customer', 'name' => 'c.name', 'searchable' => true, 'orderable' => false],
                ['data' => 'reference_no', 'name' => 'si.reference_no', 'searchable' => true, 'orderable' => true],
                ['data' => 'total_amount', 'name' => 'si.total_amount', 'searchable' => false, 'orderable' => false],
                ['data' => 'status', 'name' => 'si.status', 'searchable' => true, 'orderable' => true],
                ['data' => 'actions', 'name' => 'actions', 'searchable' => false, 'orderable' => false],
            ],
        ]));

        $response->assertOk();
        $response->assertJsonStructure([
            'data',
            'recordsTotal',
            'recordsFiltered',
            'sum_total_amount',
        ]);
    }

    public function test_sales_invoice_export_returns_excel_file(): void
    {
        $response = $this->get('/sales-invoices/export');

        $response->assertOk();
        $this->assertStringContainsString(
            'spreadsheet',
            strtolower((string) $response->headers->get('Content-Type'))
        );
    }
}
