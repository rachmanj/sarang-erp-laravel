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
use App\Http\Controllers\Master\CustomerController;
use App\Http\Controllers\Master\VendorController;
use App\Http\Controllers\Dimensions\ProjectController as DimProjectController;
use App\Http\Controllers\Dimensions\FundController as DimFundController;
use App\Http\Controllers\Dimensions\DepartmentController as DimDepartmentController;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\SalesOrderController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\GoodsReceiptController;
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

    // Customers (Sales group)
    Route::prefix('customers')->group(function () {
        Route::get('/', [CustomerController::class, 'index'])->name('customers.index');
        Route::get('/data', [CustomerController::class, 'data'])->name('customers.data');
        Route::get('/create', [CustomerController::class, 'create'])->name('customers.create');
        Route::post('/', [CustomerController::class, 'store'])->name('customers.store');
        Route::get('/{customer}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
        Route::patch('/{customer}', [CustomerController::class, 'update'])->name('customers.update');
    });

    // Vendors (Purchase group)
    Route::prefix('vendors')->group(function () {
        Route::get('/', [VendorController::class, 'index'])->name('vendors.index');
        Route::get('/data', [VendorController::class, 'data'])->name('vendors.data');
        Route::get('/create', [VendorController::class, 'create'])->name('vendors.create');
        Route::post('/', [VendorController::class, 'store'])->name('vendors.store');
        Route::get('/{vendor}/edit', [VendorController::class, 'edit'])->name('vendors.edit');
        Route::patch('/{vendor}', [VendorController::class, 'update'])->name('vendors.update');
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
    Route::prefix('funds')->middleware(['permission:funds.view'])->group(function () {
        Route::get('/', [DimFundController::class, 'index'])->name('funds.index');
        Route::get('/data', [DimFundController::class, 'data'])->name('funds.data');
        Route::post('/', [DimFundController::class, 'store'])->middleware('permission:funds.manage')->name('funds.store');
        Route::patch('/{id}', [DimFundController::class, 'update'])->middleware('permission:funds.manage')->name('funds.update');
        Route::delete('/{id}', [DimFundController::class, 'destroy'])->middleware('permission:funds.manage')->name('funds.destroy');
    });
    Route::prefix('departments')->middleware(['permission:departments.view'])->group(function () {
        Route::get('/', [DimDepartmentController::class, 'index'])->name('departments.index');
        Route::get('/data', [DimDepartmentController::class, 'data'])->name('departments.data');
        Route::post('/', [DimDepartmentController::class, 'store'])->middleware('permission:departments.manage')->name('departments.store');
        Route::patch('/{id}', [DimDepartmentController::class, 'update'])->middleware('permission:departments.manage')->name('departments.update');
        Route::delete('/{id}', [DimDepartmentController::class, 'destroy'])->middleware('permission:departments.manage')->name('departments.destroy');
    });
});

// Auth routes (AdminLTE views)
Route::middleware('guest')->group(function () {
    Route::get('login', [\App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [\App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'store']);
});

Route::post('logout', [\App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'destroy'])->name('logout');
