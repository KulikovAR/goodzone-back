<?php

use App\Http\Controllers\AssetsController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\{
    RestaurantController,
    CategoryController,
    ProductController,
    BannerController,
    SettingController,
    OrderController
};


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

Route::middleware(['guest'])->group(function () {
    Route::get('/login', [AuthController::class, 'login'])->name('auth.login');
    Route::post('/verify', [AuthController::class, 'verify'])->name('auth.verify');

    Route::middleware('refresh')->group(function () {
        Route::post('/refresh', [AuthController::class, 'refresh'])->name('auth.refresh');
    });
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/logout', [AuthController::class, 'logout']);
});

Route::get('/assets/{locale?}', [AssetsController::class, 'show'])->name('assets.index');


Route::apiResource('restaurants', RestaurantController::class);
Route::apiResource('categories', CategoryController::class);
Route::apiResource('products', ProductController::class);
Route::apiResource('banners', BannerController::class);
Route::apiResource('settings', SettingController::class);

Route::get('products/{id}/recommended', [ProductController::class, 'getRecommended']);
Route::post('orders', [OrderController::class, 'store']);
Route::get('orders', [OrderController::class, 'index']);
Route::get('orders/{id}', [OrderController::class, 'show']);