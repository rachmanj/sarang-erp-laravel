<?php

use App\Http\Controllers\Reports\ReportsController;
use Illuminate\Support\Facades\Route;

Route::prefix('reports')->group(function () {
    Route::get('/trial-balance', [ReportsController::class, 'trialBalance'])->name('reports.trial-balance');
    Route::get('/gl-detail', [ReportsController::class, 'glDetail'])->name('reports.gl-detail');
    Route::get('/ar-aging', [ReportsController::class, 'arAging'])->name('reports.ar-aging');
    Route::get('/ap-aging', [ReportsController::class, 'apAging'])->name('reports.ap-aging');
    Route::get('/cash-ledger', [ReportsController::class, 'cashLedger'])->name('reports.cash-ledger');
    Route::get('/withholding-recap', [ReportsController::class, 'withholdingRecap'])->name('reports.withholding-recap');
    Route::get('/ar-balances', [ReportsController::class, 'arBalances'])->name('reports.ar-balances');
    Route::get('/ap-balances', [ReportsController::class, 'apBalances'])->name('reports.ap-balances');
});
