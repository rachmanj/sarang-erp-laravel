<?php

namespace Tests\Feature;

use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderLine;
use App\Models\InventoryItem;
use App\Models\ProductCategory;
use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use App\Models\User;
use App\Models\Warehouse;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DeliveryOrderPrintItemCodeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $user = User::factory()->create();
        $user->givePermissionTo('sales-orders.view');
        $this->actingAs($user);
    }

    /**
     * @return array{deliveryOrder: DeliveryOrder, itemCode: string}
     */
    private function createDeliveryOrderWithLine(): array
    {
        $bpId = (int) DB::table('business_partners')->value('id');
        $entityId = (int) DB::table('company_entities')->value('id');
        $userId = (int) DB::table('users')->value('id');
        $currencyId = (int) DB::table('currencies')->value('id');
        $warehouse = Warehouse::query()->firstOrFail();
        $category = ProductCategory::query()->firstOrFail();

        $item = InventoryItem::query()->create([
            'code' => 'DO-PRINT-'.uniqid(),
            'name' => 'Print Test Line',
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

        $so = SalesOrder::query()->create([
            'order_no' => 'T-SO-DO-PRINT-'.uniqid(),
            'date' => now()->toDateString(),
            'business_partner_id' => $bpId,
            'company_entity_id' => $entityId,
            'currency_id' => $currencyId,
            'exchange_rate' => 1,
            'warehouse_id' => $warehouse->id,
            'status' => 'processing',
            'approval_status' => 'approved',
            'total_amount' => 100000,
            'created_by' => $userId,
        ]);

        $revenueAccountId = (int) DB::table('accounts')->where('code', '4.1.1.01')->value('id');

        $soLine = SalesOrderLine::query()->create([
            'order_id' => $so->id,
            'account_id' => $revenueAccountId,
            'inventory_item_id' => $item->id,
            'item_code' => $item->code,
            'item_name' => $item->name,
            'qty' => 2,
            'delivered_qty' => 0,
            'pending_qty' => 2,
            'unit_price' => 10000,
            'amount' => 20000,
        ]);

        $deliveryOrder = DeliveryOrder::query()->create([
            'do_number' => 'T-DO-PRINT-'.uniqid(),
            'sales_order_id' => $so->id,
            'business_partner_id' => $bpId,
            'company_entity_id' => $entityId,
            'warehouse_id' => $warehouse->id,
            'delivery_address' => 'Test delivery address',
            'planned_delivery_date' => now()->toDateString(),
            'delivery_method' => 'courier',
            'status' => 'draft',
            'approval_status' => 'pending',
            'created_by' => $userId,
        ]);

        DeliveryOrderLine::query()->create([
            'delivery_order_id' => $deliveryOrder->id,
            'sales_order_line_id' => $soLine->id,
            'inventory_item_id' => $item->id,
            'item_code' => $item->code,
            'item_name' => $item->name,
            'ordered_qty' => 2,
            'picked_qty' => 0,
            'unit_price' => 10000,
            'amount' => 20000,
            'status' => 'pending',
        ]);

        $deliveryOrder->load([
            'customer.primaryContact',
            'businessPartnerProject',
            'salesOrder',
            'warehouse',
            'companyEntity',
            'lines.inventoryItem.baseUnit.unit',
            'lines.partNumber',
            'lines.salesOrderLine.orderUnit',
            'lines.account',
            'createdBy',
        ]);

        return [
            'deliveryOrder' => $deliveryOrder,
            'itemCode' => $item->code,
        ];
    }

    /**
     * @return array{headerCols: int, bodyCols: int}
     */
    private function extractLineTableColumnCounts(string $html): array
    {
        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $targetTable = null;

        foreach ($xpath->query('//table') as $table) {
            if (! $table instanceof DOMElement) {
                continue;
            }

            if (str_contains($table->textContent, 'Part No.') && str_contains($table->textContent, 'Delivered Qty')) {
                $targetTable = $table;
                break;
            }
        }

        $this->assertNotNull($targetTable, 'Line items table not found in rendered HTML.');

        $headerCols = $xpath->query('.//thead/tr[1]/th', $targetTable)->length;
        $bodyCols = $xpath->query('.//tbody/tr[1]/td', $targetTable)->length;

        return [
            'headerCols' => $headerCols,
            'bodyCols' => $bodyCols,
        ];
    }

    private function assertItemCodeHeaderPresent(string $html, bool $expected): void
    {
        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $headers = $xpath->query('//table//thead//th');

        $hasItemCodeHeader = false;
        foreach ($headers as $header) {
            if (trim($header->textContent) === 'Item Code') {
                $hasItemCodeHeader = true;
                break;
            }
        }

        if ($expected) {
            $this->assertTrue($hasItemCodeHeader, 'Expected Item Code header to be present.');
        } else {
            $this->assertFalse($hasItemCodeHeader, 'Expected Item Code header to be hidden.');
        }
    }

    private function assertItemCodeInBody(string $html, string $itemCode, bool $expected): void
    {
        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $bodyRows = $xpath->query('//table//tbody/tr');

        $foundInBody = false;
        foreach ($bodyRows as $row) {
            if (! $row instanceof DOMElement) {
                continue;
            }

            foreach ($xpath->query('./td', $row) as $cell) {
                if (trim($cell->textContent) === $itemCode) {
                    $foundInBody = true;
                    break 2;
                }
            }
        }

        if ($expected) {
            $this->assertTrue($foundInBody, 'Expected item code to appear in line row.');
        } else {
            $this->assertFalse($foundInBody, 'Expected item code to be hidden from line row.');
        }
    }

    /**
     * @param  array<string, string>  $layouts
     */
    private function assertPrintRouteItemCodeVisibility(array $layouts, bool $showItemCode): void
    {
        ['deliveryOrder' => $deliveryOrder, 'itemCode' => $itemCode] = $this->createDeliveryOrderWithLine();

        foreach ($layouts as $layout => $viewName) {
            $query = $layout === 'standard' ? [] : ['layout' => $layout];
            if (! $showItemCode) {
                $query['show_item_code'] = 0;
            }

            $response = $this->get(route('delivery-orders.print', ['deliveryOrder' => $deliveryOrder->id] + $query));
            $response->assertOk();

            $html = $response->getContent();
            $this->assertItemCodeHeaderPresent($html, $showItemCode);
            $this->assertItemCodeInBody($html, $itemCode, $showItemCode);

            $counts = $this->extractLineTableColumnCounts($html);
            $expectedCols = $showItemCode ? 7 : 6;
            $this->assertSame($expectedCols, $counts['headerCols'], "Unexpected header column count for layout {$layout}.");
            $this->assertSame($counts['headerCols'], $counts['bodyCols'], "Header/body column mismatch for layout {$layout}.");
        }
    }

    public function test_a4_print_views_show_item_code_by_default(): void
    {
        ['deliveryOrder' => $deliveryOrder, 'itemCode' => $itemCode] = $this->createDeliveryOrderWithLine();

        foreach (['delivery_orders.print', 'delivery_orders.print_cv_saranghae'] as $viewName) {
            $html = view($viewName, [
                'deliveryOrder' => $deliveryOrder,
                'entity' => null,
            ])->render();

            $this->assertItemCodeHeaderPresent($html, true);
            $this->assertItemCodeInBody($html, $itemCode, true);

            $counts = $this->extractLineTableColumnCounts($html);
            $this->assertSame(7, $counts['headerCols'], "{$viewName} should render 7 columns with Item Code.");
            $this->assertSame($counts['headerCols'], $counts['bodyCols']);
        }
    }

    public function test_a4_print_views_hide_item_code_when_flag_is_off(): void
    {
        ['deliveryOrder' => $deliveryOrder, 'itemCode' => $itemCode] = $this->createDeliveryOrderWithLine();

        foreach (['delivery_orders.print', 'delivery_orders.print_cv_saranghae'] as $viewName) {
            $html = view($viewName, [
                'deliveryOrder' => $deliveryOrder,
                'entity' => null,
                'showItemCode' => false,
            ])->render();

            $this->assertItemCodeHeaderPresent($html, false);
            $this->assertItemCodeInBody($html, $itemCode, false);

            $counts = $this->extractLineTableColumnCounts($html);
            $this->assertSame(6, $counts['headerCols'], "{$viewName} should render 6 columns without Item Code.");
            $this->assertSame($counts['headerCols'], $counts['bodyCols']);
        }
    }

    public function test_print_route_passes_show_item_code_query_param(): void
    {
        $layouts = [
            'standard' => 'delivery_orders.print',
            'cv_saranghae' => 'delivery_orders.print_cv_saranghae',
        ];

        $this->assertPrintRouteItemCodeVisibility($layouts, true);
        $this->assertPrintRouteItemCodeVisibility($layouts, false);
    }

    public function test_dotmatrix_layouts_are_unchanged_by_show_item_code_param(): void
    {
        ['deliveryOrder' => $deliveryOrder] = $this->createDeliveryOrderWithLine();

        foreach (['dotmatrix', 'cv_saranghae_dotmatrix'] as $layout) {
            foreach ([true, false] as $showItemCode) {
                $query = ['layout' => $layout];
                if (! $showItemCode) {
                    $query['show_item_code'] = 0;
                }

                $response = $this->get(route('delivery-orders.print', ['deliveryOrder' => $deliveryOrder->id] + $query));
                $response->assertOk();

                $html = $response->getContent();
                $this->assertItemCodeHeaderPresent($html, false);
                $this->assertStringContainsString('Part#', $html);
            }
        }
    }
}
