<?php

use App\Http\Controllers\AssetsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BonusController;
use Illuminate\Support\Facades\Route;

Route::middleware(['guest'])->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->name('auth.login');
    Route::post('/verify', [AuthController::class, 'verify'])->name('auth.verify');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/bonus/credit', [BonusController::class, 'credit']);
    Route::post('/bonus/debit', [BonusController::class, 'debit']);
    Route::post('/bonus/promotion', [BonusController::class, 'promotion']);
});

Route::get('/assets/{locale?}', [AssetsController::class, 'show'])->name('assets.index');

