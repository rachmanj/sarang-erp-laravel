<?php

use App\Http\Controllers\SalesOrderController;
use App\Http\Controllers\SalesQuotationController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\GoodsReceiptPOController;
use App\Http\Controllers\DeliveryOrderController;
use App\Http\Controllers\PurchaseDashboardController;
use App\Http\Controllers\SalesDashboardController;
use Illuminate\Support\Facades\Route;

// Sales Dashboard
Route::get('/sales/dashboard', [SalesDashboardController::class, 'index'])
    ->name('sales.dashboard');

// Sales Orders
Route::prefix('sales-orders')->group(function () {
    Route::get('/', [SalesOrderController::class, 'index'])->name('sales-orders.index');
    Route::get('/data', function () {
        $q = \Illuminate\Support\Facades\DB::table('sales_orders as so')
            ->leftJoin('business_partners as c', 'c.id', '=', 'so.business_partner_id')
            ->select('so.id', 'so.date', 'so.order_no', 'so.reference_no', 'so.business_partner_id', 'c.name as customer_name', 'so.total_amount', 'so.status');
        if (request()->filled('status')) {
            $q->where('so.status', request('status'));
        }
        if (request()->filled('from')) {
            $q->whereDate('so.date', '>=', request('from'));
        }
        if (request()->filled('to')) {
            $q->whereDate('so.date', '<=', request('to'));
        }
        if (request()->filled('business_partner_id')) {
            $q->where('so.business_partner_id', (int)request('business_partner_id'));
        }
        if (request()->filled('order_no')) {
            $q->where('so.order_no', 'like', '%' . request('order_no') . '%');
        }
        if (request()->filled('reference_no')) {
            $q->where('so.reference_no', 'like', '%' . request('reference_no') . '%');
        }
        if (request()->filled('q')) {
            $kw = request('q');
            $q->where(function ($w) use ($kw) {
                $w->where('so.order_no', 'like', '%' . $kw . '%')
                    ->orWhere('so.reference_no', 'like', '%' . $kw . '%')
                    ->orWhere('so.description', 'like', '%' . $kw . '%')
                    ->orWhere('c.name', 'like', '%' . $kw . '%');
            });
        }
        return Yajra\DataTables\Facades\DataTables::of($q)
            ->editColumn('id', function ($r) {
                static $idx = -1;
                $idx++;
                return (int) request()->input('start', 0) + $idx + 1;
            })
            ->editColumn('date', function ($r) {
                if ($r->date) {
                    return \Carbon\Carbon::parse($r->date)->format('d-M-Y');
                }
                return '-';
            })
            ->editColumn('total_amount', fn($r) => 'Rp ' . number_format((float)$r->total_amount, 0, ',', '.'))
            ->editColumn('reference_no', fn($r) => $r->reference_no ?: '-')
            ->addColumn('customer', fn($r) => $r->customer_name ?: ('#' . $r->business_partner_id))
            ->addColumn('actions', function ($r) {
                $actions = '<a class="btn btn-xs btn-info" href="' . route('sales-orders.show', $r->id) . '">View</a>';
                
                // Add edit button only for draft status
                if ($r->status === 'draft') {
                    $actions .= ' <a class="btn btn-xs btn-warning" href="' . route('sales-orders.edit', $r->id) . '">Edit</a>';
                }
                
                return $actions;
            })
            ->rawColumns(['actions'])->toJson();
    })->name('sales-orders.data');
    Route::get('/csv', function () {
        $q = \Illuminate\Support\Facades\DB::table('sales_orders as so')
            ->leftJoin('business_partners as c', 'c.id', '=', 'so.business_partner_id')
            ->select('so.date', 'so.order_no', 'so.reference_no', 'c.name as customer', 'so.total_amount', 'so.status');
        if (request()->filled('status')) {
            $q->where('so.status', request('status'));
        }
        if (request()->filled('from')) {
            $q->whereDate('so.date', '>=', request('from'));
        }
        if (request()->filled('to')) {
            $q->whereDate('so.date', '<=', request('to'));
        }
        if (request()->filled('business_partner_id')) {
            $q->where('so.business_partner_id', (int)request('business_partner_id'));
        }
        if (request()->filled('order_no')) {
            $q->where('so.order_no', 'like', '%' . request('order_no') . '%');
        }
        if (request()->filled('reference_no')) {
            $q->where('so.reference_no', 'like', '%' . request('reference_no') . '%');
        }
        if (request()->filled('q')) {
            $kw = request('q');
            $q->where(function ($w) use ($kw) {
                $w->where('so.order_no', 'like', '%' . $kw . '%')
                    ->orWhere('so.reference_no', 'like', '%' . $kw . '%')
                    ->orWhere('so.description', 'like', '%' . $kw . '%')
                    ->orWhere('c.name', 'like', '%' . $kw . '%');
            });
        }
        $rows = $q->orderBy('so.date', 'desc')->get();
        $csv = "date,order_no,reference_no,customer,total,status\n";
        foreach ($rows as $r) {
            $csv .= sprintf("%s,%s,%s,%s,%.2f,%s\n", $r->date, $r->order_no, str_replace(',', ' ', (string)($r->reference_no ?? '')), str_replace(',', ' ', (string)$r->customer), (float)$r->total_amount, $r->status);
        }
        return response($csv, 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="sales-orders.csv"']);
    })->name('sales-orders.csv');
    Route::get('/create', [SalesOrderController::class, 'create'])->name('sales-orders.create');
    Route::post('/', [SalesOrderController::class, 'store'])->name('sales-orders.store');
    Route::get('/{id}', [SalesOrderController::class, 'show'])->name('sales-orders.show');
    Route::get('/{id}/edit', [SalesOrderController::class, 'edit'])->name('sales-orders.edit');
    Route::patch('/{id}', [SalesOrderController::class, 'update'])->name('sales-orders.update');
    Route::get('/{id}/create-invoice', [SalesOrderController::class, 'createInvoice'])->name('sales-orders.create-invoice');
    Route::get('/fix-approval/{orderNo}', function ($orderNo) {
        $salesOrder = \App\Models\SalesOrder::where('order_no', $orderNo)->firstOrFail();
        
        // Ensure superadmin has officer role
        $officerRole = \App\Models\UserRole::where('user_id', 1)
            ->where('role_name', 'officer')
            ->first();
            
        if (!$officerRole) {
            \App\Models\UserRole::create([
                'user_id' => 1,
                'role_name' => 'officer',
                'is_active' => true,
            ]);
        }
        
        // Create approval workflow if missing
        $existingApprovals = \App\Models\SalesOrderApproval::where('sales_order_id', $salesOrder->id)->count();
        if ($existingApprovals === 0) {
            $approvalWorkflowService = app(\App\Services\ApprovalWorkflowService::class);
            $approvalRecords = $approvalWorkflowService->createWorkflowForDocument(
                'sales_order',
                $salesOrder->id,
                $salesOrder->total_amount
            );
            
            foreach ($approvalRecords as $record) {
                \App\Models\SalesOrderApproval::create([
                    'sales_order_id' => $record['document_id'],
                    'user_id' => $record['user_id'],
                    'approval_level' => $record['role_name'],
                    'status' => $record['status'],
                ]);
            }
        }
        
        return redirect()->route('sales-orders.show', $salesOrder->id)
            ->with('success', 'Approval workflow fixed. You can now approve the Sales Order.');
    })->name('sales-orders.fix-approval');
    
    Route::post('/{id}/approve', [SalesOrderController::class, 'approve'])->name('sales-orders.approve');
    Route::post('/{id}/confirm', [SalesOrderController::class, 'confirm'])->name('sales-orders.confirm');
    Route::post('/{id}/close', [SalesOrderController::class, 'close'])->name('sales-orders.close');

    // Currency API Routes
    Route::get('/api/exchange-rate', [SalesOrderController::class, 'getExchangeRate'])->name('sales-orders.api.exchange-rate');
    Route::get('/api/document-number', [SalesOrderController::class, 'getDocumentNumber'])->name('sales-orders.api.document-number');
});

// Sales Quotations
Route::prefix('sales-quotations')->middleware(['auth'])->group(function () {
    Route::get('/', [SalesQuotationController::class, 'index'])->middleware('permission:ar.quotations.view')->name('sales-quotations.index');
    Route::get('/data', [SalesQuotationController::class, 'data'])->middleware('permission:ar.quotations.view')->name('sales-quotations.data');
    Route::get('/csv', function () {
        $q = \Illuminate\Support\Facades\DB::table('sales_quotations as sq')
            ->leftJoin('business_partners as bp', 'bp.id', '=', 'sq.business_partner_id')
            ->select('sq.date', 'sq.quotation_no', 'sq.valid_until_date', 'bp.name as customer', 'sq.total_amount', 'sq.net_amount', 'sq.status', 'sq.approval_status');
        if (request()->filled('status')) {
            $q->where('sq.status', request('status'));
        }
        if (request()->filled('from')) {
            $q->whereDate('sq.date', '>=', request('from'));
        }
        if (request()->filled('to')) {
            $q->whereDate('sq.date', '<=', request('to'));
        }
        if (request()->filled('business_partner_id')) {
            $q->where('sq.business_partner_id', (int)request('business_partner_id'));
        }
        if (request()->filled('q')) {
            $kw = request('q');
            $q->where(function ($w) use ($kw) {
                $w->where('sq.quotation_no', 'like', '%' . $kw . '%')
                    ->orWhere('sq.reference_no', 'like', '%' . $kw . '%')
                    ->orWhere('bp.name', 'like', '%' . $kw . '%');
            });
        }
        $rows = $q->orderBy('sq.date', 'desc')->get();
        $csv = "date,quotation_no,valid_until_date,customer,total_amount,net_amount,status,approval_status\n";
        foreach ($rows as $r) {
            $csv .= sprintf(
                "%s,%s,%s,%s,%.2f,%.2f,%s,%s\n",
                $r->date,
                $r->quotation_no,
                $r->valid_until_date,
                str_replace(',', ' ', (string)$r->customer),
                (float)$r->total_amount,
                (float)$r->net_amount,
                $r->status,
                $r->approval_status
            );
        }
        return response($csv, 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="sales-quotations.csv"']);
    })->middleware('permission:ar.quotations.view')->name('sales-quotations.csv');
    Route::get('/create', [SalesQuotationController::class, 'create'])->middleware('permission:ar.quotations.create')->name('sales-quotations.create');
    Route::post('/', [SalesQuotationController::class, 'store'])->middleware('permission:ar.quotations.create')->name('sales-quotations.store');
    Route::get('/{id}', [SalesQuotationController::class, 'show'])->middleware('permission:ar.quotations.view')->name('sales-quotations.show');
    Route::get('/{id}/edit', [SalesQuotationController::class, 'edit'])->middleware('permission:ar.quotations.update')->name('sales-quotations.edit');
    Route::put('/{id}', [SalesQuotationController::class, 'update'])->middleware('permission:ar.quotations.update')->name('sales-quotations.update');
    Route::delete('/{id}', [SalesQuotationController::class, 'destroy'])->middleware('permission:ar.quotations.delete')->name('sales-quotations.destroy');
    Route::post('/{id}/send', [SalesQuotationController::class, 'send'])->middleware('permission:ar.quotations.update')->name('sales-quotations.send');
    Route::post('/{id}/accept', [SalesQuotationController::class, 'accept'])->middleware('permission:ar.quotations.update')->name('sales-quotations.accept');
    Route::post('/{id}/reject', [SalesQuotationController::class, 'reject'])->middleware('permission:ar.quotations.update')->name('sales-quotations.reject');
    Route::get('/{id}/convert', [SalesQuotationController::class, 'convert'])->middleware('permission:ar.quotations.convert')->name('sales-quotations.convert');
    Route::post('/{id}/convert-to-sales-order', [SalesQuotationController::class, 'convertToSalesOrder'])->middleware('permission:ar.quotations.convert')->name('sales-quotations.convert-to-sales-order');
    Route::get('/{id}/print', [SalesQuotationController::class, 'print'])->middleware('permission:ar.quotations.view')->name('sales-quotations.print');
    Route::post('/{id}/approve', [SalesQuotationController::class, 'approve'])->middleware('permission:ar.quotations.approve')->name('sales-quotations.approve');
    Route::post('/{id}/reject-approval', [SalesQuotationController::class, 'rejectApproval'])->middleware('permission:ar.quotations.approve')->name('sales-quotations.reject-approval');

    // API Routes
    Route::get('/api/exchange-rate', [SalesQuotationController::class, 'getExchangeRate'])->name('sales-quotations.api.exchange-rate');
    Route::get('/api/document-number', [SalesQuotationController::class, 'getDocumentNumber'])->name('sales-quotations.api.document-number');
});

// Delivery Orders
Route::prefix('delivery-orders')->group(function () {
    Route::get('/', [DeliveryOrderController::class, 'index'])->name('delivery-orders.index');
    Route::get('/data', function () {
        $q = \Illuminate\Support\Facades\DB::table('delivery_orders as do')
            ->leftJoin('business_partners as c', 'c.id', '=', 'do.business_partner_id')
            ->leftJoin('sales_orders as so', 'so.id', '=', 'do.sales_order_id')
            ->leftJoin('users as u', 'u.id', '=', 'do.created_by')
            ->select('do.id', 'do.created_at', 'do.do_number', 'do.business_partner_id', 'c.name as customer_name', 'so.order_no as sales_order_no', 'do.planned_delivery_date', 'do.status', 'do.approval_status', 'do.created_by', 'u.name as creator_name');
        if (request()->filled('status')) {
            $q->where('do.status', request('status'));
        }
        if (request()->filled('date_from') || request()->filled('from')) {
            $dateFrom = request('date_from') ?: request('from');
            $q->whereDate('do.planned_delivery_date', '>=', $dateFrom);
        }
        if (request()->filled('date_to') || request()->filled('to')) {
            $dateTo = request('date_to') ?: request('to');
            $q->whereDate('do.planned_delivery_date', '<=', $dateTo);
        }
        if (request()->filled('customer_id') || request()->filled('business_partner_id')) {
            $customerId = request('customer_id') ?: request('business_partner_id');
            $q->where('do.business_partner_id', (int)$customerId);
        }
        if (request()->filled('company_entity_id')) {
            $q->where('do.company_entity_id', (int)request('company_entity_id'));
        }
        if (request()->filled('q')) {
            $kw = request('q');
            $q->where(function ($w) use ($kw) {
                $w->where('do.do_number', 'like', '%' . $kw . '%')->orWhere('so.order_no', 'like', '%' . $kw . '%')->orWhere('c.name', 'like', '%' . $kw . '%');
            });
        }
        return Yajra\DataTables\Facades\DataTables::of($q)
            ->filterColumn('do_number', function ($query, $keyword) {
                $query->where('do.do_number', 'like', '%' . $keyword . '%');
            })
            ->filterColumn('sales_order_no', function ($query, $keyword) {
                $query->where('so.order_no', 'like', '%' . $keyword . '%');
            })
            ->filterColumn('customer', function ($query, $keyword) {
                $query->where('c.name', 'like', '%' . $keyword . '%');
            })
            ->filterColumn('created_by', function ($query, $keyword) {
                $query->where('u.name', 'like', '%' . $keyword . '%');
            })
            ->editColumn('planned_delivery_date', function ($r) {
                return $r->planned_delivery_date ? \Carbon\Carbon::parse($r->planned_delivery_date)->format('d-M-Y') : '';
            })
            ->addColumn('customer', fn($r) => $r->customer_name ?: ('#' . $r->business_partner_id))
            ->addColumn('created_by', fn($r) => $r->creator_name ?: ('#' . $r->created_by))
            ->addColumn('actions', function ($r) {
                $url = route('delivery-orders.show', $r->id);
                return '<a class="btn btn-xs btn-info" href="' . $url . '">View</a>';
            })
            ->rawColumns(['actions'])->toJson();
    })->name('delivery-orders.data');
    Route::get('/create', [DeliveryOrderController::class, 'create'])->name('delivery-orders.create');
    Route::post('/', [DeliveryOrderController::class, 'store'])->name('delivery-orders.store');
    Route::get('/{deliveryOrder}', [DeliveryOrderController::class, 'show'])->name('delivery-orders.show');
    Route::get('/{deliveryOrder}/edit', [DeliveryOrderController::class, 'edit'])->name('delivery-orders.edit');
    Route::patch('/{deliveryOrder}', [DeliveryOrderController::class, 'update'])->name('delivery-orders.update');
    Route::delete('/{deliveryOrder}', [DeliveryOrderController::class, 'destroy'])->name('delivery-orders.destroy');
    Route::post('/{deliveryOrder}/approve', [DeliveryOrderController::class, 'approve'])->name('delivery-orders.approve');
    Route::post('/{deliveryOrder}/reject', [DeliveryOrderController::class, 'reject'])->name('delivery-orders.reject');
    Route::post('/{deliveryOrder}/update-picking', [DeliveryOrderController::class, 'updatePicking'])->name('delivery-orders.update-picking');
    Route::post('/{deliveryOrder}/update-delivery', [DeliveryOrderController::class, 'updateDelivery'])->name('delivery-orders.update-delivery');
    Route::post('/{deliveryOrder}/mark-delivered', [DeliveryOrderController::class, 'markAsDelivered'])->name('delivery-orders.mark-delivered');
    Route::get('/{deliveryOrder}/print', [DeliveryOrderController::class, 'print'])->name('delivery-orders.print');
    Route::get('/{deliveryOrder}/create-invoice', [DeliveryOrderController::class, 'createInvoice'])->name('delivery-orders.create-invoice');
});

// Purchase Dashboard
Route::get('/purchase/dashboard', [PurchaseDashboardController::class, 'index'])
    ->name('purchase.dashboard');

// Purchase Orders
Route::prefix('purchase-orders')->group(function () {
    Route::get('/', [PurchaseOrderController::class, 'index'])->name('purchase-orders.index');
    Route::get('/data', function () {
        $q = \Illuminate\Support\Facades\DB::table('purchase_orders as po')
            ->leftJoin('business_partners as v', 'v.id', '=', 'po.business_partner_id')
            ->select('po.id', 'po.date', 'po.order_no', 'po.business_partner_id', 'v.name as vendor_name', 'po.total_amount', 'po.status', 'po.closure_status', 'po.closed_at', 'po.closed_by_document_type', 'po.closed_by_document_id');
        if (request()->filled('status')) {
            $q->where('po.status', request('status'));
        }
        if (request()->filled('closure_status')) {
            $q->where('po.closure_status', request('closure_status'));
        }
        if (request()->filled('from')) {
            $q->whereDate('po.date', '>=', request('from'));
        }
        if (request()->filled('to')) {
            $q->whereDate('po.date', '<=', request('to'));
        }
        if (request()->filled('business_partner_id')) {
            $q->where('po.business_partner_id', (int)request('business_partner_id'));
        }
        if (request()->filled('company_entity_id')) {
            $q->where('po.company_entity_id', (int)request('company_entity_id'));
        }
        if (request()->filled('q')) {
            $kw = request('q');
            $q->where(function ($w) use ($kw) {
                $w->where('po.order_no', 'like', '%' . $kw . '%')->orWhere('po.description', 'like', '%' . $kw . '%')->orWhere('v.name', 'like', '%' . $kw . '%');
            });
        }
        return Yajra\DataTables\Facades\DataTables::of($q)
            ->editColumn('date', function ($r) {
                return $r->date ? \Carbon\Carbon::parse($r->date)->format('d-M-Y') : '';
            })
            ->editColumn('total_amount', fn($r) => 'Rp ' . number_format((float)$r->total_amount, 0, ',', '.'))
            ->addColumn('vendor', fn($r) => $r->vendor_name ?: ('#' . $r->business_partner_id))
            ->addColumn('closure_status', function ($r) {
                if ($r->closure_status === 'closed') {
                    $closedBy = $r->closed_by_document_type ? ' by ' . $r->closed_by_document_type : '';
                    return '<span class="badge badge-success"><i class="fas fa-check"></i> Closed' . $closedBy . '</span>';
                } else {
                    $daysOpen = round($r->closed_at ? \Carbon\Carbon::parse($r->closed_at)->diffInDays(now()) : \Carbon\Carbon::parse($r->date)->diffInDays(now()));
                    $isOverdue = $daysOpen > 30; // Default threshold
                    $badgeClass = $isOverdue ? 'badge-warning' : 'badge-info';
                    return '<span class="badge ' . $badgeClass . '"><i class="fas fa-clock"></i> Open (' . $daysOpen . ' days)</span>';
                }
            })
            ->addColumn('actions', function ($r) {
                $actions = '<a class="btn btn-xs btn-info" href="' . route('purchase-orders.show', $r->id) . '">View</a>';

                // Add edit button only for draft status
                if ($r->status === 'draft') {
                    $actions .= ' <a class="btn btn-xs btn-warning" href="' . route('purchase-orders.edit', $r->id) . '">Edit</a>';
                }

                return $actions;
            })
            ->rawColumns(['actions', 'closure_status'])->toJson();
    })->name('purchase-orders.data');
    Route::get('/csv', function () {
        $q = \Illuminate\Support\Facades\DB::table('purchase_orders as po')
            ->leftJoin('business_partners as v', 'v.id', '=', 'po.business_partner_id')
            ->select('po.date', 'po.order_no', 'v.name as vendor', 'po.total_amount', 'po.status', 'po.closure_status', 'po.created_at');
        if (request()->filled('status')) {
            $q->where('po.status', request('status'));
        }
        if (request()->filled('from')) {
            $q->whereDate('po.date', '>=', request('from'));
        }
        if (request()->filled('to')) {
            $q->whereDate('po.date', '<=', request('to'));
        }
        if (request()->filled('business_partner_id')) {
            $q->where('po.business_partner_id', (int)request('business_partner_id'));
        }
        if (request()->filled('q')) {
            $kw = request('q');
            $q->where(function ($w) use ($kw) {
                $w->where('po.order_no', 'like', '%' . $kw . '%')->orWhere('po.description', 'like', '%' . $kw . '%')->orWhere('v.name', 'like', '%' . $kw . '%');
            });
        }
        $rows = $q->orderBy('po.date', 'desc')->get();
        $csv = "date,order_no,vendor,total,status,closure_status\n";
        foreach ($rows as $r) {
            $formattedDate = $r->date ? \Carbon\Carbon::parse($r->date)->format('d-M-Y') : '';
            $formattedTotal = 'Rp ' . number_format($r->total_amount, 0, ',', '.');
            $closureStatus = $r->closure_status === 'open' ?
                round(\Carbon\Carbon::parse($r->created_at)->diffInDays(\Carbon\Carbon::now())) . ' days' :
                ucfirst($r->closure_status);
            $csv .= sprintf(
                "%s,%s,%s,%s,%s,%s\n",
                $formattedDate,
                $r->order_no,
                str_replace(',', ' ', (string)$r->vendor),
                $formattedTotal,
                $r->status,
                $closureStatus
            );
        }
        return response($csv, 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="purchase-orders.csv"']);
    })->name('purchase-orders.csv');
    Route::get('/create', [PurchaseOrderController::class, 'create'])->name('purchase-orders.create');
    Route::post('/', [PurchaseOrderController::class, 'store'])->name('purchase-orders.store');
    Route::get('/{id}', [PurchaseOrderController::class, 'show'])->name('purchase-orders.show');
    Route::get('/{id}/edit', [PurchaseOrderController::class, 'edit'])->name('purchase-orders.edit');
    Route::put('/{id}', [PurchaseOrderController::class, 'update'])->name('purchase-orders.update');
    Route::get('/{id}/create-invoice', [PurchaseOrderController::class, 'createInvoice'])->name('purchase-orders.create-invoice');
    Route::get('/{id}/create-assets', [PurchaseOrderController::class, 'createAssets'])->name('purchase-orders.create-assets');
    Route::post('/{id}/store-assets', [PurchaseOrderController::class, 'storeAssets'])->name('purchase-orders.store-assets');
    Route::get('/asset-categories', [PurchaseOrderController::class, 'getAssetCategories'])->name('purchase-orders.asset-categories');
    Route::post('/{id}/approve', [PurchaseOrderController::class, 'approve'])->name('purchase-orders.approve');
    Route::post('/{id}/close', [PurchaseOrderController::class, 'close'])->name('purchase-orders.close');
    Route::delete('/{id}', [PurchaseOrderController::class, 'destroy'])->name('purchase-orders.destroy');
    Route::get('/{id}/copy-to-grpo', [PurchaseOrderController::class, 'showCopyToGRPO'])->name('purchase-orders.show-copy-to-grpo');
    Route::post('/{id}/copy-to-grpo', [PurchaseOrderController::class, 'copyToGRPO'])->name('purchase-orders.copy-to-grpo');
    Route::get('/{id}/copy-to-purchase-invoice', [PurchaseOrderController::class, 'showCopyToPurchaseInvoice'])->name('purchase-orders.show-copy-to-purchase-invoice');
    Route::get('/{id}/copy-to-purchase-invoice/execute', [PurchaseOrderController::class, 'copyToPurchaseInvoice'])->name('purchase-orders.copy-to-purchase-invoice');

    // Unit Conversion API Routes
    Route::get('/api/item-units', [PurchaseOrderController::class, 'getItemUnits'])->name('purchase-orders.api.item-units');
    Route::get('/api/conversion-preview', [PurchaseOrderController::class, 'getConversionPreview'])->name('purchase-orders.api.conversion-preview');

    // Currency API Routes
    Route::get('/api/exchange-rate', [PurchaseOrderController::class, 'getExchangeRate'])->name('purchase-orders.api.exchange-rate');
    Route::get('/api/document-number', [PurchaseOrderController::class, 'getDocumentNumber'])->name('purchase-orders.api.document-number');
});

// Goods Receipt PO
Route::prefix('goods-receipt-pos')->group(function () {
    Route::get('/', [GoodsReceiptPOController::class, 'index'])->name('goods-receipt-pos.index');
    Route::get('/data', function () {
        $q = \Illuminate\Support\Facades\DB::table('goods_receipt_po as grpo')
            ->leftJoin('business_partners as v', 'v.id', '=', 'grpo.business_partner_id')
            ->select('grpo.id', 'grpo.date', 'grpo.grn_no', 'grpo.business_partner_id', 'v.name as vendor_name', 'grpo.total_amount', 'grpo.status');
        if (request()->filled('status')) {
            $q->where('grpo.status', request('status'));
        }
        if (request()->filled('from')) {
            $q->whereDate('grpo.date', '>=', request('from'));
        }
        if (request()->filled('to')) {
            $q->whereDate('grpo.date', '<=', request('to'));
        }
        if (request()->filled('business_partner_id')) {
            $q->where('grpo.business_partner_id', (int)request('business_partner_id'));
        }
        if (request()->filled('q')) {
            $kw = request('q');
            $q->where(function ($w) use ($kw) {
                $w->where('grpo.grn_no', 'like', '%' . $kw . '%')->orWhere('grpo.description', 'like', '%' . $kw . '%')->orWhere('v.name', 'like', '%' . $kw . '%');
            });
        }
        return Yajra\DataTables\Facades\DataTables::of($q)
            ->editColumn('total_amount', fn($r) => number_format((float)$r->total_amount, 2))
            ->addColumn('vendor', fn($r) => $r->vendor_name ?: ('#' . $r->business_partner_id))
            ->addColumn('actions', function ($r) {
                $url = route('goods-receipt-pos.show', $r->id);
                return '<a class="btn btn-xs btn-info" href="' . $url . '">View</a>';
            })
            ->rawColumns(['actions'])->toJson();
    })->name('goods-receipt-pos.data');
    Route::get('/csv', function () {
        $q = \Illuminate\Support\Facades\DB::table('goods_receipt_po as grpo')
            ->leftJoin('business_partners as v', 'v.id', '=', 'grpo.business_partner_id')
            ->select('grpo.date', 'grpo.grn_no', 'v.name as vendor', 'grpo.total_amount', 'grpo.status');
        if (request()->filled('status')) {
            $q->where('grpo.status', request('status'));
        }
        if (request()->filled('from')) {
            $q->whereDate('grpo.date', '>=', request('from'));
        }
        if (request()->filled('to')) {
            $q->whereDate('grpo.date', '<=', request('to'));
        }
        if (request()->filled('business_partner_id')) {
            $q->where('grpo.business_partner_id', (int)request('business_partner_id'));
        }
        if (request()->filled('q')) {
            $kw = request('q');
            $q->where(function ($w) use ($kw) {
                $w->where('grpo.grn_no', 'like', '%' . $kw . '%')->orWhere('grpo.description', 'like', '%' . $kw . '%')->orWhere('v.name', 'like', '%' . $kw . '%');
            });
        }
        $rows = $q->orderBy('grpo.date', 'desc')->get();
        $csv = "date,grpo_no,vendor,total,status\n";
        foreach ($rows as $r) {
            $csv .= sprintf("%s,%s,%s,%.2f,%s\n", $r->date, $r->grn_no, str_replace(',', ' ', (string)$r->vendor), (float)$r->total_amount, $r->status);
        }
        return response($csv, 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="goods-receipt-pos.csv"']);
    })->name('goods-receipt-pos.csv');
    Route::get('/create', [GoodsReceiptPOController::class, 'create'])->name('goods-receipt-pos.create');
    Route::post('/', [GoodsReceiptPOController::class, 'store'])->name('goods-receipt-pos.store');

    // AJAX endpoints for enhanced functionality (must come before parameterized routes)
    Route::get('/available-pos', [GoodsReceiptPOController::class, 'getAvailablePOs'])->name('goods-receipt-pos.available-pos');
    Route::get('/vendor-pos', [GoodsReceiptPOController::class, 'getVendorPOs'])->name('goods-receipt-pos.vendor-pos');
    Route::get('/remaining-lines', [GoodsReceiptPOController::class, 'getRemainingPOLines'])->name('goods-receipt-pos.remaining-lines');

    // Parameterized routes (must come after specific routes)
    Route::get('/{id}', [GoodsReceiptPOController::class, 'show'])->name('goods-receipt-pos.show');
    Route::get('/{id}/create-invoice', [GoodsReceiptPOController::class, 'createInvoice'])->name('goods-receipt-pos.create-invoice');
    Route::post('/{id}/receive', [GoodsReceiptPOController::class, 'receive'])->name('goods-receipt-pos.receive');

    // Journal management routes
    Route::post('/{id}/create-journal', [GoodsReceiptPOController::class, 'createJournal'])->name('goods-receipt-pos.create-journal');
    Route::post('/{id}/reverse-journal', [GoodsReceiptPOController::class, 'reverseJournal'])->name('goods-receipt-pos.reverse-journal');
    Route::get('/{id}/journal', [GoodsReceiptPOController::class, 'showJournal'])->name('goods-receipt-pos.journal');
    
    // Inventory transaction fix route
    Route::post('/{id}/fix-inventory', [GoodsReceiptPOController::class, 'fixInventoryTransactions'])->name('goods-receipt-pos.fix-inventory');
});
