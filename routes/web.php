<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AdminLoginController;
use App\Http\Controllers\ManagerController;
use App\Http\Controllers\AccountController;

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
        Route::get('/dashboard', function () {
            return view('manager.dashboard');
        })->name('manager.dashboard');

        // Games management route
        Route::get('/games', [ManagerController::class, 'showGames'])->name('manager.games');
        // Route to get game data for editing
        Route::get('/games/{id}/edit', [ManagerController::class, 'edit'])->name('manager.games.edit');
        Route::post('/store/games', [ManagerController::class, 'store'])->name('games.store');
        // Route to update game data
        Route::put('/games/{id}', [ManagerController::class, 'update'])->name('manager.games.update');

        Route::get('/accounts', [AccountController::class, 'index'])->name('manager.accounts');
        Route::post('/accounts/store', [AccountController::class, 'store'])->name('manager.accounts.store');

        Route::get('/accounts/search', [AccountController::class, 'search'])->name('manager.accounts.search');


    });
});
