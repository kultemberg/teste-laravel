<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClienteCreditoController;

Route::prefix('v1')->group(function () {
    Route::post('/login', [AuthController::class , 'login']);

    Route::middleware('auth:sanctum')->group(function () {
            Route::get('/me', [AuthController::class , 'me']);
            Route::post('/logout', [AuthController::class , 'logout']);

            Route::post('/clientes/{cliente}/aplicar-credito', [ClienteCreditoController::class , 'aplicarCredito']);
        }
        );
    });