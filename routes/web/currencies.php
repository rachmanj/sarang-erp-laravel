<?php

use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\ExchangeRateController;
use App\Http\Controllers\CurrencyRevaluationController;
use Illuminate\Support\Facades\Route;

// Currencies
Route::prefix('currencies')->group(function () {
    Route::get('/', [CurrencyController::class, 'index'])->name('currencies.index');
    Route::get('/data', [CurrencyController::class, 'data'])->name('currencies.data');
    Route::get('/create', [CurrencyController::class, 'create'])->name('currencies.create');
    Route::post('/', [CurrencyController::class, 'store'])->name('currencies.store');
    Route::get('/{id}', [CurrencyController::class, 'show'])->name('currencies.show');
    Route::get('/{id}/edit', [CurrencyController::class, 'edit'])->name('currencies.edit');
    Route::put('/{id}', [CurrencyController::class, 'update'])->name('currencies.update');
    Route::delete('/{id}', [CurrencyController::class, 'destroy'])->name('currencies.destroy');
});

// Exchange Rates
Route::prefix('exchange-rates')->group(function () {
    Route::get('/', [ExchangeRateController::class, 'index'])->name('exchange-rates.index');
    Route::get('/data', [ExchangeRateController::class, 'data'])->name('exchange-rates.data');
    Route::get('/daily-rates', [ExchangeRateController::class, 'dailyRates'])->name('exchange-rates.daily-rates');
    Route::post('/daily-rates', [ExchangeRateController::class, 'storeDailyRates'])->name('exchange-rates.store-daily-rates');
    Route::get('/create', [ExchangeRateController::class, 'create'])->name('exchange-rates.create');
    Route::post('/', [ExchangeRateController::class, 'store'])->name('exchange-rates.store');
    Route::get('/{id}', [ExchangeRateController::class, 'show'])->name('exchange-rates.show');
    Route::get('/{id}/edit', [ExchangeRateController::class, 'edit'])->name('exchange-rates.edit');
    Route::put('/{id}', [ExchangeRateController::class, 'update'])->name('exchange-rates.update');
    Route::delete('/{id}', [ExchangeRateController::class, 'destroy'])->name('exchange-rates.destroy');

    // API Routes
    Route::get('/api/rate', [ExchangeRateController::class, 'getRate'])->name('exchange-rates.api.rate');
});

// Currency Revaluations
Route::prefix('currency-revaluations')->group(function () {
    Route::get('/', [CurrencyRevaluationController::class, 'index'])->name('currency-revaluations.index');
    Route::get('/data', [CurrencyRevaluationController::class, 'data'])->name('currency-revaluations.data');
    Route::get('/create', [CurrencyRevaluationController::class, 'create'])->name('currency-revaluations.create');
    Route::post('/calculate', [CurrencyRevaluationController::class, 'calculate'])->name('currency-revaluations.calculate');
    Route::post('/', [CurrencyRevaluationController::class, 'store'])->name('currency-revaluations.store');
    Route::get('/{id}', [CurrencyRevaluationController::class, 'show'])->name('currency-revaluations.show');
    Route::get('/{id}/preview', [CurrencyRevaluationController::class, 'preview'])->name('currency-revaluations.preview');
    Route::post('/{id}/post', [CurrencyRevaluationController::class, 'post'])->name('currency-revaluations.post');
    Route::post('/{id}/reverse', [CurrencyRevaluationController::class, 'reverse'])->name('currency-revaluations.reverse');
});
