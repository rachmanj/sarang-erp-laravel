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

Route::prefix('bank-reconciliation')->middleware(['permission:bank_reconciliation.view|bank_accounts.view'])->group(function () {
    Route::get('/', [BankReconciliationController::class, 'index'])->name('bank-reconciliation.index');
    Route::get('/sessions', [BankReconciliationController::class, 'sessions'])->name('bank-reconciliation.sessions');
    Route::get('/data', [BankReconciliationController::class, 'data'])->name('bank-reconciliation.data');
    Route::get('/koran/cell', [BankReconciliationController::class, 'koranCell'])->name('bank-reconciliation.koran.cell');
    Route::get('/create', [BankReconciliationController::class, 'create'])
        ->middleware('permission:bank_reconciliation.import')->name('bank-reconciliation.create');
    Route::post('/', [BankReconciliationController::class, 'store'])
        ->middleware('permission:bank_reconciliation.import')->name('bank-reconciliation.store');
    Route::get('/{bankReconciliation}', [BankReconciliationController::class, 'show'])->name('bank-reconciliation.show');
    Route::get('/{bankReconciliation}/status', [BankReconciliationController::class, 'status'])->name('bank-reconciliation.status');
    Route::get('/{bankReconciliation}/report', [BankReconciliationController::class, 'report'])->name('bank-reconciliation.report');
    Route::get('/{bankReconciliation}/export.csv', [BankReconciliationController::class, 'exportCsv'])->name('bank-reconciliation.export');
    Route::get('/{bankReconciliation}/statement-pdf', [BankReconciliationController::class, 'statementPdf'])->name('bank-reconciliation.statement-pdf');
    Route::get('/{bankReconciliation}/lines/{line}/suggestions', [BankReconciliationController::class, 'suggestions'])->name('bank-reconciliation.lines.suggestions');
    Route::post('/{bankReconciliation}/parse', [BankReconciliationController::class, 'parse'])
        ->middleware('permission:bank_reconciliation.reconcile')->name('bank-reconciliation.parse');
    Route::post('/{bankReconciliation}/fetch-book', [BankReconciliationController::class, 'fetchBook'])
        ->middleware('permission:bank_reconciliation.reconcile')->name('bank-reconciliation.fetch-book');
    Route::post('/{bankReconciliation}/auto-match', [BankReconciliationController::class, 'autoMatch'])
        ->middleware('permission:bank_reconciliation.reconcile')->name('bank-reconciliation.auto-match');
    Route::post('/{bankReconciliation}/match', [BankReconciliationController::class, 'match'])
        ->middleware('permission:bank_reconciliation.reconcile')->name('bank-reconciliation.match');
    Route::post('/{bankReconciliation}/unmatch/{group}', [BankReconciliationController::class, 'unmatch'])
        ->middleware('permission:bank_reconciliation.reconcile')->name('bank-reconciliation.unmatch');
    Route::post('/{bankReconciliation}/balances', [BankReconciliationController::class, 'updateBalances'])
        ->middleware('permission:bank_reconciliation.reconcile')->name('bank-reconciliation.balances');
    Route::post('/{bankReconciliation}/lines', [BankReconciliationController::class, 'storeLine'])
        ->middleware('permission:bank_reconciliation.reconcile')->name('bank-reconciliation.lines.store');
    Route::put('/{bankReconciliation}/lines/{line}', [BankReconciliationController::class, 'updateLine'])
        ->middleware('permission:bank_reconciliation.reconcile')->name('bank-reconciliation.lines.update');
    Route::delete('/{bankReconciliation}/lines/{line}', [BankReconciliationController::class, 'destroyLine'])
        ->middleware('permission:bank_reconciliation.reconcile')->name('bank-reconciliation.lines.destroy');
    Route::post('/{bankReconciliation}/lines/{line}/exclude', [BankReconciliationController::class, 'excludeBankLine'])
        ->middleware('permission:bank_reconciliation.reconcile')->name('bank-reconciliation.lines.exclude');
    Route::post('/{bankReconciliation}/lines/{line}/outstanding', [BankReconciliationController::class, 'outstandingBankLine'])
        ->middleware('permission:bank_reconciliation.reconcile')->name('bank-reconciliation.lines.outstanding');
    Route::post('/{bankReconciliation}/lines/{line}/adjust', [BankReconciliationController::class, 'postAdjustment'])
        ->middleware('permission:bank_reconciliation.reconcile')->name('bank-reconciliation.lines.adjust');
    Route::post('/{bankReconciliation}/book-lines/{bookLine}/exclude', [BankReconciliationController::class, 'excludeBookLine'])
        ->middleware('permission:bank_reconciliation.reconcile')->name('bank-reconciliation.book-lines.exclude');
    Route::post('/{bankReconciliation}/book-lines/{bookLine}/outstanding', [BankReconciliationController::class, 'outstandingBookLine'])
        ->middleware('permission:bank_reconciliation.reconcile')->name('bank-reconciliation.book-lines.outstanding');
    Route::post('/{bankReconciliation}/finalize', [BankReconciliationController::class, 'finalize'])
        ->middleware('permission:bank_reconciliation.finalize')->name('bank-reconciliation.finalize');
});
