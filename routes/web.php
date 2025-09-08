<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Reports\ReportsController;
use App\Http\Controllers\Dev\PostingDemoController;
use App\Http\Controllers\Accounting\ManualJournalController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\RoleController as AdminRoleController;
use App\Http\Controllers\Admin\PermissionController as AdminPermissionController;
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

    Route::prefix('reports')->group(function () {
        Route::get('/trial-balance', [ReportsController::class, 'trialBalance'])->name('reports.trial-balance');
        Route::get('/gl-detail', [ReportsController::class, 'glDetail'])->name('reports.gl-detail');
    });

    Route::prefix('dev')->group(function () {
        Route::post('/post-journal', [PostingDemoController::class, 'store'])->name('dev.post-journal');
    });

    Route::prefix('journals')->middleware(['permission:journals.view'])->group(function () {
        Route::get('/', [ManualJournalController::class, 'index'])->name('journals.index');
        Route::get('/manual/create', [ManualJournalController::class, 'create'])->middleware('permission:journals.create')->name('journals.manual.create');
        Route::post('/manual', [ManualJournalController::class, 'store'])->middleware('permission:journals.create')->name('journals.manual.store');
        Route::post('/{journal}/reverse', [ManualJournalController::class, 'reverse'])->middleware('permission:journals.reverse')->name('journals.reverse');
        Route::get('/data', [ManualJournalController::class, 'data'])->name('journals.data');
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
});

// Auth routes (AdminLTE views)
Route::middleware('guest')->group(function () {
    Route::get('login', [\App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [\App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'store']);
});

Route::post('logout', [\App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'destroy'])->name('logout');
