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
use App\Http\Controllers\Accounting\AccountController;
use App\Http\Controllers\Accounting\CashExpenseController;
use App\Http\Controllers\ControlAccountController;
use App\Http\Controllers\BusinessPartnerController;
use App\Http\Controllers\Dimensions\ProjectController as DimProjectController;
use App\Http\Controllers\Dimensions\DepartmentController as DimDepartmentController;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\SalesOrderController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\GoodsReceiptPOController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\AssetCategoryController;
use App\Http\Controllers\AssetDepreciationController;
use App\Http\Controllers\AssetDisposalController;
use App\Http\Controllers\AssetMovementController;
use App\Http\Controllers\AssetImportController;
use App\Http\Controllers\AssetDataQualityController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ProductCategoryController;
use App\Http\Controllers\TaxController;
use App\Http\Controllers\COGSController;
use App\Http\Controllers\SupplierAnalyticsController;
use App\Http\Controllers\BusinessIntelligenceController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\Accounting\AccountStatementController;
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
    Route::put('password', [PasswordController::class, 'update'])->name('password.update');
    Route::get('/change-password', [ProfileController::class, 'changePassword'])->name('profile.change-password');

    require __DIR__ . '/web/reports.php';

    Route::prefix('dev')->group(function () {
        Route::post('/post-journal', [PostingDemoController::class, 'store'])->name('dev.post-journal');
    });

    require __DIR__ . '/web/journals.php';

    require __DIR__ . '/web/orders.php';

    // Periods
    Route::prefix('periods')->group(function () {
        Route::get('/', [PeriodController::class, 'index'])->middleware('permission:periods.view')->name('periods.index');
        Route::post('/close', [PeriodController::class, 'close'])->middleware('permission:periods.close')->name('periods.close');
        Route::post('/open', [PeriodController::class, 'open'])->middleware('permission:periods.close')->name('periods.open');
    });

    require __DIR__ . '/web/orders.php';
    require __DIR__ . '/web/ar_ap.php';

    // Business Partners (Unified Customers & Suppliers)
    Route::prefix('business-partners')->group(function () {
        Route::get('/', [BusinessPartnerController::class, 'index'])->name('business_partners.index');
        Route::get('/data', [BusinessPartnerController::class, 'data'])->name('business_partners.data');
        Route::get('/create', [BusinessPartnerController::class, 'create'])->name('business_partners.create');
        Route::post('/', [BusinessPartnerController::class, 'store'])->name('business_partners.store');
        Route::get('/{businessPartner}', [BusinessPartnerController::class, 'show'])->name('business_partners.show');
        Route::get('/{businessPartner}/edit', [BusinessPartnerController::class, 'edit'])->name('business_partners.edit');
        Route::put('/{businessPartner}', [BusinessPartnerController::class, 'update'])->name('business_partners.update');
        Route::delete('/{businessPartner}', [BusinessPartnerController::class, 'destroy'])->name('business_partners.destroy');
        Route::get('/search', [BusinessPartnerController::class, 'search'])->name('business_partners.search');
        Route::get('/by-type', [BusinessPartnerController::class, 'getByType'])->name('business_partners.by-type');
        Route::get('/{businessPartner}/journal-history', [BusinessPartnerController::class, 'journalHistory'])->name('business_partners.journal_history');
    });

    // Accounts
    Route::prefix('accounts')->group(function () {
        Route::get('/', [AccountController::class, 'index'])->name('accounts.index');
        Route::get('/create', [AccountController::class, 'create'])->name('accounts.create');
        Route::post('/', [AccountController::class, 'store'])->name('accounts.store');
        Route::get('/{account}/edit', [AccountController::class, 'edit'])->name('accounts.edit');
        Route::patch('/{account}', [AccountController::class, 'update'])->name('accounts.update');
    });

    // Cash Expenses
    Route::prefix('cash-expenses')->group(function () {
        Route::get('/', [CashExpenseController::class, 'index'])->name('cash-expenses.index');
        Route::get('/data', [CashExpenseController::class, 'data'])->name('cash-expenses.data');
        Route::get('/create', [CashExpenseController::class, 'create'])->name('cash-expenses.create');
        Route::post('/', [CashExpenseController::class, 'store'])->name('cash-expenses.store');
        Route::get('/{cashExpense}/print', [CashExpenseController::class, 'print'])->name('cash-expenses.print');
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

    // ERP Parameters
    Route::prefix('erp-parameters')->middleware(['permission:manage-erp-parameters'])->group(function () {
        Route::get('/', [App\Http\Controllers\ErpParameterController::class, 'index'])->name('erp-parameters.index');
        Route::get('/create', [App\Http\Controllers\ErpParameterController::class, 'create'])->name('erp-parameters.create');
        Route::post('/', [App\Http\Controllers\ErpParameterController::class, 'store'])->name('erp-parameters.store');
        Route::get('/{erpParameter}', [App\Http\Controllers\ErpParameterController::class, 'show'])->name('erp-parameters.show');
        Route::get('/{erpParameter}/edit', [App\Http\Controllers\ErpParameterController::class, 'edit'])->name('erp-parameters.edit');
        Route::patch('/{erpParameter}', [App\Http\Controllers\ErpParameterController::class, 'update'])->name('erp-parameters.update');
        Route::delete('/{erpParameter}', [App\Http\Controllers\ErpParameterController::class, 'destroy'])->name('erp-parameters.destroy');
        Route::get('/by-category', [App\Http\Controllers\ErpParameterController::class, 'getByCategory'])->name('erp-parameters.by-category');
        Route::post('/bulk-update', [App\Http\Controllers\ErpParameterController::class, 'bulkUpdate'])->name('erp-parameters.bulk-update');
    });

    // Downloads
    Route::get('/downloads', [DownloadController::class, 'index'])->middleware('permission:reports.view')->name('downloads.index');

    // Dimensions Management
    Route::prefix('projects')->middleware(['permission:projects.view'])->group(function () {
        Route::get('/', [DimProjectController::class, 'index'])->name('projects.index');
        Route::get('/data', [DimProjectController::class, 'data'])->name('projects.data');
        Route::post('/', [DimProjectController::class, 'store'])->middleware('permission:projects.manage')->name('projects.store');
        Route::patch('/{id}', [DimProjectController::class, 'update'])->middleware('permission:projects.manage')->name('projects.update');
        Route::delete('/{id}', [DimProjectController::class, 'destroy'])->middleware('permission:projects.manage')->name('projects.destroy');
    });
    Route::prefix('departments')->middleware(['permission:departments.view'])->group(function () {
        Route::get('/', [DimDepartmentController::class, 'index'])->name('departments.index');
        Route::get('/data', [DimDepartmentController::class, 'data'])->name('departments.data');
        Route::post('/', [DimDepartmentController::class, 'store'])->middleware('permission:departments.manage')->name('departments.store');
        Route::patch('/{id}', [DimDepartmentController::class, 'update'])->middleware('permission:departments.manage')->name('departments.update');
        Route::delete('/{id}', [DimDepartmentController::class, 'destroy'])->middleware('permission:departments.manage')->name('departments.destroy');
    });

    // Fixed Assets Management
    Route::prefix('asset-categories')->middleware(['permission:asset_categories.view'])->group(function () {
        Route::get('/', [AssetCategoryController::class, 'index'])->name('asset-categories.index');
        Route::get('/data', [AssetCategoryController::class, 'data'])->name('asset-categories.data');
        Route::get('/accounts', [AssetCategoryController::class, 'getAccounts'])->name('asset-categories.accounts');
        Route::post('/', [AssetCategoryController::class, 'store'])->middleware('permission:asset_categories.manage')->name('asset-categories.store');
        Route::patch('/{assetCategory}', [AssetCategoryController::class, 'update'])->middleware('permission:asset_categories.manage')->name('asset-categories.update');
        Route::delete('/{assetCategory}', [AssetCategoryController::class, 'destroy'])->middleware('permission:asset_categories.manage')->name('asset-categories.destroy');
    });

    Route::prefix('assets')->middleware(['permission:assets.view'])->group(function () {
        Route::get('/', [AssetController::class, 'index'])->name('assets.index');
        Route::get('/data', [AssetController::class, 'data'])->name('assets.data');
        Route::get('/create', [AssetController::class, 'create'])->middleware('permission:assets.create')->name('assets.create');
        Route::post('/', [AssetController::class, 'store'])->middleware('permission:assets.create')->name('assets.store');
        Route::get('/{asset}', [AssetController::class, 'show'])->name('assets.show');
        Route::get('/{asset}/edit', [AssetController::class, 'edit'])->middleware('permission:assets.update')->name('assets.edit');
        Route::patch('/{asset}', [AssetController::class, 'update'])->middleware('permission:assets.update')->name('assets.update');
        Route::delete('/{asset}', [AssetController::class, 'destroy'])->middleware('permission:assets.delete')->name('assets.destroy');
        Route::get('/categories', [AssetController::class, 'getCategories'])->name('assets.categories');
        Route::get('/projects', [AssetController::class, 'getProjects'])->name('assets.projects');
        Route::get('/departments', [AssetController::class, 'getDepartments'])->name('assets.departments');
        Route::get('/vendors', [AssetController::class, 'getVendors'])->name('assets.vendors');

        // Asset Import Routes
        Route::prefix('import')->middleware(['permission:assets.create'])->group(function () {
            Route::get('/', [AssetImportController::class, 'index'])->name('assets.import.index');
            Route::get('/template', [AssetImportController::class, 'template'])->name('assets.import.template');
            Route::post('/validate', [AssetImportController::class, 'validate'])->name('assets.import.validate');
            Route::post('/import', [AssetImportController::class, 'import'])->name('assets.import.import');
            Route::get('/reference-data', [AssetImportController::class, 'getReferenceData'])->name('assets.import.reference-data');
            Route::post('/bulk-update', [AssetImportController::class, 'bulkUpdate'])->middleware('permission:assets.update')->name('assets.import.bulk-update');
        });

        // Asset Data Quality Routes
        Route::prefix('data-quality')->middleware(['permission:assets.view'])->group(function () {
            Route::get('/', [AssetDataQualityController::class, 'index'])->name('assets.data-quality.index');
            Route::get('/duplicates', [AssetDataQualityController::class, 'duplicates'])->name('assets.data-quality.duplicates');
            Route::get('/incomplete', [AssetDataQualityController::class, 'incomplete'])->name('assets.data-quality.incomplete');
            Route::get('/consistency', [AssetDataQualityController::class, 'consistency'])->name('assets.data-quality.consistency');
            Route::get('/orphaned', [AssetDataQualityController::class, 'orphaned'])->name('assets.data-quality.orphaned');
            Route::get('/export', [AssetDataQualityController::class, 'exportReport'])->name('assets.data-quality.export');
            Route::get('/score', [AssetDataQualityController::class, 'getDataQualityScore'])->name('assets.data-quality.score');
            Route::post('/duplicate-details', [AssetDataQualityController::class, 'getDuplicateDetails'])->name('assets.data-quality.duplicate-details');
            Route::post('/assets-by-issue', [AssetDataQualityController::class, 'getAssetsByIssue'])->name('assets.data-quality.assets-by-issue');
        });

        // Asset Bulk Operations Routes
        Route::prefix('bulk-operations')->middleware(['permission:assets.update'])->group(function () {
            Route::get('/', [AssetController::class, 'bulkUpdateIndex'])->name('assets.bulk-operations.index');
            Route::get('/data', [AssetController::class, 'bulkUpdateData'])->name('assets.bulk-update.data');
            Route::post('/preview', [AssetController::class, 'bulkUpdatePreview'])->name('assets.bulk-update.preview');
            Route::post('/update', [AssetController::class, 'bulkUpdate'])->name('assets.bulk-update');
        });
    });

    // Fixed Assets Depreciation
    Route::prefix('assets/depreciation')->middleware(['permission:assets.depreciation.run'])->group(function () {
        Route::get('/', [AssetDepreciationController::class, 'index'])->name('assets.depreciation.index');
        Route::get('/data', [AssetDepreciationController::class, 'data'])->name('assets.depreciation.data');
        Route::get('/create', [AssetDepreciationController::class, 'create'])->name('assets.depreciation.create');
        Route::post('/', [AssetDepreciationController::class, 'store'])->name('assets.depreciation.store');
        Route::get('/{run}', [AssetDepreciationController::class, 'show'])->name('assets.depreciation.show');
        Route::get('/{run}/calculate', [AssetDepreciationController::class, 'calculate'])->name('assets.depreciation.calculate');
        Route::post('/{run}/entries', [AssetDepreciationController::class, 'createEntries'])->name('assets.depreciation.createEntries');
        Route::post('/{run}/post', [AssetDepreciationController::class, 'post'])->name('assets.depreciation.post');
        Route::post('/{run}/reverse', [AssetDepreciationController::class, 'reverse'])->name('assets.depreciation.reverse');
        Route::get('/{run}/entries', [AssetDepreciationController::class, 'entries'])->name('assets.depreciation.entries');
    });

    // Asset depreciation schedule
    Route::get('/assets/{asset}/schedule', [AssetDepreciationController::class, 'schedule'])->middleware(['permission:assets.view'])->name('assets.schedule');

    // Asset Disposals
    Route::prefix('assets/disposals')->middleware(['permission:assets.disposal.view'])->group(function () {
        Route::get('/', [AssetDisposalController::class, 'index'])->name('assets.disposals.index');
        Route::get('/data', [AssetDisposalController::class, 'data'])->name('assets.disposals.data');
        Route::get('/create', [AssetDisposalController::class, 'create'])->middleware('permission:assets.disposal.create')->name('assets.disposals.create');
        Route::post('/', [AssetDisposalController::class, 'store'])->middleware('permission:assets.disposal.create')->name('assets.disposals.store');
        Route::get('/{disposal}', [AssetDisposalController::class, 'show'])->name('assets.disposals.show');
        Route::get('/{disposal}/edit', [AssetDisposalController::class, 'edit'])->middleware('permission:assets.disposal.update')->name('assets.disposals.edit');
        Route::patch('/{disposal}', [AssetDisposalController::class, 'update'])->middleware('permission:assets.disposal.update')->name('assets.disposals.update');
        Route::delete('/{disposal}', [AssetDisposalController::class, 'destroy'])->middleware('permission:assets.disposal.delete')->name('assets.disposals.destroy');
        Route::post('/{disposal}/post', [AssetDisposalController::class, 'post'])->middleware('permission:assets.disposal.post')->name('assets.disposals.post');
        Route::post('/{disposal}/reverse', [AssetDisposalController::class, 'reverse'])->middleware('permission:assets.disposal.reverse')->name('assets.disposals.reverse');
    });

    // Asset Movements
    Route::prefix('assets/movements')->middleware(['permission:assets.movement.view'])->group(function () {
        Route::get('/', [AssetMovementController::class, 'index'])->name('assets.movements.index');
        Route::get('/data', [AssetMovementController::class, 'data'])->name('assets.movements.data');
        Route::get('/create', [AssetMovementController::class, 'create'])->middleware('permission:assets.movement.create')->name('assets.movements.create');
        Route::post('/', [AssetMovementController::class, 'store'])->middleware('permission:assets.movement.create')->name('assets.movements.store');
        Route::get('/{movement}', [AssetMovementController::class, 'show'])->name('assets.movements.show');
        Route::get('/{movement}/edit', [AssetMovementController::class, 'edit'])->middleware('permission:assets.movement.update')->name('assets.movements.edit');
        Route::patch('/{movement}', [AssetMovementController::class, 'update'])->middleware('permission:assets.movement.update')->name('assets.movements.update');
        Route::delete('/{movement}', [AssetMovementController::class, 'destroy'])->middleware('permission:assets.movement.delete')->name('assets.movements.destroy');
        Route::post('/{movement}/approve', [AssetMovementController::class, 'approve'])->middleware('permission:assets.movement.approve')->name('assets.movements.approve');
        Route::post('/{movement}/complete', [AssetMovementController::class, 'complete'])->middleware('permission:assets.movement.update')->name('assets.movements.complete');
        Route::post('/{movement}/cancel', [AssetMovementController::class, 'cancel'])->middleware('permission:assets.movement.update')->name('assets.movements.cancel');
        Route::get('/asset/{asset}/history', [AssetMovementController::class, 'assetMovements'])->name('assets.movements.history');
    });

    // Inventory Management
    Route::prefix('inventory')->middleware(['permission:inventory.view'])->group(function () {
        Route::get('/', [InventoryController::class, 'index'])->name('inventory.index');
        Route::get('/data', [InventoryController::class, 'data'])->name('inventory.data');
        Route::get('/create', [InventoryController::class, 'create'])->middleware('permission:inventory.create')->name('inventory.create');
        Route::post('/', [InventoryController::class, 'store'])->middleware('permission:inventory.create')->name('inventory.store');
        Route::get('/{item}', [InventoryController::class, 'show'])->name('inventory.show');
        Route::get('/{item}/edit', [InventoryController::class, 'edit'])->middleware('permission:inventory.update')->name('inventory.edit');
        Route::patch('/{item}', [InventoryController::class, 'update'])->middleware('permission:inventory.update')->name('inventory.update');
        Route::delete('/{item}', [InventoryController::class, 'destroy'])->middleware('permission:inventory.delete')->name('inventory.destroy');

        // Stock Management
        Route::post('/{item}/adjust-stock', [InventoryController::class, 'adjustStock'])->middleware('permission:inventory.adjust')->name('inventory.adjust-stock');
        Route::post('/{item}/transfer-stock', [InventoryController::class, 'transferStock'])->middleware('permission:inventory.transfer')->name('inventory.transfer-stock');

        // Reports and Analytics
        Route::get('/low-stock', [InventoryController::class, 'lowStock'])->name('inventory.low-stock');
        Route::get('/valuation-report', [InventoryController::class, 'valuationReport'])->name('inventory.valuation-report');

        // API Endpoints
        Route::get('/api/items', [InventoryController::class, 'getItems'])->name('inventory.get-items');
        Route::get('/api/items/{id}', [InventoryController::class, 'getItemDetails'])->name('inventory.get-item-details');
        Route::get('/api/search', [InventoryController::class, 'search'])->name('inventory.search');

        // Export Functions
        Route::get('/export', [InventoryController::class, 'export'])->name('inventory.export');
        Route::get('/export-low-stock', [InventoryController::class, 'exportLowStock'])->name('inventory.export-low-stock');
        Route::get('/export-valuation', [InventoryController::class, 'exportValuation'])->name('inventory.export-valuation');

        // Price Level Management
        Route::get('/{item}/effective-price', [InventoryController::class, 'getEffectivePrice'])->name('inventory.get-effective-price');
        Route::post('/set-customer-price-level', [InventoryController::class, 'setCustomerPriceLevel'])->middleware('permission:inventory.update')->name('inventory.set-customer-price-level');
        Route::get('/{item}/price-level-summary', [InventoryController::class, 'getPriceLevelSummary'])->name('inventory.get-price-level-summary');
    });

    // Product Category Management
    Route::prefix('product-categories')->middleware(['permission:inventory.view'])->group(function () {
        Route::get('/', [ProductCategoryController::class, 'index'])->name('product-categories.index');
        Route::get('/create', [ProductCategoryController::class, 'create'])->middleware('permission:inventory.create')->name('product-categories.create');
        Route::post('/', [ProductCategoryController::class, 'store'])->middleware('permission:inventory.create')->name('product-categories.store');
        Route::get('/{productCategory}', [ProductCategoryController::class, 'show'])->name('product-categories.show');
        Route::get('/{productCategory}/edit', [ProductCategoryController::class, 'edit'])->middleware('permission:inventory.update')->name('product-categories.edit');
        Route::patch('/{productCategory}', [ProductCategoryController::class, 'update'])->middleware('permission:inventory.update')->name('product-categories.update');
        Route::delete('/{productCategory}', [ProductCategoryController::class, 'destroy'])->middleware('permission:inventory.delete')->name('product-categories.destroy');

        // API Endpoints
        Route::get('/api/categories', [ProductCategoryController::class, 'getCategories'])->name('product-categories.get-categories');
        Route::get('/{productCategory}/account-mapping', [ProductCategoryController::class, 'getAccountMapping'])->name('product-categories.get-account-mapping');
    });

    // Warehouse Management
    Route::prefix('warehouses')->middleware(['permission:inventory.view'])->group(function () {
        Route::get('/', [WarehouseController::class, 'index'])->name('warehouses.index');
        Route::get('/create', [WarehouseController::class, 'create'])->middleware('permission:inventory.create')->name('warehouses.create');
        Route::post('/', [WarehouseController::class, 'store'])->middleware('permission:inventory.create')->name('warehouses.store');
        Route::get('/{warehouse}', [WarehouseController::class, 'show'])->name('warehouses.show');
        Route::get('/{warehouse}/edit', [WarehouseController::class, 'edit'])->middleware('permission:inventory.update')->name('warehouses.edit');
        Route::patch('/{warehouse}', [WarehouseController::class, 'update'])->middleware('permission:inventory.update')->name('warehouses.update');
        Route::delete('/{warehouse}', [WarehouseController::class, 'destroy'])->middleware('permission:inventory.delete')->name('warehouses.destroy');

        // Warehouse Stock Management
        Route::get('/api/warehouses', [WarehouseController::class, 'getWarehouses'])->name('warehouses.get-warehouses');
        Route::get('/api/items/{itemId}/stock', [WarehouseController::class, 'getItemStock'])->name('warehouses.get-item-stock');
        Route::post('/transfer-stock', [WarehouseController::class, 'transferStock'])->middleware('permission:inventory.transfer')->name('warehouses.transfer-stock');
        Route::get('/low-stock/{warehouse?}', [WarehouseController::class, 'lowStock'])->name('warehouses.low-stock');
    });

    // Audit Log Management
    Route::prefix('audit-logs')->middleware(['permission:admin.view'])->group(function () {
        Route::get('/', [AuditLogController::class, 'index'])->name('audit-logs.index');
        Route::get('/data', [AuditLogController::class, 'data'])->name('audit-logs.data');
        Route::get('/{entityType}/{entityId}', [AuditLogController::class, 'show'])->name('audit-logs.show');
        Route::get('/by-user/{userId}', [AuditLogController::class, 'byUser'])->name('audit-logs.by-user');
        Route::get('/by-action/{action}', [AuditLogController::class, 'byAction'])->name('audit-logs.by-action');
    });

    // Tax Compliance Management
    Route::prefix('tax')->middleware(['permission:tax.view'])->group(function () {
        Route::get('/', [TaxController::class, 'index'])->name('tax.index');

        // Tax Transactions
        Route::get('/transactions', [TaxController::class, 'transactions'])->name('tax.transactions');
        Route::get('/transactions/data', [TaxController::class, 'transactionsData'])->name('tax.transactions.data');
        Route::get('/transactions/create', [TaxController::class, 'createTransaction'])->middleware('permission:tax.create')->name('tax.transactions.create');
        Route::post('/transactions', [TaxController::class, 'storeTransaction'])->middleware('permission:tax.create')->name('tax.transactions.store');
        Route::get('/transactions/{transaction}', [TaxController::class, 'showTransaction'])->name('tax.transactions.show');
        Route::post('/transactions/{transaction}/mark-paid', [TaxController::class, 'markAsPaid'])->middleware('permission:tax.update')->name('tax.transactions.mark-paid');
        Route::get('/transactions/export', [TaxController::class, 'exportTransactions'])->name('tax.transactions.export');

        // Tax Periods
        Route::get('/periods', [TaxController::class, 'periods'])->name('tax.periods');
        Route::get('/periods/create', [TaxController::class, 'createPeriod'])->middleware('permission:tax.create')->name('tax.periods.create');
        Route::post('/periods', [TaxController::class, 'storePeriod'])->middleware('permission:tax.create')->name('tax.periods.store');
        Route::post('/periods/{period}/close', [TaxController::class, 'closePeriod'])->middleware('permission:tax.update')->name('tax.periods.close');

        // Tax Reports
        Route::get('/reports', [TaxController::class, 'reports'])->name('tax.reports');
        Route::get('/reports/create', [TaxController::class, 'createReport'])->middleware('permission:tax.create')->name('tax.reports.create');
        Route::post('/reports', [TaxController::class, 'storeReport'])->middleware('permission:tax.create')->name('tax.reports.store');
        Route::get('/reports/{report}', [TaxController::class, 'showReport'])->name('tax.reports.show');
        Route::post('/reports/{report}/submit', [TaxController::class, 'submitReport'])->middleware('permission:tax.update')->name('tax.reports.submit');
        Route::post('/reports/{report}/approve', [TaxController::class, 'approveReport'])->middleware('permission:tax.approve')->name('tax.reports.approve');

        // Tax Settings
        Route::get('/settings', [TaxController::class, 'settings'])->name('tax.settings');
        Route::post('/settings', [TaxController::class, 'updateSettings'])->middleware('permission:tax.settings')->name('tax.settings.update');

        // Tax Calendar
        Route::get('/calendar', [TaxController::class, 'calendar'])->name('tax.calendar');

        // Compliance Logs
        Route::get('/compliance-logs', [TaxController::class, 'complianceLogs'])->name('tax.compliance-logs');
    });

    // COGS Management
    Route::prefix('cogs')->middleware(['permission:cogs.view'])->group(function () {
        Route::get('/', [COGSController::class, 'index'])->name('cogs.index');

        // Cost History
        Route::get('/cost-history', [COGSController::class, 'costHistory'])->name('cogs.cost-history');

        // Product Costs
        Route::get('/product-costs', [COGSController::class, 'productCosts'])->name('cogs.product-costs');

        // Margin Analysis
        Route::get('/margin-analysis', [COGSController::class, 'marginAnalysis'])->name('cogs.margin-analysis');

        // API Endpoints
        Route::post('/calculate-product-cogs', [COGSController::class, 'calculateProductCOGS'])->middleware('permission:cogs.calculate')->name('cogs.calculate-product');
        Route::post('/calculate-customer-profitability', [COGSController::class, 'calculateCustomerProfitability'])->middleware('permission:cogs.calculate')->name('cogs.calculate-customer');
        Route::post('/generate-report', [COGSController::class, 'generateReport'])->middleware('permission:cogs.report')->name('cogs.generate-report');
        Route::get('/product-cost-trends', [COGSController::class, 'getProductCostTrends'])->name('cogs.product-trends');
        Route::post('/allocate-indirect-costs', [COGSController::class, 'allocateIndirectCosts'])->middleware('permission:cogs.allocate')->name('cogs.allocate');
        Route::get('/optimization-opportunities', [COGSController::class, 'getOptimizationOpportunities'])->name('cogs.optimization');
        Route::get('/export', [COGSController::class, 'export'])->middleware('permission:cogs.export')->name('cogs.export');
    });

    // Supplier Analytics Management
    Route::prefix('supplier-analytics')->middleware(['permission:supplier_analytics.view'])->group(function () {
        Route::get('/', [SupplierAnalyticsController::class, 'index'])->name('supplier-analytics.index');

        // Performance Analysis
        Route::get('/performance', [SupplierAnalyticsController::class, 'performance'])->name('supplier-analytics.performance');

        // Supplier Comparisons
        Route::get('/comparisons', [SupplierAnalyticsController::class, 'comparisons'])->name('supplier-analytics.comparisons');

        // Optimization Opportunities
        Route::get('/optimization', [SupplierAnalyticsController::class, 'optimization'])->name('supplier-analytics.optimization');

        // API Endpoints
        Route::post('/generate-analytics', [SupplierAnalyticsController::class, 'generateAnalytics'])->middleware('permission:supplier_analytics.generate')->name('supplier-analytics.generate');
        Route::get('/supplier-ranking', [SupplierAnalyticsController::class, 'getSupplierRanking'])->name('supplier-analytics.ranking');
        Route::post('/compare-suppliers', [SupplierAnalyticsController::class, 'compareSuppliers'])->middleware('permission:supplier_analytics.compare')->name('supplier-analytics.compare');
        Route::get('/supplier-trends', [SupplierAnalyticsController::class, 'getSupplierTrends'])->name('supplier-analytics.trends');
        Route::get('/supplier-risk', [SupplierAnalyticsController::class, 'calculateSupplierRisk'])->name('supplier-analytics.risk');
        Route::get('/supplier-details', [SupplierAnalyticsController::class, 'getSupplierDetails'])->name('supplier-analytics.details');
        Route::get('/export', [SupplierAnalyticsController::class, 'export'])->middleware('permission:supplier_analytics.export')->name('supplier-analytics.export');
    });

    // Business Intelligence Management
    Route::prefix('business-intelligence')->middleware(['permission:business_intelligence.view'])->group(function () {
        Route::get('/', [BusinessIntelligenceController::class, 'index'])->name('business-intelligence.index');

        // Analytics Reports
        Route::get('/reports', [BusinessIntelligenceController::class, 'reports'])->name('business-intelligence.reports');

        // Insights and Recommendations
        Route::get('/insights', [BusinessIntelligenceController::class, 'insights'])->name('business-intelligence.insights');

        // KPI Dashboard
        Route::get('/kpi-dashboard', [BusinessIntelligenceController::class, 'kpiDashboard'])->name('business-intelligence.kpi-dashboard');

        // API Endpoints
        Route::post('/generate-report', [BusinessIntelligenceController::class, 'generateReport'])->middleware('permission:business_intelligence.generate')->name('business-intelligence.generate');
        Route::get('/report-details', [BusinessIntelligenceController::class, 'getReportDetails'])->name('business-intelligence.report-details');
        Route::get('/insights-data', [BusinessIntelligenceController::class, 'getInsights'])->name('business-intelligence.insights-data');
        Route::get('/trend-analysis', [BusinessIntelligenceController::class, 'getTrendAnalysis'])->name('business-intelligence.trend-analysis');
        Route::get('/kpi-metrics', [BusinessIntelligenceController::class, 'getKpiMetrics'])->name('business-intelligence.kpi-metrics');
        Route::get('/dashboard-summary', [BusinessIntelligenceController::class, 'getDashboardSummary'])->name('business-intelligence.dashboard-summary');
        Route::get('/export-report', [BusinessIntelligenceController::class, 'exportReport'])->middleware('permission:business_intelligence.export')->name('business-intelligence.export');
        Route::delete('/delete-report', [BusinessIntelligenceController::class, 'deleteReport'])->middleware('permission:business_intelligence.delete')->name('business-intelligence.delete');
    });

    // Unified Analytics Dashboard
    Route::prefix('analytics')->middleware(['permission:analytics.view'])->group(function () {
        Route::get('/unified-dashboard', [AnalyticsController::class, 'unifiedDashboard'])->name('analytics.unified-dashboard');
        Route::get('/comprehensive-data', [AnalyticsController::class, 'getComprehensiveAnalytics'])->name('analytics.comprehensive-data');
        Route::post('/generate-integrated-report', [AnalyticsController::class, 'generateIntegratedReport'])->middleware('permission:analytics.generate')->name('analytics.generate-integrated-report');
    });

    // Account Statements Management
    Route::prefix('account-statements')->middleware(['permission:account_statements.view'])->group(function () {
        Route::get('/', [AccountStatementController::class, 'index'])->name('account-statements.index');
        Route::get('/create', [AccountStatementController::class, 'create'])->middleware('permission:account_statements.create')->name('account-statements.create');
        Route::post('/', [AccountStatementController::class, 'store'])->middleware('permission:account_statements.create')->name('account-statements.store');
        Route::get('/{accountStatement}', [AccountStatementController::class, 'show'])->name('account-statements.show');
        Route::get('/{accountStatement}/edit', [AccountStatementController::class, 'edit'])->middleware('permission:account_statements.update')->name('account-statements.edit');
        Route::patch('/{accountStatement}', [AccountStatementController::class, 'update'])->middleware('permission:account_statements.update')->name('account-statements.update');
        Route::delete('/{accountStatement}', [AccountStatementController::class, 'destroy'])->middleware('permission:account_statements.delete')->name('account-statements.destroy');

        // Statement Actions
        Route::post('/{accountStatement}/finalize', [AccountStatementController::class, 'finalize'])->middleware('permission:account_statements.update')->name('account-statements.finalize');
        Route::post('/{accountStatement}/cancel', [AccountStatementController::class, 'cancel'])->middleware('permission:account_statements.update')->name('account-statements.cancel');

        // Export and Print
        Route::get('/{accountStatement}/export', [AccountStatementController::class, 'export'])->name('account-statements.export');
        Route::get('/{accountStatement}/print', [AccountStatementController::class, 'print'])->name('account-statements.print');

        // Balance API Endpoints
        Route::get('/api/account-balance', [AccountStatementController::class, 'getAccountBalance'])->name('account-statements.account-balance');
        Route::get('/api/business-partner-balance', [AccountStatementController::class, 'getBusinessPartnerBalance'])->name('account-statements.business-partner-balance');
    });

    // Control Account Management
    Route::prefix('control-accounts')->middleware(['permission:accounts.view'])->group(function () {
        Route::get('/', [ControlAccountController::class, 'index'])->name('control-accounts.index');
        Route::get('/create', [ControlAccountController::class, 'create'])->middleware('permission:accounts.manage')->name('control-accounts.create');
        Route::post('/', [ControlAccountController::class, 'store'])->middleware('permission:accounts.manage')->name('control-accounts.store');
        Route::get('/reconciliation', [ControlAccountController::class, 'reconciliation'])->name('control-accounts.reconciliation');
        Route::get('/data', [ControlAccountController::class, 'data'])->name('control-accounts.data');
        Route::get('/{controlAccount}', [ControlAccountController::class, 'show'])->name('control-accounts.show');
        Route::post('/{controlAccount}/reconcile', [ControlAccountController::class, 'reconcile'])->middleware('permission:accounts.manage')->name('control-accounts.reconcile');
    });
});

// Auth routes (AdminLTE views)
Route::middleware('guest')->group(function () {
    Route::get('login', [\App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [\App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'store']);
});

Route::post('logout', [\App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'destroy'])->name('logout');
