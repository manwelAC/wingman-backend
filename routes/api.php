<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\PricingController;
use App\Http\Controllers\Api\CalculatorController;

// Public routes
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });


    //Profile routes
    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'show']);
        Route::put('/', [ProfileController::class, 'update']);
    });


    //Customer routes
    Route::apiResource('customers', CustomerController::class);


    //Pricing routes
    Route::prefix('pricing')->group(function () {
        Route::get('/', [PricingController::class, 'index']);
        Route::post('/', [PricingController::class, 'store']);
        Route::put('/{id}', [PricingController::class, 'update']);
        Route::delete('/{id}', [PricingController::class, 'destroy']);
        Route::get('/audit', [PricingController::class, 'audit']);
    });

    //Calculator Controller
    Route::prefix('calculator')->group(function () {
        Route::post('/rank-boost', [CalculatorController::class, 'rankBoost']);
    });
});