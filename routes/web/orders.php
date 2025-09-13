<?php

use App\Http\Controllers\SalesOrderController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\GoodsReceiptController;
use Illuminate\Support\Facades\Route;

// Sales Orders
Route::prefix('sales-orders')->group(function () {
    Route::get('/', [SalesOrderController::class, 'index'])->name('sales-orders.index');
    Route::get('/data', function () {
        $q = \Illuminate\Support\Facades\DB::table('sales_orders as so')
            ->leftJoin('customers as c', 'c.id', '=', 'so.customer_id')
            ->select('so.id', 'so.date', 'so.order_no', 'so.customer_id', 'c.name as customer_name', 'so.total_amount', 'so.status');
        if (request()->filled('status')) {
            $q->where('so.status', request('status'));
        }
        if (request()->filled('from')) {
            $q->whereDate('so.date', '>=', request('from'));
        }
        if (request()->filled('to')) {
            $q->whereDate('so.date', '<=', request('to'));
        }
        if (request()->filled('customer_id')) {
            $q->where('so.customer_id', (int)request('customer_id'));
        }
        if (request()->filled('q')) {
            $kw = request('q');
            $q->where(function ($w) use ($kw) {
                $w->where('so.order_no', 'like', '%' . $kw . '%')->orWhere('so.description', 'like', '%' . $kw . '%')->orWhere('c.name', 'like', '%' . $kw . '%');
            });
        }
        return Yajra\DataTables\Facades\DataTables::of($q)
            ->editColumn('total_amount', fn($r) => number_format((float)$r->total_amount, 2))
            ->addColumn('customer', fn($r) => $r->customer_name ?: ('#' . $r->customer_id))
            ->addColumn('actions', function ($r) {
                $url = route('sales-orders.show', $r->id);
                return '<a class="btn btn-xs btn-info" href="' . $url . '">View</a>';
            })
            ->rawColumns(['actions'])->toJson();
    })->name('sales-orders.data');
    Route::get('/csv', function () {
        $q = \Illuminate\Support\Facades\DB::table('sales_orders as so')
            ->leftJoin('customers as c', 'c.id', '=', 'so.customer_id')
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
        if (request()->filled('customer_id')) {
            $q->where('so.customer_id', (int)request('customer_id'));
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

// Purchase Orders
Route::prefix('purchase-orders')->group(function () {
    Route::get('/', [PurchaseOrderController::class, 'index'])->name('purchase-orders.index');
    Route::get('/data', function () {
        $q = \Illuminate\Support\Facades\DB::table('purchase_orders as po')
            ->leftJoin('vendors as v', 'v.id', '=', 'po.vendor_id')
            ->select('po.id', 'po.date', 'po.order_no', 'po.vendor_id', 'v.name as vendor_name', 'po.total_amount', 'po.status');
        if (request()->filled('status')) {
            $q->where('po.status', request('status'));
        }
        if (request()->filled('from')) {
            $q->whereDate('po.date', '>=', request('from'));
        }
        if (request()->filled('to')) {
            $q->whereDate('po.date', '<=', request('to'));
        }
        if (request()->filled('vendor_id')) {
            $q->where('po.vendor_id', (int)request('vendor_id'));
        }
        if (request()->filled('q')) {
            $kw = request('q');
            $q->where(function ($w) use ($kw) {
                $w->where('po.order_no', 'like', '%' . $kw . '%')->orWhere('po.description', 'like', '%' . $kw . '%')->orWhere('v.name', 'like', '%' . $kw . '%');
            });
        }
        return Yajra\DataTables\Facades\DataTables::of($q)
            ->editColumn('total_amount', fn($r) => number_format((float)$r->total_amount, 2))
            ->addColumn('vendor', fn($r) => $r->vendor_name ?: ('#' . $r->vendor_id))
            ->addColumn('actions', function ($r) {
                $url = route('purchase-orders.show', $r->id);
                return '<a class="btn btn-xs btn-info" href="' . $url . '">View</a>';
            })
            ->rawColumns(['actions'])->toJson();
    })->name('purchase-orders.data');
    Route::get('/csv', function () {
        $q = \Illuminate\Support\Facades\DB::table('purchase_orders as po')
            ->leftJoin('vendors as v', 'v.id', '=', 'po.vendor_id')
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
        if (request()->filled('vendor_id')) {
            $q->where('po.vendor_id', (int)request('vendor_id'));
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
    Route::get('/{id}/create-invoice', [PurchaseOrderController::class, 'createInvoice'])->name('purchase-orders.create-invoice');
    Route::get('/{id}/create-assets', [PurchaseOrderController::class, 'createAssets'])->name('purchase-orders.create-assets');
    Route::post('/{id}/store-assets', [PurchaseOrderController::class, 'storeAssets'])->name('purchase-orders.store-assets');
    Route::get('/asset-categories', [PurchaseOrderController::class, 'getAssetCategories'])->name('purchase-orders.asset-categories');
    Route::post('/{id}/approve', [PurchaseOrderController::class, 'approve'])->name('purchase-orders.approve');
    Route::post('/{id}/close', [PurchaseOrderController::class, 'close'])->name('purchase-orders.close');
});

// Goods Receipts
Route::prefix('goods-receipts')->group(function () {
    Route::get('/', [GoodsReceiptController::class, 'index'])->name('goods-receipts.index');
    Route::get('/data', function () {
        $q = \Illuminate\Support\Facades\DB::table('goods_receipts as grn')
            ->leftJoin('vendors as v', 'v.id', '=', 'grn.vendor_id')
            ->select('grn.id', 'grn.date', 'grn.grn_no', 'grn.vendor_id', 'v.name as vendor_name', 'grn.total_amount', 'grn.status');
        if (request()->filled('status')) {
            $q->where('grn.status', request('status'));
        }
        if (request()->filled('from')) {
            $q->whereDate('grn.date', '>=', request('from'));
        }
        if (request()->filled('to')) {
            $q->whereDate('grn.date', '<=', request('to'));
        }
        if (request()->filled('vendor_id')) {
            $q->where('grn.vendor_id', (int)request('vendor_id'));
        }
        if (request()->filled('q')) {
            $kw = request('q');
            $q->where(function ($w) use ($kw) {
                $w->where('grn.grn_no', 'like', '%' . $kw . '%')->orWhere('grn.description', 'like', '%' . $kw . '%')->orWhere('v.name', 'like', '%' . $kw . '%');
            });
        }
        return Yajra\DataTables\Facades\DataTables::of($q)
            ->editColumn('total_amount', fn($r) => number_format((float)$r->total_amount, 2))
            ->addColumn('vendor', fn($r) => $r->vendor_name ?: ('#' . $r->vendor_id))
            ->addColumn('actions', function ($r) {
                $url = route('goods-receipts.show', $r->id);
                return '<a class="btn btn-xs btn-info" href="' . $url . '">View</a>';
            })
            ->rawColumns(['actions'])->toJson();
    })->name('goods-receipts.data');
    Route::get('/csv', function () {
        $q = \Illuminate\Support\Facades\DB::table('goods_receipts as grn')
            ->leftJoin('vendors as v', 'v.id', '=', 'grn.vendor_id')
            ->select('grn.date', 'grn.grn_no', 'v.name as vendor', 'grn.total_amount', 'grn.status');
        if (request()->filled('status')) {
            $q->where('grn.status', request('status'));
        }
        if (request()->filled('from')) {
            $q->whereDate('grn.date', '>=', request('from'));
        }
        if (request()->filled('to')) {
            $q->whereDate('grn.date', '<=', request('to'));
        }
        if (request()->filled('vendor_id')) {
            $q->where('grn.vendor_id', (int)request('vendor_id'));
        }
        if (request()->filled('q')) {
            $kw = request('q');
            $q->where(function ($w) use ($kw) {
                $w->where('grn.grn_no', 'like', '%' . $kw . '%')->orWhere('grn.description', 'like', '%' . $kw . '%')->orWhere('v.name', 'like', '%' . $kw . '%');
            });
        }
        $rows = $q->orderBy('grn.date', 'desc')->get();
        $csv = "date,grn_no,vendor,total,status\n";
        foreach ($rows as $r) {
            $csv .= sprintf("%s,%s,%s,%.2f,%s\n", $r->date, $r->grn_no, str_replace(',', ' ', (string)$r->vendor), (float)$r->total_amount, $r->status);
        }
        return response($csv, 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="goods-receipts.csv"']);
    })->name('goods-receipts.csv');
    Route::get('/create', [GoodsReceiptController::class, 'create'])->name('goods-receipts.create');
    Route::post('/', [GoodsReceiptController::class, 'store'])->name('goods-receipts.store');
    Route::get('/{id}', [GoodsReceiptController::class, 'show'])->name('goods-receipts.show');
    Route::get('/{id}/create-invoice', [GoodsReceiptController::class, 'createInvoice'])->name('goods-receipts.create-invoice');
    Route::post('/{id}/receive', [GoodsReceiptController::class, 'receive'])->name('goods-receipts.receive');
});
