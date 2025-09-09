<?php

use App\Http\Controllers\Accounting\ManualJournalController;
use Illuminate\Support\Facades\Route;

Route::prefix('journals')->middleware(['permission:journals.view'])->group(function () {
    Route::get('/', [ManualJournalController::class, 'index'])->name('journals.index');
    Route::get('/manual/create', [ManualJournalController::class, 'create'])->middleware('permission:journals.create')->name('journals.manual.create');
    Route::post('/manual', [ManualJournalController::class, 'store'])->middleware('permission:journals.create')->name('journals.manual.store');
    Route::post('/{journal}/reverse', [ManualJournalController::class, 'reverse'])->middleware('permission:journals.reverse')->name('journals.reverse');
    Route::get('/data', [ManualJournalController::class, 'data'])->name('journals.data');
});
