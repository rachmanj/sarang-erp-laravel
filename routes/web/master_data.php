<?php

use App\Http\Controllers\UnitOfMeasureController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\Dimensions\ProjectController as DimProjectController;
use App\Http\Controllers\Dimensions\DepartmentController as DimDepartmentController;
use App\Http\Controllers\ProductCategoryController;
use App\Http\Controllers\WarehouseController;
use Illuminate\Support\Facades\Route;

// Master Data Routes
Route::prefix('master-data')->name('master-data.')->middleware(['auth', 'permission:view_master_data'])->group(function () {

    // Product Categories
    Route::resource('product-categories', ProductCategoryController::class);

    // Warehouses
    Route::resource('warehouses', WarehouseController::class);

    // Dimensions
    Route::prefix('dimensions')->name('dimensions.')->group(function () {
        Route::resource('projects', DimProjectController::class);
        Route::resource('departments', DimDepartmentController::class);
    });
});

// Unit of Measure Routes
Route::prefix('unit-of-measures')->name('unit-of-measures.')->middleware(['auth'])->group(function () {
    Route::get('/', [UnitOfMeasureController::class, 'index'])->name('index');
    Route::get('/create', [UnitOfMeasureController::class, 'create'])->name('create');
    Route::post('/', [UnitOfMeasureController::class, 'store'])->name('store');
    Route::get('/{unitOfMeasure}', [UnitOfMeasureController::class, 'show'])->name('show');
    Route::get('/{unitOfMeasure}/edit', [UnitOfMeasureController::class, 'edit'])->name('edit');
    Route::put('/{unitOfMeasure}', [UnitOfMeasureController::class, 'update'])->name('update');
    Route::delete('/{unitOfMeasure}', [UnitOfMeasureController::class, 'destroy'])->name('destroy');

    // API Routes for AJAX
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/item-units', [UnitOfMeasureController::class, 'getItemUnits'])->name('item-units');
            Route::post('/store', [UnitOfMeasureController::class, 'storeAjax'])->name('store');
    });
});

// Inventory Items with Unit Management
Route::prefix('inventory-items')->name('inventory-items.')->middleware(['auth'])->group(function () {
    Route::get('/', [InventoryController::class, 'index'])->name('index');
    Route::get('/create', [InventoryController::class, 'create'])->name('create');
    Route::post('/', [InventoryController::class, 'store'])->name('store');
    Route::get('/{inventoryItem}', [InventoryController::class, 'show'])->name('show');
    Route::get('/{inventoryItem}/edit', [InventoryController::class, 'edit'])->name('edit');
    Route::put('/{inventoryItem}', [InventoryController::class, 'update'])->name('update');
    Route::delete('/{inventoryItem}', [InventoryController::class, 'destroy'])->name('destroy');

    // Unit Management for Items
    Route::prefix('{inventoryItem}/units')->name('units.')->group(function () {
        Route::get('/', [InventoryController::class, 'manageUnits'])->name('index');
        Route::post('/', [InventoryController::class, 'addUnit'])->name('store');
        Route::put('/{itemUnit}', [InventoryController::class, 'updateUnit'])->name('update');
        Route::delete('/{itemUnit}', [InventoryController::class, 'removeUnit'])->name('destroy');
        Route::post('/set-base', [InventoryController::class, 'setBaseUnit'])->name('set-base');
    });
});
