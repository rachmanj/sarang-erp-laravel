<?php

namespace Database\Seeders;

use App\Models\Accounting\Journal;
use App\Models\Accounting\PurchaseInvoice;
use App\Models\Accounting\SalesInvoice;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetDepreciationRun;
use App\Models\BusinessPartner;
use App\Models\ControlAccount;
use App\Models\ControlAccountBalance;
use App\Models\Currency;
use App\Models\DocumentRelationship;
use App\Models\Finance\Period;
use App\Models\GRGIHeader;
use App\Models\GRGIPurpose;
use App\Models\InventoryItem;
use App\Models\InventoryValuation;
use App\Models\InventoryWarehouseStock;
use App\Models\ProductCategory;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderApproval;
use App\Models\SalesOrder;
use App\Models\SalesOrderApproval;
use App\Models\SupplierPerformance;
use App\Models\TaxComplianceLog;
use App\Models\TaxPeriod;
use App\Models\TaxReport;
use App\Models\TaxSetting;
use App\Models\TaxTransaction;
use App\Models\User;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DashboardDemoSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $user = $this->ensureDemoUser();
            $currency = $this->ensureBaseCurrency();
            $warehouse = $this->ensureWarehouse();

            $suppliers = $this->seedBusinessPartners('supplier', [
                ['code' => 'DEMO-SUP-001', 'name' => 'PT Nusantara Supplies'],
                ['code' => 'DEMO-SUP-002', 'name' => 'PT Mitra Logistik'],
            ]);

            $customers = $this->seedBusinessPartners('customer', [
                ['code' => 'DEMO-CUS-001', 'name' => 'PT Global Retail'],
                ['code' => 'DEMO-CUS-002', 'name' => 'PT Prima Distribution'],
                ['code' => 'DEMO-CUS-003', 'name' => 'PT Ventura Digital'],
            ]);

            $items = $this->seedInventoryItems($currency, $warehouse);
            $this->seedInventorySnapshots($items, $warehouse);

            $this->seedControlAccountBalances();
            $this->seedPeriods();
            $this->seedUnpostedJournals($user);

            $salesOrders = $this->seedSalesOrders($customers, $currency, $warehouse, $user);
            $purchaseOrders = $this->seedPurchaseOrders($suppliers, $currency, $warehouse, $user);

            $this->seedApprovals($salesOrders['orders'], $purchaseOrders['orders'], $user);
            $this->seedDeliveryOrders($salesOrders['orders'], $customers, $warehouse, $user);

            $this->seedSalesInvoices($customers, $currency);
            $this->seedPurchaseInvoices($suppliers, $currency);

            $this->seedSupplierPerformance($suppliers);
            $this->seedGrgiDocuments($warehouse, $user);
            $this->seedAssets($suppliers, $user);

            $this->seedComplianceData($user);
            $this->seedDocumentRelationships($salesOrders['orders'], $purchaseOrders['orders']);
        });
    }

    private function ensureDemoUser(): User
    {
        return User::firstOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Admin', 'username' => 'superadmin', 'password' => bcrypt('password')]
        );
    }

    private function ensureBaseCurrency(): Currency
    {
        return Currency::firstOrCreate(
            ['code' => 'IDR'],
            ['name' => 'Indonesian Rupiah', 'symbol' => 'Rp', 'decimal_places' => 0, 'is_active' => true, 'is_base_currency' => true]
        );
    }

    private function ensureWarehouse(): Warehouse
    {
        return Warehouse::firstOrCreate(
            ['code' => 'WH001'],
            [
                'name' => 'Main Warehouse',
                'address' => 'Jl. Sudirman No. 123, Jakarta Pusat',
                'contact_person' => 'John Doe',
                'phone' => '021-1234567',
                'email' => 'warehouse@company.com',
                'is_active' => true,
            ]
        );
    }

    /**
     * @param array<int,array<string,string>> $partners
     * @return array<int,BusinessPartner>
     */
    private function seedBusinessPartners(string $type, array $partners): array
    {
        $collection = [];

        foreach ($partners as $partner) {
            $collection[] = BusinessPartner::updateOrCreate(
                ['code' => $partner['code']],
                [
                    'name' => $partner['name'],
                    'partner_type' => $type,
                    'status' => 'active',
                ]
            );
        }

        return $collection;
    }

    /**
     * Seed inventory items and return array with helper metadata.
     *
     * @return array<int,array<string,mixed>>
     */
    private function seedInventoryItems(Currency $currency, Warehouse $warehouse): array
    {
        $categoryLookup = ProductCategory::pluck('id', 'code')->toArray();

        $items = [
            [
                'code' => 'DEMO-EL-001',
                'name' => '4K Monitor 27 inch',
                'category_code' => 'ELECTRONICS',
                'purchase_price' => 4500000,
                'selling_price' => 5250000,
            ],
            [
                'code' => 'DEMO-ST-001',
                'name' => 'Premium Notebook A5',
                'category_code' => 'STATIONERY',
                'purchase_price' => 25000,
                'selling_price' => 39000,
            ],
            [
                'code' => 'DEMO-FU-001',
                'name' => 'Ergonomic Office Chair',
                'category_code' => 'FURNITURE',
                'purchase_price' => 980000,
                'selling_price' => 1350000,
            ],
        ];

        $results = [];

        foreach ($items as $itemData) {
            $categoryId = $categoryLookup[$itemData['category_code']] ?? null;

            $item = InventoryItem::updateOrCreate(
                ['code' => $itemData['code']],
                [
                    'name' => $itemData['name'],
                    'description' => $itemData['name'],
                    'category_id' => $categoryId,
                    'default_warehouse_id' => $warehouse->id,
                    'unit_of_measure' => 'PCS',
                    'purchase_currency_id' => $currency->id,
                    'selling_currency_id' => $currency->id,
                    'purchase_price' => $itemData['purchase_price'],
                    'selling_price' => $itemData['selling_price'],
                    'min_stock_level' => 10,
                    'max_stock_level' => 200,
                    'reorder_point' => 15,
                    'valuation_method' => 'fifo',
                    'item_type' => 'item',
                    'is_active' => true,
                ]
            );

            $results[] = [
                'model' => $item,
                'base_stock' => $itemData['code'] === 'DEMO-ST-001' ? 40 : 25,
            ];
        }

        return $results;
    }

    /**
     * @param array<int,array<string,mixed>> $items
     */
    private function seedInventorySnapshots(array $items, Warehouse $warehouse): void
    {
        $months = collect(range(0, 5))
            ->map(fn(int $offset) => Carbon::now()->startOfMonth()->subMonths($offset))
            ->sort();

        foreach ($items as $itemInfo) {
            /** @var \App\Models\InventoryItem $item */
            $item = $itemInfo['model'];
            $baseStock = (int) $itemInfo['base_stock'];

            foreach ($months as $monthDate) {
                $quantity = max(5, $baseStock - random_int(0, 8));
                $unitCost = round($item->purchase_price * (1 + random_int(-5, 5) / 100), 2);

                InventoryValuation::updateOrCreate(
                    [
                        'item_id' => $item->id,
                        'valuation_date' => $monthDate->copy()->endOfMonth()->toDateString(),
                    ],
                    [
                        'quantity_on_hand' => $quantity,
                        'unit_cost' => $unitCost,
                        'total_value' => $quantity * $unitCost,
                        'valuation_method' => $item->valuation_method,
                    ]
                );
            }

            InventoryWarehouseStock::updateOrCreate(
                [
                    'item_id' => $item->id,
                    'warehouse_id' => $warehouse->id,
                ],
                [
                    'quantity_on_hand' => $baseStock,
                    'reserved_quantity' => 5,
                    'available_quantity' => $baseStock - 5,
                    'min_stock_level' => 10,
                    'max_stock_level' => 200,
                    'reorder_point' => 15,
                ]
            );
        }

        // Intentionally force one low-stock record for dashboard alert
        InventoryWarehouseStock::whereHas('item', fn($query) => $query->where('code', 'DEMO-ST-001'))
            ->update([
                'quantity_on_hand' => 12,
                'reserved_quantity' => 2,
                'available_quantity' => 10,
                'reorder_point' => 15,
            ]);
    }

    private function seedControlAccountBalances(): void
    {
        $balances = [
            'ar' => 42500000,
            'ap' => 31250000,
            'inventory' => 78000000,
            'fixed_assets' => 152500000,
        ];

        foreach ($balances as $type => $value) {
            $controlAccount = ControlAccount::where('control_type', $type)->first();

            if (!$controlAccount) {
                continue;
            }

            ControlAccountBalance::updateOrCreate(
                [
                    'control_account_id' => $controlAccount->id,
                    'project_id' => null,
                    'dept_id' => null,
                ],
                [
                    'balance' => $value,
                    'reconciled_balance' => $value * 0.96,
                    'last_reconciled_at' => Carbon::now()->subDays(random_int(5, 15)),
                ]
            );
        }
    }

    private function seedPeriods(): void
    {
        $currentMonth = Carbon::now()->startOfMonth();

        foreach (range(0, 5) as $offset) {
            $month = $currentMonth->copy()->subMonths($offset);

            Period::updateOrCreate(
                [
                    'month' => (int) $month->format('m'),
                    'year' => (int) $month->format('Y'),
                ],
                [
                    'is_closed' => $offset >= 3,
                    'closed_at' => $offset >= 3 ? $month->copy()->addMonth()->startOfMonth() : null,
                ]
            );
        }
    }

    private function seedUnpostedJournals(User $user): void
    {
        foreach (range(1, 3) as $index) {
            $journalNo = sprintf('JNL-DEMO-%s%02d', Carbon::now()->format('Ym'), $index);

            Journal::updateOrCreate(
                ['journal_no' => $journalNo],
                [
                    'date' => Carbon::now()->subDays($index * 2),
                    'description' => 'Demo journal entry ' . $index,
                    'source_type' => 'manual',
                    'source_id' => 0,
                    'posted_by' => null,
                    'posted_at' => null,
                ]
            );
        }
    }

    /**
     * Seed sales orders and return reference arrays.
     *
     * @param array<int,BusinessPartner> $customers
     * @return array{orders:\Illuminate\Support\Collection}
     */
    private function seedSalesOrders(array $customers, Currency $currency, Warehouse $warehouse, User $user): array
    {
        $orders = collect();
        $statuses = [
            ['status' => 'draft', 'approval_status' => 'pending'],
            ['status' => 'approved', 'approval_status' => 'approved'],
            ['status' => 'closed', 'approval_status' => 'approved'],
        ];

        foreach ($statuses as $idx => $statusSet) {
            $customer = $customers[$idx % count($customers)];
            $orderNo = sprintf('SO-DEMO-%s%02d', Carbon::now()->format('Ym'), $idx + 1);
            $date = Carbon::now()->subDays(($idx + 1) * 7);

            $order = SalesOrder::updateOrCreate(
                ['order_no' => $orderNo],
                [
                    'business_partner_id' => $customer->id,
                    'currency_id' => $currency->id,
                    'exchange_rate' => 1,
                    'warehouse_id' => $warehouse->id,
                    'date' => $date,
                    'expected_delivery_date' => $date->copy()->addDays(10),
                    'total_amount' => 8500000 + ($idx * 1250000),
                    'total_amount_foreign' => 8500000 + ($idx * 1250000),
                    'freight_cost' => 125000,
                    'handling_cost' => 75000,
                    'insurance_cost' => 55000,
                    'total_cost' => 8500000 + ($idx * 1250000),
                    'order_type' => 'item',
                    'status' => $statusSet['status'],
                    'approval_status' => $statusSet['approval_status'],
                    'created_by' => $user->id,
                ]
            );

            $orders->push($order);
        }

        return ['orders' => $orders];
    }

    /**
     * @param array<int,BusinessPartner> $suppliers
     * @return array{orders:\Illuminate\Support\Collection}
     */
    private function seedPurchaseOrders(array $suppliers, Currency $currency, Warehouse $warehouse, User $user): array
    {
        $orders = collect();
        $statuses = [
            ['status' => 'draft', 'approval_status' => 'pending'],
            ['status' => 'approved', 'approval_status' => 'approved'],
            ['status' => 'closed', 'approval_status' => 'approved'],
        ];

        foreach ($statuses as $idx => $statusSet) {
            $supplier = $suppliers[$idx % count($suppliers)];
            $orderNo = sprintf('PO-DEMO-%s%02d', Carbon::now()->format('Ym'), $idx + 1);
            $date = Carbon::now()->subDays(($idx + 1) * 9);

            $order = PurchaseOrder::updateOrCreate(
                ['order_no' => $orderNo],
                [
                    'business_partner_id' => $supplier->id,
                    'currency_id' => $currency->id,
                    'exchange_rate' => 1,
                    'warehouse_id' => $warehouse->id,
                    'date' => $date,
                    'expected_delivery_date' => $date->copy()->addDays(14),
                    'total_amount' => 6400000 + ($idx * 950000),
                    'total_amount_foreign' => 6400000 + ($idx * 950000),
                    'freight_cost' => 95000,
                    'freight_cost_foreign' => 95000,
                    'handling_cost' => 65000,
                    'handling_cost_foreign' => 65000,
                    'insurance_cost' => 45000,
                    'insurance_cost_foreign' => 45000,
                    'total_cost' => 6400000 + ($idx * 950000),
                    'total_cost_foreign' => 6400000 + ($idx * 950000),
                    'order_type' => 'item',
                    'status' => $statusSet['status'],
                    'approval_status' => $statusSet['approval_status'],
                    'closure_status' => $statusSet['status'] === 'closed' ? 'closed' : 'open',
                    'created_by' => $user->id,
                ]
            );

            $orders->push($order);
        }

        return ['orders' => $orders];
    }

    private function seedApprovals($salesOrders, $purchaseOrders, User $user): void
    {
        foreach ($purchaseOrders as $index => $purchaseOrder) {
            PurchaseOrderApproval::updateOrCreate(
                [
                    'purchase_order_id' => $purchaseOrder->id,
                    'user_id' => $user->id,
                ],
                [
                    'approval_level' => 'manager',
                    'status' => $purchaseOrder->approval_status === 'approved' ? 'approved' : 'pending',
                    'approved_at' => $purchaseOrder->approval_status === 'approved'
                        ? $purchaseOrder->date->copy()->addDays(1)
                        : null,
                ]
            );
        }

        foreach ($salesOrders as $index => $salesOrder) {
            SalesOrderApproval::updateOrCreate(
                [
                    'sales_order_id' => $salesOrder->id,
                    'user_id' => $user->id,
                ],
                [
                    'approval_level' => 'sales_manager',
                    'status' => $salesOrder->approval_status === 'approved' ? 'approved' : 'pending',
                    'approved_at' => $salesOrder->approval_status === 'approved'
                        ? $salesOrder->date->copy()->addDays(1)
                        : null,
                ]
            );
        }
    }

    private function seedDeliveryOrders($salesOrders, array $customers, Warehouse $warehouse, User $user): void
    {
        $pendingOrder = $salesOrders->firstWhere('status', 'approved') ?? $salesOrders->first();
        $completedOrder = $salesOrders->firstWhere('status', 'closed') ?? $salesOrders->last();

        if ($pendingOrder) {
            $customer = $customers[0];
            \App\Models\DeliveryOrder::updateOrCreate(
                ['do_number' => 'DO-DEMO-001'],
                [
                    'sales_order_id' => $pendingOrder->id,
                    'business_partner_id' => $customer->id,
                    'warehouse_id' => $warehouse->id,
                    'delivery_address' => 'Gedung Menara Prima, Jakarta Selatan',
                    'delivery_contact_person' => 'Andi Setiawan',
                    'delivery_phone' => '021-5556677',
                    'planned_delivery_date' => Carbon::now()->addDays(3),
                    'delivery_method' => 'own_fleet',
                    'delivery_instructions' => 'Deliver to loading dock between 09:00-11:00',
                    'logistics_cost' => 150000,
                    'status' => 'picking',
                    'created_by' => $user->id,
                    'approval_status' => 'pending',
                ]
            );
        }

        if ($completedOrder) {
            $customer = $customers[1] ?? $customers[0];
            \App\Models\DeliveryOrder::updateOrCreate(
                ['do_number' => 'DO-DEMO-002'],
                [
                    'sales_order_id' => $completedOrder->id,
                    'business_partner_id' => $customer->id,
                    'warehouse_id' => $warehouse->id,
                    'delivery_address' => 'Kawasan Industri MM2100, Bekasi',
                    'delivery_contact_person' => 'Sari Wulandari',
                    'delivery_phone' => '021-6677889',
                    'planned_delivery_date' => Carbon::now()->subDays(10),
                    'actual_delivery_date' => Carbon::now()->subDays(7),
                    'delivery_method' => 'courier',
                    'logistics_cost' => 185000,
                    'status' => 'delivered',
                    'approval_status' => 'approved',
                    'approved_by' => $user->id,
                    'approved_at' => Carbon::now()->subDays(8),
                    'created_by' => $user->id,
                    'closure_status' => 'closed',
                ]
            );
        }
    }

    /**
     * @param array<int,BusinessPartner> $customers
     */
    private function seedSalesInvoices(array $customers, Currency $currency): void
    {
        $months = collect(range(0, 5))
            ->map(fn(int $offset) => Carbon::now()->startOfMonth()->subMonths($offset));

        foreach ($months as $index => $monthDate) {
            foreach ($customers as $customerIndex => $customer) {
                $invoiceNo = sprintf('SINV-DEMO-%s-%02d', $monthDate->format('Ym'), $customerIndex + 1);
                $invoiceDate = $monthDate->copy()->addDays(5 + (3 * $customerIndex));
                $amount = 5500000 + ($customerIndex * 1750000) + ($index * 350000);
                $isClosed = $index >= 3;

                SalesInvoice::updateOrCreate(
                    ['invoice_no' => $invoiceNo],
                    [
                        'business_partner_id' => $customer->id,
                        'currency_id' => $currency->id,
                        'exchange_rate' => 1,
                        'date' => $invoiceDate,
                        'due_date' => $invoiceDate->clone()->addDays(30),
                        'description' => 'Demo sales invoice for ' . $customer->name,
                        'total_amount' => $amount,
                        'total_amount_foreign' => $amount,
                        'status' => 'posted',
                        'closure_status' => $isClosed ? 'closed' : 'open',
                        'posted_at' => $invoiceDate->clone()->addDays(1),
                    ]
                );
            }
        }
    }

    /**
     * @param array<int,BusinessPartner> $suppliers
     */
    private function seedPurchaseInvoices(array $suppliers, Currency $currency): void
    {
        $months = collect(range(0, 5))
            ->map(fn(int $offset) => Carbon::now()->startOfMonth()->subMonths($offset));

        foreach ($months as $index => $monthDate) {
            foreach ($suppliers as $supplierIndex => $supplier) {
                $invoiceNo = sprintf('PINV-DEMO-%s-%02d', $monthDate->format('Ym'), $supplierIndex + 1);
                $invoiceDate = $monthDate->copy()->addDays(8 + (4 * $supplierIndex));
                $amount = 4200000 + ($supplierIndex * 1350000) + ($index * 250000);
                $isClosed = $index >= 4;

                PurchaseInvoice::updateOrCreate(
                    ['invoice_no' => $invoiceNo],
                    [
                        'business_partner_id' => $supplier->id,
                        'currency_id' => $currency->id,
                        'exchange_rate' => 1,
                        'date' => $invoiceDate,
                        'due_date' => $invoiceDate->clone()->addDays(30),
                        'description' => 'Demo purchase invoice from ' . $supplier->name,
                        'total_amount' => $amount,
                        'total_amount_foreign' => $amount,
                        'status' => 'posted',
                        'closure_status' => $isClosed ? 'closed' : 'open',
                        'posted_at' => $invoiceDate->clone()->addDays(1),
                    ]
                );
            }
        }
    }

    private function seedSupplierPerformance(array $suppliers): void
    {
        foreach ($suppliers as $idx => $supplier) {
            DB::table('supplier_performance')->updateOrInsert(
                [
                    'business_partner_id' => $supplier->id,
                    'year' => (int) Carbon::now()->format('Y'),
                    'month' => Carbon::now()->format('n'),
                ],
                [
                    'total_orders' => 8 - $idx,
                    'total_amount' => 22000000 + ($idx * 4500000),
                    'avg_delivery_days' => 3 + $idx,
                    'quality_rating' => 4.4 - ($idx * 0.2),
                    'price_rating' => 4.1 - ($idx * 0.15),
                    'service_rating' => 4.6 - ($idx * 0.1),
                    'overall_rating' => 4.37 - ($idx * 0.15),
                    'notes' => 'Dashboard demo supplier performance',
                    'updated_at' => now(),
                ]
            );
        }
    }

    private function seedGrgiDocuments(Warehouse $warehouse, User $user): void
    {
        $purpose = GRGIPurpose::first();
        if (!$purpose) {
            $purpose = GRGIPurpose::create([
                'code' => 'DEMO-PURPOSE',
                'name' => 'Demo Purpose',
                'type' => 'goods_receipt',
                'description' => 'Autogenerated for dashboard demo seeding',
                'is_active' => true,
            ]);
        }

        GRGIHeader::updateOrCreate(
            ['document_number' => 'GRGI-DEMO-0001'],
            [
                'document_type' => 'goods_receipt',
                'purpose_id' => $purpose->id,
                'warehouse_id' => $warehouse->id,
                'transaction_date' => Carbon::now()->subDays(4),
                'reference_number' => 'REF-GRGI-001',
                'notes' => 'Dashboard demo GR/ GI pending approval',
                'total_amount' => 1850000,
                'status' => 'pending_approval',
                'created_by' => $user->id,
            ]
        );
    }

    private function seedAssets(array $suppliers, User $user): void
    {
        $category = AssetCategory::first();

        if (!$category) {
            return;
        }

        $supplier = $suppliers[0] ?? null;
        $baseDate = Carbon::now()->subMonths(18);

        $asset = Asset::updateOrCreate(
            ['code' => 'DEMO-FA-001'],
            [
                'name' => 'Warehouse Forklift',
                'category_id' => $category->id,
                'description' => 'Forklift used for warehouse operations',
                'acquisition_cost' => 285000000,
                'salvage_value' => 15000000,
                'current_book_value' => 214500000,
                'accumulated_depreciation' => 70500000,
                'method' => 'straight_line',
                'life_months' => 60,
                'placed_in_service_date' => $baseDate->copy()->toDateString(),
                'status' => 'active',
                'business_partner_id' => $supplier?->id,
            ]
        );

        AssetDepreciationRun::updateOrCreate(
            ['period' => Carbon::now()->format('Y-m')],
            [
                'status' => 'draft',
                'total_depreciation' => 4750000,
                'asset_count' => 1,
                'created_by' => $user->id,
                'notes' => 'Demo depreciation run awaiting posting',
            ]
        );
    }

    private function seedComplianceData(User $user): void
    {
        // Tax periods & reports
        $taxPeriod = TaxPeriod::updateOrCreate(
            [
                'year' => (int) Carbon::now()->format('Y'),
                'month' => (int) Carbon::now()->format('m'),
                'period_type' => 'monthly',
            ],
            [
                'start_date' => Carbon::now()->startOfMonth(),
                'end_date' => Carbon::now()->endOfMonth(),
                'status' => 'open',
            ]
        );

        TaxReport::updateOrCreate(
            ['tax_period_id' => $taxPeriod->id, 'report_type' => 'spt_ppn'],
            [
                'report_name' => 'PPN Monthly Filing',
                'status' => 'draft',
                'due_date' => Carbon::now()->copy()->addDays(10),
                'created_by' => $user->id,
                'report_data' => ['total_ppn_output' => 12500000, 'total_ppn_input' => 8300000],
            ]
        );

        TaxComplianceLog::create([
            'action' => 'created',
            'entity_type' => 'tax_report',
            'entity_id' => $taxPeriod->id,
            'description' => 'Demo tax report generated for dashboard sample',
            'user_id' => $user->id,
        ]);

        DB::table('tax_calendar')->updateOrInsert(
            ['event_name' => 'PPN Filing Deadline'],
            [
                'event_type' => 'deadline',
                'event_date' => Carbon::now()->addDays(12)->toDateString(),
                'tax_type' => 'ppn',
                'description' => 'Dashboard demo – upcoming PPN submission deadline',
                'is_recurring' => true,
                'recurrence_pattern' => 'monthly',
                'is_active' => true,
                'updated_at' => now(),
            ]
        );

        TaxSetting::updateOrCreate(
            ['setting_key' => 'dashboard_demo_tax_flag'],
            [
                'setting_name' => 'Dashboard Demo Tax Flag',
                'setting_value' => 'Demo reminder flag',
                'data_type' => 'string',
                'description' => 'Inactive setting to surface configuration alert on dashboard demo data',
                'is_active' => false,
            ]
        );
    }

    private function seedDocumentRelationships($salesOrders, $purchaseOrders): void
    {
        $salesInvoice = SalesInvoice::where('invoice_no', 'like', 'SINV-DEMO%')->first();
        $purchaseInvoice = PurchaseInvoice::where('invoice_no', 'like', 'PINV-DEMO%')->first();
        $salesOrder = $salesOrders->first();
        $purchaseOrder = $purchaseOrders->first();

        if ($salesOrder && $salesInvoice) {
            DocumentRelationship::updateOrCreate(
                [
                    'source_document_type' => 'sales_order',
                    'source_document_id' => $salesOrder->id,
                    'target_document_type' => 'sales_invoice',
                    'target_document_id' => $salesInvoice->id,
                ],
                [
                    'relationship_type' => 'target',
                    'notes' => 'Demo SO→SI linkage for dashboard sample',
                ]
            );
        }

        if ($purchaseOrder && $purchaseInvoice) {
            DocumentRelationship::updateOrCreate(
                [
                    'source_document_type' => 'purchase_order',
                    'source_document_id' => $purchaseOrder->id,
                    'target_document_type' => 'purchase_invoice',
                    'target_document_id' => $purchaseInvoice->id,
                ],
                [
                    'relationship_type' => 'target',
                    'notes' => 'Demo PO→PI linkage for dashboard sample',
                ]
            );
        }
    }
}
