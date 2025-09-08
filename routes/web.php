<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Reports\ReportsController;
use App\Http\Controllers\Dev\PostingDemoController;
use App\Http\Controllers\Accounting\ManualJournalController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\RoleController as AdminRoleController;
use App\Http\Controllers\Admin\PermissionController as AdminPermissionController;
use App\Http\Controllers\Accounting\PeriodController;
use App\Http\Controllers\Accounting\SalesInvoiceController;
use App\Http\Controllers\Accounting\PurchaseInvoiceController;
use App\Http\Controllers\Accounting\SalesReceiptController;
use App\Http\Controllers\Accounting\PurchasePaymentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/login');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::prefix('reports')->group(function () {
        Route::get('/trial-balance', [ReportsController::class, 'trialBalance'])->name('reports.trial-balance');
        Route::get('/gl-detail', [ReportsController::class, 'glDetail'])->name('reports.gl-detail');
        Route::get('/ar-aging', [ReportsController::class, 'arAging'])->name('reports.ar-aging');
        Route::get('/ap-aging', [ReportsController::class, 'apAging'])->name('reports.ap-aging');
        Route::get('/cash-ledger', [ReportsController::class, 'cashLedger'])->name('reports.cash-ledger');
    });

    Route::prefix('dev')->group(function () {
        Route::post('/post-journal', [PostingDemoController::class, 'store'])->name('dev.post-journal');
    });

    Route::prefix('journals')->middleware(['permission:journals.view'])->group(function () {
        Route::get('/', [ManualJournalController::class, 'index'])->name('journals.index');
        Route::get('/manual/create', [ManualJournalController::class, 'create'])->middleware('permission:journals.create')->name('journals.manual.create');
        Route::post('/manual', [ManualJournalController::class, 'store'])->middleware('permission:journals.create')->name('journals.manual.store');
        Route::post('/{journal}/reverse', [ManualJournalController::class, 'reverse'])->middleware('permission:journals.reverse')->name('journals.reverse');
        Route::get('/data', [ManualJournalController::class, 'data'])->name('journals.data');
    });

    // Periods
    Route::prefix('periods')->group(function () {
        Route::get('/', [PeriodController::class, 'index'])->middleware('permission:periods.view')->name('periods.index');
        Route::post('/close', [PeriodController::class, 'close'])->middleware('permission:periods.close')->name('periods.close');
        Route::post('/open', [PeriodController::class, 'open'])->middleware('permission:periods.close')->name('periods.open');
    });

    // AR - Sales Invoices
    Route::prefix('sales-invoices')->group(function () {
        Route::get('/', [SalesInvoiceController::class, 'index'])->middleware('permission:ar.invoices.view')->name('sales-invoices.index');
        Route::get('/create', [SalesInvoiceController::class, 'create'])->middleware('permission:ar.invoices.create')->name('sales-invoices.create');
        Route::post('/', [SalesInvoiceController::class, 'store'])->middleware('permission:ar.invoices.create')->name('sales-invoices.store');
        Route::get('/{id}', [SalesInvoiceController::class, 'show'])->middleware('permission:ar.invoices.view')->name('sales-invoices.show');
        Route::post('/{id}/post', [SalesInvoiceController::class, 'post'])->middleware('permission:ar.invoices.post')->name('sales-invoices.post');
        Route::get('/{id}/print', function ($id) {
            $invoice = \App\Models\Accounting\SalesInvoice::with('lines')->findOrFail($id);
            return view('sales_invoices.print', compact('invoice'));
        })->middleware('permission:ar.invoices.view')->name('sales-invoices.print');
        Route::get('/{id}/pdf', [SalesInvoiceController::class, 'pdf'])->middleware('permission:ar.invoices.view')->name('sales-invoices.pdf');
        Route::post('/{id}/queue-pdf', [SalesInvoiceController::class, 'queuePdf'])->middleware('permission:ar.invoices.view')->name('sales-invoices.queuePdf');
    });

    // AP - Purchase Invoices
    Route::prefix('purchase-invoices')->group(function () {
        Route::get('/', [PurchaseInvoiceController::class, 'index'])->middleware('permission:ap.invoices.view')->name('purchase-invoices.index');
        Route::get('/create', [PurchaseInvoiceController::class, 'create'])->middleware('permission:ap.invoices.create')->name('purchase-invoices.create');
        Route::post('/', [PurchaseInvoiceController::class, 'store'])->middleware('permission:ap.invoices.create')->name('purchase-invoices.store');
        Route::get('/{id}', [PurchaseInvoiceController::class, 'show'])->middleware('permission:ap.invoices.view')->name('purchase-invoices.show');
        Route::post('/{id}/post', [PurchaseInvoiceController::class, 'post'])->middleware('permission:ap.invoices.post')->name('purchase-invoices.post');
        Route::get('/{id}/print', [PurchaseInvoiceController::class, 'print'])->middleware('permission:ap.invoices.view')->name('purchase-invoices.print');
        Route::get('/{id}/pdf', [PurchaseInvoiceController::class, 'pdf'])->middleware('permission:ap.invoices.view')->name('purchase-invoices.pdf');
        Route::post('/{id}/queue-pdf', [PurchaseInvoiceController::class, 'queuePdf'])->middleware('permission:ap.invoices.view')->name('purchase-invoices.queuePdf');
    });

    // AR - Sales Receipts
    Route::prefix('sales-receipts')->group(function () {
        Route::get('/', [SalesReceiptController::class, 'index'])->middleware('permission:ar.receipts.view')->name('sales-receipts.index');
        Route::get('/create', [SalesReceiptController::class, 'create'])->middleware('permission:ar.receipts.create')->name('sales-receipts.create');
        Route::post('/', [SalesReceiptController::class, 'store'])->middleware('permission:ar.receipts.create')->name('sales-receipts.store');
        Route::get('/{id}', [SalesReceiptController::class, 'show'])->middleware('permission:ar.receipts.view')->name('sales-receipts.show');
        Route::post('/{id}/post', [SalesReceiptController::class, 'post'])->middleware('permission:ar.receipts.post')->name('sales-receipts.post');
        Route::get('/{id}/print', function ($id) {
            $receipt = \App\Models\Accounting\SalesReceipt::with('lines')->findOrFail($id);
            return view('sales_receipts.print', compact('receipt'));
        })->middleware('permission:ar.receipts.view')->name('sales-receipts.print');
        Route::get('/{id}/pdf', [SalesReceiptController::class, 'pdf'])->middleware('permission:ar.receipts.view')->name('sales-receipts.pdf');
        Route::post('/{id}/queue-pdf', [SalesReceiptController::class, 'queuePdf'])->middleware('permission:ar.receipts.view')->name('sales-receipts.queuePdf');
    });

    // AP - Purchase Payments
    Route::prefix('purchase-payments')->group(function () {
        Route::get('/', [PurchasePaymentController::class, 'index'])->middleware('permission:ap.payments.view')->name('purchase-payments.index');
        Route::get('/create', [PurchasePaymentController::class, 'create'])->middleware('permission:ap.payments.create')->name('purchase-payments.create');
        Route::post('/', [PurchasePaymentController::class, 'store'])->middleware('permission:ap.payments.create')->name('purchase-payments.store');
        Route::get('/{id}', [PurchasePaymentController::class, 'show'])->middleware('permission:ap.payments.view')->name('purchase-payments.show');
        Route::post('/{id}/post', [PurchasePaymentController::class, 'post'])->middleware('permission:ap.payments.post')->name('purchase-payments.post');
        Route::get('/{id}/print', function ($id) {
            $payment = \App\Models\Accounting\PurchasePayment::with('lines')->findOrFail($id);
            return view('purchase_payments.print', compact('payment'));
        })->middleware('permission:ap.payments.view')->name('purchase-payments.print');
        Route::get('/{id}/pdf', [PurchasePaymentController::class, 'pdf'])->middleware('permission:ap.payments.view')->name('purchase-payments.pdf');
        Route::post('/{id}/queue-pdf', [PurchasePaymentController::class, 'queuePdf'])->middleware('permission:ap.payments.view')->name('purchase-payments.queuePdf');
    });
    // Admin - Users, Roles, Permissions
    Route::prefix('admin')->middleware(['permission:view-admin'])->group(function () {
        // Users
        Route::get('/users', [AdminUserController::class, 'index'])->name('admin.users.index');
        Route::get('/users/create', [AdminUserController::class, 'create'])->name('admin.users.create');
        Route::get('/users/{user}/edit', [AdminUserController::class, 'edit'])->name('admin.users.edit');
        Route::get('/users/data', [AdminUserController::class, 'data'])->name('admin.users.data');
        Route::post('/users', [AdminUserController::class, 'store'])->name('admin.users.store');
        Route::patch('/users/{user}', [AdminUserController::class, 'update'])->name('admin.users.update');
        Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('admin.users.destroy');
        Route::post('/users/{user}/roles', [AdminUserController::class, 'syncRoles'])->name('admin.users.syncRoles');

        // Roles
        Route::get('/roles', [AdminRoleController::class, 'index'])->name('admin.roles.index');
        Route::get('/roles/create', [AdminRoleController::class, 'create'])->name('admin.roles.create');
        Route::get('/roles/{role}/edit', [AdminRoleController::class, 'edit'])->name('admin.roles.edit');
        Route::get('/roles/data', [AdminRoleController::class, 'data'])->name('admin.roles.data');
        Route::post('/roles', [AdminRoleController::class, 'store'])->name('admin.roles.store');
        Route::patch('/roles/{role}', [AdminRoleController::class, 'update'])->name('admin.roles.update');
        Route::delete('/roles/{role}', [AdminRoleController::class, 'destroy'])->name('admin.roles.destroy');
        Route::post('/roles/{role}/permissions', [AdminRoleController::class, 'syncPermissions'])->name('admin.roles.syncPermissions');
        // Permissions
        Route::get('/permissions', [AdminPermissionController::class, 'index'])->name('admin.permissions.index');
        Route::get('/permissions/data', [AdminPermissionController::class, 'data'])->name('admin.permissions.data');
        Route::post('/permissions', [AdminPermissionController::class, 'store'])->name('admin.permissions.store');
        Route::patch('/permissions/{permission}', [AdminPermissionController::class, 'update'])->name('admin.permissions.update');
        Route::delete('/permissions/{permission}', [AdminPermissionController::class, 'destroy'])->name('admin.permissions.destroy');
    });
});

// Auth routes (AdminLTE views)
Route::middleware('guest')->group(function () {
    Route::get('login', [\App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [\App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'store']);
});

Route::post('logout', [\App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'destroy'])->name('logout');
