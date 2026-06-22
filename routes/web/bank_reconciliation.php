<?php

use App\Http\Controllers\Accounting\BankAccountController;
use App\Http\Controllers\Accounting\BankReconciliationController;
use Illuminate\Support\Facades\Route;

Route::prefix('bank-accounts')->middleware(['permission:bank_accounts.view'])->group(function () {
    Route::get('/', [BankAccountController::class, 'index'])->name('bank-accounts.index');
    Route::get('/data', [BankAccountController::class, 'data'])->name('bank-accounts.data');
    Route::get('/create', [BankAccountController::class, 'create'])
        ->middleware('permission:bank_accounts.manage')->name('bank-accounts.create');
    Route::post('/', [BankAccountController::class, 'store'])
        ->middleware('permission:bank_accounts.manage')->name('bank-accounts.store');
    Route::get('/{bankAccount}/edit', [BankAccountController::class, 'edit'])
        ->middleware('permission:bank_accounts.manage')->name('bank-accounts.edit');
    Route::put('/{bankAccount}', [BankAccountController::class, 'update'])
        ->middleware('permission:bank_accounts.manage')->name('bank-accounts.update');
});

Route::prefix('bank-reconciliation')->middleware(['permission:bank_reconciliation.view'])->group(function () {
    Route::get('/', [BankReconciliationController::class, 'index'])->name('bank-reconciliation.index');
    Route::get('/data', [BankReconciliationController::class, 'data'])->name('bank-reconciliation.data');
    Route::get('/import', [BankReconciliationController::class, 'importForm'])
        ->middleware('permission:bank_reconciliation.import')->name('bank-reconciliation.import');
    Route::post('/import', [BankReconciliationController::class, 'import'])
        ->middleware('permission:bank_reconciliation.import')->name('bank-reconciliation.import.store');
    Route::get('/{bankReconciliation}', [BankReconciliationController::class, 'show'])->name('bank-reconciliation.show');
    Route::get('/{bankReconciliation}/book-data', [BankReconciliationController::class, 'bookData'])->name('bank-reconciliation.book-data');
    Route::post('/{bankReconciliation}/auto-match', [BankReconciliationController::class, 'autoMatch'])
        ->middleware('permission:bank_reconciliation.reconcile')->name('bank-reconciliation.auto-match');
    Route::post('/{bankReconciliation}/ai-match', [BankReconciliationController::class, 'aiMatch'])
        ->middleware('permission:bank_reconciliation.reconcile')->name('bank-reconciliation.ai-match');
    Route::post('/{bankReconciliation}/manual-match', [BankReconciliationController::class, 'manualMatch'])
        ->middleware('permission:bank_reconciliation.reconcile')->name('bank-reconciliation.manual-match');
    Route::post('/{bankReconciliation}/adjustment', [BankReconciliationController::class, 'adjustment'])
        ->middleware('permission:bank_reconciliation.reconcile')->name('bank-reconciliation.adjustment');
    Route::post('/{bankReconciliation}/ignore-line', [BankReconciliationController::class, 'ignoreLine'])
        ->middleware('permission:bank_reconciliation.reconcile')->name('bank-reconciliation.ignore-line');
    Route::post('/{bankReconciliation}/finalize', [BankReconciliationController::class, 'finalize'])
        ->middleware('permission:bank_reconciliation.finalize')->name('bank-reconciliation.finalize');
});
