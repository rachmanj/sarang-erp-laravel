<?php

use App\Http\Controllers\Reports\ReportsController;
use App\Http\Controllers\Reports\AssetReportsController;
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

    // Asset Reports
    Route::prefix('assets')->group(function () {
        Route::get('/', [AssetReportsController::class, 'index'])->name('reports.assets.index');
        Route::get('/register', [AssetReportsController::class, 'assetRegister'])->name('reports.assets.register');
        Route::get('/depreciation-schedule', [AssetReportsController::class, 'depreciationSchedule'])->name('reports.assets.depreciation-schedule');
        Route::get('/disposal-summary', [AssetReportsController::class, 'disposalSummary'])->name('reports.assets.disposal-summary');
        Route::get('/movement-log', [AssetReportsController::class, 'movementLog'])->name('reports.assets.movement-log');
        Route::get('/summary', [AssetReportsController::class, 'summary'])->name('reports.assets.summary');
        Route::get('/aging', [AssetReportsController::class, 'assetAging'])->name('reports.assets.aging');
        Route::get('/low-value', [AssetReportsController::class, 'lowValueAssets'])->name('reports.assets.low-value');
        Route::get('/depreciation-history', [AssetReportsController::class, 'depreciationRunHistory'])->name('reports.assets.depreciation-history');
        Route::post('/data', [AssetReportsController::class, 'getReportData'])->name('reports.assets.data');
    });
});
