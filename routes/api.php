<?php

use App\Http\Controllers\AssetsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BonusController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['guest'])->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->name('auth.login');
    Route::post('/verify', [AuthController::class, 'verify'])->name('auth.verify');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/bonus/info', [BonusController::class, 'info']);
    Route::get('/bonus/history', [BonusController::class, 'history']);
    Route::get('/bonus/promotional-history', [BonusController::class, 'promotionalHistory']);
    Route::put('/user/update', [UserController::class, 'update']);
    Route::get('/user', [UserController::class, 'show']);

    Route::middleware('Check1cRole')->group(function () {
        Route::post('/bonus/credit', [BonusController::class, 'credit']);
        Route::post('/bonus/debit', [BonusController::class, 'debit']);
        Route::post('/bonus/promotion', [BonusController::class, 'promotion']);
    });

    Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
});

Route::get('offer', [AssetsController::class, 'offer'])->name('asset.offer');

Route::get('/assets/{locale?}', [AssetsController::class, 'show'])->name('assets.index');
Route::get('/bonus-level', [BonusController::class, 'levels']);
