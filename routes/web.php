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

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

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
        // Manager dashboard
        Route::get('/', [DashboardController::class, 'dashboard'])->name('manager.dashboard');

        // Games management route
        Route::get('/games', [ManagerController::class, 'showGames'])->name('manager.games');
        // Route to get game data for editing
        Route::middleware(['checkRole:admin'])->group(function () {
            Route::get('/games/{id}/edit', [ManagerController::class, 'edit'])->name('manager.games.edit');
            Route::post('/games/store', [ManagerController::class, 'store'])->name('games.store');
            Route::put('/games/{id}', [ManagerController::class, 'update'])->name('manager.games.update');
        });

        Route::get('/games/ps4', [ManagerController::class, 'showPS4Games'])->name('manager.games.ps4');
        Route::get('/games/ps5', [ManagerController::class, 'showPS5Games'])->name('manager.games.ps5');

        Route::middleware(['checkRole:admin,account manager'])->group(function () {
            Route::get('/accounts', [AccountController::class, 'index'])->name('manager.accounts');
            Route::post('/accounts/store', [AccountController::class, 'store'])->name('manager.accounts.store');
            Route::get('/accounts/search', [AccountController::class, 'search'])->name('manager.accounts.search');
            Route::get('/accounts/export', [AccountController::class, 'export'])->name('manager.accounts.export');
            Route::get('/accounts/{id}/edit', [AccountController::class, 'edit'])->name('manager.accounts.edit');
            Route::put('/accounts/{id}', [AccountController::class, 'update'])->name('manager.accounts.update');
        });

        Route::get('/orders', [OrderController::class, 'index'])->name('manager.orders');
        Route::get('/orders/search', [OrderController::class, 'search'])->name('manager.orders.search');
        Route::get('/orders/export', [OrderController::class, 'export'])->name('manager.orders.export');
        Route::post('/orders/store', [OrderController::class, 'store'])->name('orders.store');
        Route::post('/orders/sell-card', [OrderController::class, 'sellCard'])->name('manager.orders.sell.card');

        Route::middleware(['checkRole:admin'])->group(function () {
            Route::post('/orders/undo', [OrderController::class, 'undo'])->name('manager.orders.undo');
        });
        Route::get(
            '/orders/has-problem',
            [OrderController::class, 'ordersHasProblem']
        )->name('manager.orders.has_problem');

        Route::get(
            '/orders/needs-return',
            [OrderController::class, 'ordersWithNeedsReturn']
        )->name('manager.orders.needs_return');
        Route::get(
            '/orders/solved',
            [OrderController::class, 'solvedOrders']
        )->name('manager.orders.solved');

        Route::post(
            '/reports/solve-problem',
            [ReportsController::class, 'solveProblem']
        )->name('reports.solve_problem');


        Route::get('/users', [UserController::class, 'index'])->name('manager.users.index');
        Route::get('/users/sales', [UserController::class, 'sales'])->name('manager.users.sales');
        Route::get('/users/accountants', [UserController::class, 'accountants'])->name('manager.users.accountants');
        Route::get('/users/admins', [UserController::class, 'admins'])->name('manager.users.admins');
        Route::get('/users/account-managers', [UserController::class, 'accountManagers'])->name('manager.users.acc.managers');
        Route::get('/users/search', [UserController::class, 'search'])->name('manager.users.search');
        Route::get('/users/{id}/edit', [UserController::class, 'edit'])->name('manager.users.edit');
        Route::put('/users/update/{id}', [UserController::class, 'update'])->name('manager.users.update');
        Route::post('/users/store', [UserController::class, 'store'])->name('manager.users.store');
        Route::delete('/users/delete/{id}', [UserController::class, 'destroy'])->name('users.delete');


        Route::get('/storeProfiles', [StoreProfileController::class, 'index'])->name('manager.storeProfiles.index');
        Route::post(
            '/storeProfiles/store',
            [StoreProfileController::class, 'store']
        )->name('manager.storeProfiles.store');

        Route::get(
            '/storeProfiles/{id}/edit',
            [StoreProfileController::class, 'edit']
        )->name('manager.storeProfiles.edit');

        Route::put(
            '/storeProfiles/update/{id}',
            [StoreProfileController::class, 'update']
        )->name('manager.storeProfiles.update');

        Route::get(
            '/storeProfiles/search',
            [StoreProfileController::class, 'search']
        )->name('manager.storeProfiles.search');



        Route::post('/reports/store', [ReportsController::class, 'store'])->name('manager.reports.store');
        Route::get('/reports/{order_id}', [ReportsController::class, 'getReportsForOrder']);
        // Route to display games and special prices for a specific store profile

        Route::put(
            '/special-prices/{id}/toggle-availability',
            [SpecialPriceController::class, 'toggleAvailability']
        );

        Route::post(
            '/special-prices/create',
            [SpecialPriceController::class, 'createSpecialPrice']
        )->name('special-prices.create');

        Route::get(
            '/special-prices/{id}',
            [SpecialPriceController::class,'getGamesWithSpecialPrices']
        )->name('manager.special-prices');

        Route::put(
            '/special-prices/{id}',
            [SpecialPriceController::class, 'update']
        )->name('special-prices.update');

        Route::get(
            '/special-prices/{id}/edit',
            [SpecialPriceController::class, 'edit']
        )->name('special-prices.edit');

        Route::resource('masters', MasterController::class);

        Route::resource('card-categories', CardCategoryController::class);
        Route::get(
            '/cards/sell',
            [CardCategoryController::class,'sell']
        )->name('manager.sell-cards');

        Route::resource('cards', CardController::class);


        //Route::get('/assign-roles', [RoleAssignmentController::class, 'assignRolesBasedOnQuery']);
    });
});
