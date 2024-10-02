<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AdminLoginController;
use App\Http\Controllers\ManagerController;

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
    // Manager login routes
    Route::get('/login', [AdminLoginController::class, 'showLoginForm'])->name('manager.login');
    Route::post('/login', [AdminLoginController::class, 'login'])->name('manager.login.submit');
    Route::post('/logout', [AdminLoginController::class, 'logout'])->name('manager.logout');

    // Manager dashboard (protected route)
    Route::get('/dashboard', function () {
        return view('manager.dashboard');
    })->middleware('auth:admin')->name('manager.dashboard');

    // Protected routes for managers (only admins can access these routes)
    Route::middleware('auth:admin')->group(function () {
        // Games management route
        Route::get('/games', [ManagerController::class, 'showGames'])->name('manager.games');
    });
});
