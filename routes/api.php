<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClienteCreditoController;
use App\Http\Controllers\Api\CobrancaController;
use App\Http\Controllers\Api\DashboardFinanceiroController;

Route::prefix('v1')->group(function () {
    Route::post('/login', [AuthController::class , 'login']);

    Route::middleware('auth:sanctum')->group(function () {
            Route::get('/me', [AuthController::class , 'me']);
            Route::post('/logout', [AuthController::class , 'logout']);
            Route::post('/clientes/{cliente}/aplicar-credito', [ClienteCreditoController::class , 'aplicarCredito']);
            Route::get('/cobrancas', [CobrancaController::class , 'index'])->middleware('throttle:cobrancas');
            Route::get('/dashboard', [DashboardFinanceiroController::class , 'index']);
        }
        );
    });