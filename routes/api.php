<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ManagerController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/login', function (Request $request) {
    $request->validate([
        'phone' => 'required|string',
        'password' => 'required',
    ]);

    $user = \App\Models\User::where('phone', $request->phone)->first();

    if (!$user || !Hash::check($request->password, $user->password)) {
        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    // Generate a token
    $token = $user->createToken('API Token', ['*'], now()->addDays(8))->plainTextToken;

    return response()->json(['token' => $token], 200);
});

Route::middleware('auth:sanctum')->get('orders/latest', [OrderController::class, 'latestCustomerOrders']);
Route::middleware('auth:sanctum')->post('/orders/receive', [OrderController::class, 'storeApi']);
Route::middleware('auth:sanctum')->post('/orders/check_stock', [OrderController::class, 'checkStockApi']);
Route::get('/games/platform/{platform}', [ManagerController::class, 'getGamesByPlatformApi']);
Route::get('/games/{id}', [ManagerController::class, 'getGameById']);
