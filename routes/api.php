<?php

use App\Domains\Auth\Http\Controllers\LoginController;
use App\Domains\Auth\Http\Controllers\LogoutController;
use App\Domains\Auth\Http\Controllers\RefreshController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [LoginController::class, 'login']);

Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [LogoutController::class, 'logout']);
    Route::post('/refresh', [RefreshController::class, 'refresh']);
});
