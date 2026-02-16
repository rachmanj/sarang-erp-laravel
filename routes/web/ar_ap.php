<?php

use App\Http\Controllers\Accounting\SalesInvoiceController;
use App\Http\Controllers\Accounting\SalesInvoiceImportController;
use App\Http\Controllers\Accounting\PurchaseInvoiceController;
use App\Http\Controllers\Accounting\SalesReceiptController;
use App\Http\Controllers\Accounting\PurchasePaymentController;
use Illuminate\Support\Facades\Route;

// AR - Sales Invoices
Route::prefix('sales-invoices')->group(function () {
    Route::get('/', [SalesInvoiceController::class, 'index'])->middleware('permission:ar.invoices.view')->name('sales-invoices.index');
    Route::get('/data', [SalesInvoiceController::class, 'data'])->middleware('permission:ar.invoices.view')->name('sales-invoices.data');
    Route::get('/create', [SalesInvoiceController::class, 'create'])->middleware('permission:ar.invoices.create')->name('sales-invoices.create');
    Route::get('/api/document-number', [SalesInvoiceController::class, 'getDocumentNumber'])->middleware('permission:ar.invoices.create')->name('sales-invoices.api.document-number');
    Route::post('/', [SalesInvoiceController::class, 'store'])->middleware('permission:ar.invoices.create')->name('sales-invoices.store');
    
    // Import routes - must come before /{id} route
    Route::prefix('import')->group(function () {
        Route::get('/', [SalesInvoiceImportController::class, 'index'])->name('sales-invoices.import.index');
        Route::get('/template', [SalesInvoiceImportController::class, 'template'])->name('sales-invoices.import.template');
        Route::post('/validate', [SalesInvoiceImportController::class, 'validateImport'])->name('sales-invoices.import.validate');
        Route::post('/', [SalesInvoiceImportController::class, 'import'])->name('sales-invoices.import.store');
    });
    
    Route::get('/{id}', [SalesInvoiceController::class, 'show'])->middleware('permission:ar.invoices.view')->name('sales-invoices.show');
    Route::get('/{id}/edit', [SalesInvoiceController::class, 'edit'])->middleware('permission:ar.invoices.create')->name('sales-invoices.edit');
    Route::put('/{id}', [SalesInvoiceController::class, 'update'])->middleware('permission:ar.invoices.create')->name('sales-invoices.update');
    Route::delete('/{id}', [SalesInvoiceController::class, 'destroy'])->middleware('permission:ar.invoices.create')->name('sales-invoices.destroy');
    Route::post('/{id}/post', [SalesInvoiceController::class, 'post'])->middleware('permission:ar.invoices.post')->name('sales-invoices.post');
    Route::get('/{id}/print', function ($id) {
        $invoice = \App\Models\Accounting\SalesInvoice::with(['lines', 'lines.account', 'lines.taxCode', 'lines.inventoryItem', 'businessPartner', 'businessPartner.primaryAddress', 'companyEntity', 'deliveryOrders'])->findOrFail($id);
        return view('sales_invoices.print', compact('invoice'));
    })->middleware('permission:ar.invoices.view')->name('sales-invoices.print');
    Route::get('/{id}/pdf', [SalesInvoiceController::class, 'pdf'])->middleware('permission:ar.invoices.view')->name('sales-invoices.pdf');
    Route::post('/{id}/queue-pdf', [SalesInvoiceController::class, 'queuePdf'])->middleware('permission:ar.invoices.view')->name('sales-invoices.queuePdf');
});

// AP - Purchase Invoices
Route::prefix('purchase-invoices')->group(function () {
    Route::get('/', [PurchaseInvoiceController::class, 'index'])->middleware('permission:ap.invoices.view')->name('purchase-invoices.index');
    Route::get('/data', [PurchaseInvoiceController::class, 'data'])->middleware('permission:ap.invoices.view')->name('purchase-invoices.data');
    Route::get('/create', [PurchaseInvoiceController::class, 'create'])->middleware('permission:ap.invoices.create')->name('purchase-invoices.create');
    Route::get('/api/document-number', [PurchaseInvoiceController::class, 'getDocumentNumber'])->middleware('permission:ap.invoices.create')->name('purchase-invoices.api.document-number');
    Route::post('/', [PurchaseInvoiceController::class, 'store'])->middleware('permission:ap.invoices.create')->name('purchase-invoices.store');
    Route::get('/{id}', [PurchaseInvoiceController::class, 'show'])->middleware('permission:ap.invoices.view')->name('purchase-invoices.show');
    Route::get('/{id}/edit', [PurchaseInvoiceController::class, 'edit'])->middleware('permission:ap.invoices.create')->name('purchase-invoices.edit');
    Route::put('/{id}', [PurchaseInvoiceController::class, 'update'])->middleware('permission:ap.invoices.create')->name('purchase-invoices.update');
    Route::post('/{id}/post', [PurchaseInvoiceController::class, 'post'])->middleware('permission:ap.invoices.post')->name('purchase-invoices.post');
    Route::post('/{id}/unpost', [PurchaseInvoiceController::class, 'unpost'])->middleware('permission:ap.invoices.post')->name('purchase-invoices.unpost');
    Route::get('/{id}/print', [PurchaseInvoiceController::class, 'print'])->middleware('permission:ap.invoices.view')->name('purchase-invoices.print');
    Route::get('/{id}/pdf', [PurchaseInvoiceController::class, 'pdf'])->middleware('permission:ap.invoices.view')->name('purchase-invoices.pdf');
    Route::post('/{id}/queue-pdf', [PurchaseInvoiceController::class, 'queuePdf'])->middleware('permission:ap.invoices.view')->name('purchase-invoices.queuePdf');
});

// AR - Sales Receipts
Route::prefix('sales-receipts')->group(function () {
    Route::get('/', [SalesReceiptController::class, 'index'])->middleware('permission:ar.receipts.view')->name('sales-receipts.index');
    Route::get('/preview-allocation', [SalesReceiptController::class, 'previewAllocation'])->middleware('permission:ar.receipts.create')->name('sales-receipts.previewAllocation');
    Route::get('/data', [SalesReceiptController::class, 'data'])->middleware('permission:ar.receipts.view')->name('sales-receipts.data');
    Route::get('/create', [SalesReceiptController::class, 'create'])->middleware('permission:ar.receipts.create')->name('sales-receipts.create');
    Route::get('/api/document-number', [SalesReceiptController::class, 'getDocumentNumber'])->middleware('permission:ar.receipts.create')->name('sales-receipts.api.document-number');
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
    Route::get('/available-invoices', [PurchasePaymentController::class, 'getAvailableInvoices'])->middleware('permission:ap.payments.create')->name('purchase-payments.availableInvoices');
    Route::get('/preview-allocation', [PurchasePaymentController::class, 'previewAllocation'])->middleware('permission:ap.payments.create')->name('purchase-payments.previewAllocation');
    Route::get('/data', [PurchasePaymentController::class, 'data'])->middleware('permission:ap.payments.view')->name('purchase-payments.data');
    Route::get('/create', [PurchasePaymentController::class, 'create'])->middleware('permission:ap.payments.create')->name('purchase-payments.create');
    Route::get('/api/document-number', [PurchasePaymentController::class, 'getDocumentNumber'])->middleware('permission:ap.payments.create')->name('purchase-payments.api.document-number');
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
