<?php

use App\Http\Controllers\CardCategoryController;
use App\Http\Controllers\ManagerController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PublicDeviceController;
use App\Http\Controllers\PublicReviewController;
use App\Http\Controllers\UserController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Machine / POS integration uses phone+password login with scoped Sanctum
| abilities. Manager web login is email OTP only (see docs/LOGIN_OTP.md).
|
*/

Route::middleware(['auth:sanctum', 'active'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/login', function (Request $request) {
    $request->validate([
        'phone' => 'required|string',
        'password' => 'required',
    ]);

    $user = User::where('phone', $request->phone)->first();

    if (! $user || ! Hash::check($request->password, $user->password)) {
        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    if ((int) $user->is_active !== 1) {
        return response()->json(['message' => 'Your account has been deactivated. Please contact support.'], 403);
    }

    $allowedIds = config('api.login_user_ids', []);
    $allowedPhones = config('api.login_phones', []);
    $idOk = in_array((int) $user->id, array_map('intval', $allowedIds), true);
    $phoneOk = in_array((string) $user->phone, array_map('strval', $allowedPhones), true);

    if (! $idOk && ! $phoneOk) {
        return response()->json([
            'message' => 'API login is restricted to integration accounts.',
        ], 403);
    }

    $abilities = config('api.token_abilities', [
        'orders:receive',
        'pos:receive-order',
        'customers:write',
        'orders:check-stock',
    ]);
    $ttlDays = max(1, (int) config('api.token_ttl_days', 8));

    $token = $user->createToken('API Token', $abilities, now()->addDays($ttlDays))->plainTextToken;

    return response()->json(['token' => $token], 200);
})->middleware('throttle:20,1');

Route::middleware(['auth:sanctum', 'active', 'abilities:orders:check-stock'])->get('orders/latest', [OrderController::class, 'latestCustomerOrders']);
Route::middleware(['auth:sanctum', 'active', 'abilities:orders:receive'])->post('/orders/receive', [OrderController::class, 'storeApi']);
Route::middleware(['auth:sanctum', 'active', 'abilities:orders:check-stock'])->post('/orders/check_stock', [OrderController::class, 'checkStockApi']);
Route::middleware(['auth:sanctum', 'active', 'abilities:orders:check-stock'])->post('/orders/check_card_stock', [OrderController::class, 'checkCardStockApi']);
Route::middleware(['auth:sanctum', 'active', 'abilities:pos:receive-order'])->post('/pos/receive-order', [OrderController::class, 'receiveFromPos']);
Route::get('/games/platform/{platform}', [ManagerController::class, 'getGamesByPlatformApi']);
// Combined featured (both platforms) must be registered before /games/featured/{platform}
Route::get('/games/featured', [ManagerController::class, 'getFeaturedGamesApi']);
Route::get('/games/featured/{platform}', [ManagerController::class, 'getFeaturedGamesByPlatformApi']);
Route::get('/games/{id}', [ManagerController::class, 'getGameById']);
Route::get('/card-ctegories/list', [CardCategoryController::class, 'sellApi']);
Route::get('/card-categories/{cardCategory}', [CardCategoryController::class, 'showApi']);

Route::middleware(['auth:sanctum', 'active', 'abilities:customers:write'])->post('/customers', [UserController::class, 'createCustomerApi'])->name('api.customers.create');

Route::match(['get', 'post'], '/device/track', [PublicDeviceController::class, 'trackByPhoneApi'])
    ->middleware('throttle:30,1')
    ->name('api.device.track');

Route::post('/reviews', [PublicReviewController::class, 'store'])
    ->middleware('throttle:30,1')
    ->name('api.reviews.store');
