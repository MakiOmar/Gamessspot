<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AdminLoginController;
use App\Http\Controllers\ManagerController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\StoreProfileController;
use App\Http\Controllers\RoleAssignmentController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\SpecialPriceController;
use App\Http\Controllers\MasterController;
use App\Http\Controllers\CardCategoryController;
use App\Http\Controllers\CardController;
use App\Http\Controllers\DashboardController;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('manager')->group(function () {
    // Manager login routes (no middleware needed here)
    Route::get('/login', [AdminLoginController::class, 'showLoginForm'])->name('manager.login');
    Route::post('/login', [AdminLoginController::class, 'login'])->name('manager.login.submit');
    Route::post('/logout', [AdminLoginController::class, 'logout'])->name('manager.logout');

    // Group routes that require 'auth:admin' middleware
    Route::middleware('auth:admin')->group(function () {
        // Dashboard route
        Route::get('/', [DashboardController::class, 'dashboard'])->middleware('can:access-dashboard')->name('manager.dashboard');

        // Routes with 'can:manage-games' middleware
        Route::middleware('can:manage-games')->group(function () {
            Route::prefix('games')->group(function () {
                Route::get('/', [ManagerController::class, 'showGames'])->name('manager.games');
                Route::get('/ps4', [ManagerController::class, 'showPS4Games'])->name('manager.games.ps4');
                Route::get('/ps5', [ManagerController::class, 'showPS5Games'])->name('manager.games.ps5');
                Route::get('/search/ps4', [ManagerController::class, 'searchPS4Games'])->name('manager.games.search.ps4');
                Route::get('/search/ps5', [ManagerController::class, 'searchPS5Games'])->name('manager.games.search.ps5');
                Route::get('/search', [ManagerController::class, 'searchGamesByTitle'])->name('manager.games.search');
            });
        });

        // Routes with 'can:edit-games' middleware for admin only
        Route::middleware(['checkRole:admin', 'can:edit-games'])->group(function () {
            Route::prefix('games')->group(function () {
                Route::get('/{id}/edit', [ManagerController::class, 'edit'])->name('manager.games.edit');
                Route::post('/store', [ManagerController::class, 'store'])->name('games.store');
                Route::put('/{id}', [ManagerController::class, 'update'])->name('manager.games.update');
            });
        });

        // Routes with 'can:manage-accounts' middleware for admin and account manager
        Route::middleware(['checkRole:admin,account manager', 'can:manage-accounts'])->group(function () {
            Route::prefix('accounts')->group(function () {
                Route::get('/', [AccountController::class, 'index'])->name('manager.accounts');
                Route::post('/store', [AccountController::class, 'store'])->name('manager.accounts.store');
                Route::get('/search', [AccountController::class, 'search'])->name('manager.accounts.search');
                Route::get('/export', [AccountController::class, 'export'])->name('manager.accounts.export');
                Route::get('/{id}/edit', [AccountController::class, 'edit'])->name('manager.accounts.edit');
                Route::put('/{id}', [AccountController::class, 'update'])->name('manager.accounts.update');
            });
        });

        // Routes with 'can:view-sell-log' middleware
        Route::middleware('can:view-sell-log')->group(function () {
            Route::prefix('orders')->group(function () {
                Route::get('/', [OrderController::class, 'index'])->name('manager.orders');
                Route::get('/search', [OrderController::class, 'search'])->name('manager.orders.search');
                Route::get('/quick-search', [OrderController::class, 'quickSearch'])->name('manager.orders.qsearch');
                Route::get('/export', [OrderController::class, 'export'])->name('manager.orders.export');
                Route::post('/store', [OrderController::class, 'store'])->name('orders.store');
                Route::post('/sell-card', [OrderController::class, 'sellCard'])->name('manager.orders.sell.card');
                Route::post('/send-to-pos', [OrderController::class, 'sendToPos'])->name('manager.orders.sendToPos');
                
                Route::get('/has-problem', [OrderController::class, 'ordersHasProblem'])->name('manager.orders.has_problem');
                Route::get('/needs-return', [OrderController::class, 'ordersWithNeedsReturn'])->name('manager.orders.needs_return');
                Route::get('/solved', [OrderController::class, 'solvedOrders'])->name('manager.orders.solved');
            });
        });

        // Customer-related routes
        Route::prefix('customers')->group(function () {
            Route::get('/export', [OrderController::class, 'customersExport'])->name('manager.customers.export');
            Route::get('/', [OrderController::class, 'uniqueBuyers'])->middleware('can:manage-users')->name('manager.uniqueBuyers');
            Route::get('/search', [OrderController::class, 'searchCustomers'])->name('manager.orders.searchCustomers');
            Route::get('/buyer-name', [UserController::class, 'searchUserHelper'])->name('manager.buyer.name');
        });

        // Admin-only order routes
        Route::middleware(['checkRole:admin', 'can:manage-options'])->group(function () {
            Route::post('/orders/undo', [OrderController::class, 'undo'])->name('manager.orders.undo');
        });
        Route::post('/reports/store', [ReportsController::class, 'store'])->name('manager.reports.store');
        // Routes with 'can:view-reports' middleware
        Route::middleware('can:view-reports')->group(function () {
            Route::post('/reports/solve-problem', [ReportsController::class, 'solveProblem'])->name('reports.solve_problem');
            Route::get('/reports/{order_id}', [ReportsController::class, 'getReportsForOrder']);
            
            Route::prefix('special-prices')->group(function () {
                Route::post('/create', [SpecialPriceController::class, 'createSpecialPrice'])->name('special-prices.create');
                Route::get('/{id}', [SpecialPriceController::class, 'getGamesWithSpecialPrices'])->name('manager.special-prices');
                Route::put('/{id}', [SpecialPriceController::class, 'update'])->name('special-prices.update');
                Route::get('/{id}/edit', [SpecialPriceController::class, 'edit'])->name('special-prices.edit');
                Route::put('/{id}/toggle-availability', [SpecialPriceController::class, 'toggleAvailability']);
            });
        });

        // Routes with 'can:manage-users' middleware
        Route::middleware('can:manage-users')->group(function () {
            Route::prefix('users')->group(function () {
                Route::get('/', [UserController::class, 'index'])->name('manager.users.index');
                Route::get('/sales', [UserController::class, 'sales'])->name('manager.users.sales');
                Route::get('/accountants', [UserController::class, 'accountants'])->name('manager.users.accountants');
                Route::get('/admins', [UserController::class, 'admins'])->name('manager.users.admins');
                Route::get('/account-managers', [UserController::class, 'accountManagers'])->name('manager.users.acc.managers');
                Route::get('/customers', [UserController::class, 'customers'])->name('manager.users.customers');
                Route::get('/search/{role?}', [UserController::class, 'search'])->name('manager.users.search');
                Route::get('/{id}/edit', [UserController::class, 'edit'])->name('manager.users.edit');
                Route::put('/update/{id}', [UserController::class, 'update'])->name('manager.users.update');
                Route::post('/store', [UserController::class, 'store'])->name('manager.users.store');
                Route::delete('/delete/{id}', [UserController::class, 'destroy'])->name('users.delete');
                Route::post('/toggle-status/{id}', [UserController::class, 'toggleStatus'])->name('users.toggleStatus');
            });
        });

        // Routes with 'can:manage-store-profiles' middleware
        Route::middleware('can:manage-store-profiles')->group(function () {
            Route::prefix('storeProfiles')->group(function () {
                Route::get('/', [StoreProfileController::class, 'index'])->name('manager.storeProfiles.index');
                Route::post('/store', [StoreProfileController::class, 'store'])->name('manager.storeProfiles.store');
                Route::get('/{id}/edit', [StoreProfileController::class, 'edit'])->name('manager.storeProfiles.edit');
                Route::put('/update/{id}', [StoreProfileController::class, 'update'])->name('manager.storeProfiles.update');
                Route::get('/search', [StoreProfileController::class, 'search'])->name('manager.storeProfiles.search');
            });
        });

        // Routes with 'can:manage-gift-cards' middleware
        Route::middleware('can:manage-gift-cards')->group(function () {
            Route::get('/cards/sell', [CardCategoryController::class, 'sell'])->name('manager.sell-cards');
        });

        // Resource routes
        Route::resource('masters', MasterController::class);
        Route::resource('card-categories', CardCategoryController::class);
        Route::resource('cards', CardController::class);
    });
});