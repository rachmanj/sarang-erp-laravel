<?php

use App\Http\Controllers\SalesOrderController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\GoodsReceiptPOController;
use App\Http\Controllers\DeliveryOrderController;
use Illuminate\Support\Facades\Route;

// Sales Orders
Route::prefix('sales-orders')->group(function () {
    Route::get('/', [SalesOrderController::class, 'index'])->name('sales-orders.index');
    Route::get('/data', function () {
        $q = \Illuminate\Support\Facades\DB::table('sales_orders as so')
            ->leftJoin('business_partners as c', 'c.id', '=', 'so.business_partner_id')
            ->select('so.id', 'so.date', 'so.order_no', 'so.business_partner_id', 'c.name as customer_name', 'so.total_amount', 'so.status');
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
        if (request()->filled('q')) {
            $kw = request('q');
            $q->where(function ($w) use ($kw) {
                $w->where('so.order_no', 'like', '%' . $kw . '%')->orWhere('so.description', 'like', '%' . $kw . '%')->orWhere('c.name', 'like', '%' . $kw . '%');
            });
        }
        return Yajra\DataTables\Facades\DataTables::of($q)
            ->editColumn('total_amount', fn($r) => number_format((float)$r->total_amount, 2))
            ->addColumn('customer', fn($r) => $r->customer_name ?: ('#' . $r->business_partner_id))
            ->addColumn('actions', function ($r) {
                $url = route('sales-orders.show', $r->id);
                return '<a class="btn btn-xs btn-info" href="' . $url . '">View</a>';
            })
            ->rawColumns(['actions'])->toJson();
    })->name('sales-orders.data');
    Route::get('/csv', function () {
        $q = \Illuminate\Support\Facades\DB::table('sales_orders as so')
            ->leftJoin('business_partners as c', 'c.id', '=', 'so.business_partner_id')
            ->select('so.date', 'so.order_no', 'c.name as customer', 'so.total_amount', 'so.status');
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
        if (request()->filled('q')) {
            $kw = request('q');
            $q->where(function ($w) use ($kw) {
                $w->where('so.order_no', 'like', '%' . $kw . '%')->orWhere('so.description', 'like', '%' . $kw . '%')->orWhere('c.name', 'like', '%' . $kw . '%');
            });
        }
        $rows = $q->orderBy('so.date', 'desc')->get();
        $csv = "date,order_no,customer,total,status\n";
        foreach ($rows as $r) {
            $csv .= sprintf("%s,%s,%s,%.2f,%s\n", $r->date, $r->order_no, str_replace(',', ' ', (string)$r->customer), (float)$r->total_amount, $r->status);
        }
        return response($csv, 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="sales-orders.csv"']);
    })->name('sales-orders.csv');
    Route::get('/create', [SalesOrderController::class, 'create'])->name('sales-orders.create');
    Route::post('/', [SalesOrderController::class, 'store'])->name('sales-orders.store');
    Route::get('/{id}', [SalesOrderController::class, 'show'])->name('sales-orders.show');
    Route::get('/{id}/create-invoice', [SalesOrderController::class, 'createInvoice'])->name('sales-orders.create-invoice');
    Route::post('/{id}/approve', [SalesOrderController::class, 'approve'])->name('sales-orders.approve');
    Route::post('/{id}/close', [SalesOrderController::class, 'close'])->name('sales-orders.close');
});

// Delivery Orders
Route::prefix('delivery-orders')->group(function () {
    Route::get('/', [DeliveryOrderController::class, 'index'])->name('delivery-orders.index');
    Route::get('/data', function () {
        $q = \Illuminate\Support\Facades\DB::table('delivery_orders as do')
            ->leftJoin('business_partners as c', 'c.id', '=', 'do.business_partner_id')
            ->leftJoin('sales_orders as so', 'so.id', '=', 'do.sales_order_id')
            ->select('do.id', 'do.created_at', 'do.do_number', 'do.business_partner_id', 'c.name as customer_name', 'so.order_no as sales_order_no', 'do.planned_delivery_date', 'do.status', 'do.approval_status');
        if (request()->filled('status')) {
            $q->where('do.status', request('status'));
        }
        if (request()->filled('from')) {
            $q->whereDate('do.planned_delivery_date', '>=', request('from'));
        }
        if (request()->filled('to')) {
            $q->whereDate('do.planned_delivery_date', '<=', request('to'));
        }
        if (request()->filled('business_partner_id')) {
            $q->where('do.business_partner_id', (int)request('business_partner_id'));
        }
        if (request()->filled('q')) {
            $kw = request('q');
            $q->where(function ($w) use ($kw) {
                $w->where('do.do_number', 'like', '%' . $kw . '%')->orWhere('so.order_no', 'like', '%' . $kw . '%')->orWhere('c.name', 'like', '%' . $kw . '%');
            });
        }
        return Yajra\DataTables\Facades\DataTables::of($q)
            ->addColumn('customer', fn($r) => $r->customer_name ?: ('#' . $r->business_partner_id))
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
    Route::post('/{deliveryOrder}/complete-delivery', [DeliveryOrderController::class, 'completeDelivery'])->name('delivery-orders.complete-delivery');
    Route::get('/{deliveryOrder}/print', [DeliveryOrderController::class, 'print'])->name('delivery-orders.print');
    Route::get('/{deliveryOrder}/create-invoice', [DeliveryOrderController::class, 'createInvoice'])->name('delivery-orders.create-invoice');
});

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
        if (request()->filled('q')) {
            $kw = request('q');
            $q->where(function ($w) use ($kw) {
                $w->where('po.order_no', 'like', '%' . $kw . '%')->orWhere('po.description', 'like', '%' . $kw . '%')->orWhere('v.name', 'like', '%' . $kw . '%');
            });
        }
        return Yajra\DataTables\Facades\DataTables::of($q)
            ->editColumn('total_amount', fn($r) => number_format((float)$r->total_amount, 2))
            ->addColumn('vendor', fn($r) => $r->vendor_name ?: ('#' . $r->business_partner_id))
            ->addColumn('closure_status', function ($r) {
                if ($r->closure_status === 'closed') {
                    $closedBy = $r->closed_by_document_type ? ' by ' . $r->closed_by_document_type : '';
                    return '<span class="badge badge-success"><i class="fas fa-check"></i> Closed' . $closedBy . '</span>';
                } else {
                    $daysOpen = $r->closed_at ? \Carbon\Carbon::parse($r->closed_at)->diffInDays(now()) : \Carbon\Carbon::parse($r->date)->diffInDays(now());
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
            ->select('po.date', 'po.order_no', 'v.name as vendor', 'po.total_amount', 'po.status');
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
        $csv = "date,order_no,vendor,total,status\n";
        foreach ($rows as $r) {
            $csv .= sprintf("%s,%s,%s,%.2f,%s\n", $r->date, $r->order_no, str_replace(',', ' ', (string)$r->vendor), (float)$r->total_amount, $r->status);
        }
        return response($csv, 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="purchase-orders.csv"']);
    })->name('purchase-orders.csv');
    Route::get('/create', [PurchaseOrderController::class, 'create'])->name('purchase-orders.create');
    Route::post('/', [PurchaseOrderController::class, 'store'])->name('purchase-orders.store');
    Route::get('/{id}', [PurchaseOrderController::class, 'show'])->name('purchase-orders.show');
    Route::get('/{id}/edit', [PurchaseOrderController::class, 'edit'])->name('purchase-orders.edit');
    Route::patch('/{id}', [PurchaseOrderController::class, 'update'])->name('purchase-orders.update');
    Route::get('/{id}/create-invoice', [PurchaseOrderController::class, 'createInvoice'])->name('purchase-orders.create-invoice');
    Route::get('/{id}/create-assets', [PurchaseOrderController::class, 'createAssets'])->name('purchase-orders.create-assets');
    Route::post('/{id}/store-assets', [PurchaseOrderController::class, 'storeAssets'])->name('purchase-orders.store-assets');
    Route::get('/asset-categories', [PurchaseOrderController::class, 'getAssetCategories'])->name('purchase-orders.asset-categories');
    Route::post('/{id}/approve', [PurchaseOrderController::class, 'approve'])->name('purchase-orders.approve');
    Route::post('/{id}/close', [PurchaseOrderController::class, 'close'])->name('purchase-orders.close');

    // Unit Conversion API Routes
    Route::get('/api/item-units', [PurchaseOrderController::class, 'getItemUnits'])->name('purchase-orders.api.item-units');
    Route::get('/api/conversion-preview', [PurchaseOrderController::class, 'getConversionPreview'])->name('purchase-orders.api.conversion-preview');
    Route::get('/api/units-by-type', [PurchaseOrderController::class, 'getUnitsByType'])->name('purchase-orders.api.units-by-type');
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
    Route::get('/vendor-pos', [GoodsReceiptPOController::class, 'getVendorPOs'])->name('goods-receipt-pos.vendor-pos');
    Route::get('/remaining-lines', [GoodsReceiptPOController::class, 'getRemainingPOLines'])->name('goods-receipt-pos.remaining-lines');

    // Parameterized routes (must come after specific routes)
    Route::get('/{id}', [GoodsReceiptPOController::class, 'show'])->name('goods-receipt-pos.show');
    Route::get('/{id}/create-invoice', [GoodsReceiptPOController::class, 'createInvoice'])->name('goods-receipt-pos.create-invoice');
    Route::post('/{id}/receive', [GoodsReceiptPOController::class, 'receive'])->name('goods-receipt-pos.receive');
});
