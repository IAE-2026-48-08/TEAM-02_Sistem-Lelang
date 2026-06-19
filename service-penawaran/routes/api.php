<?php

use App\Http\Controllers\BidController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\SsoController;

Route::middleware(['iae.auth'])->prefix('v1')->group(function () {
    Route::get('/bids', [BidController::class, 'index']);
    Route::get('/bids/{id}', [BidController::class, 'show']);
    Route::post('/bids', [BidController::class, 'store']);
});

Route::prefix('v1')->group(function () {
    Route::post('/auth/sso/login', [SsoController::class, 'login']);
});

Route::middleware(['jwt.verify'])->prefix('v1')->group(function () {
    Route::get('/auth/sso/me', [SsoController::class, 'me']);
});