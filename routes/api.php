<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\PricingController;
use App\Http\Controllers\Api\CalculatorController;
use App\Http\Controllers\Api\GrindController;
use App\Http\Controllers\Api\GameRankController;
use App\Http\Controllers\PaymentMethodController;


// Public routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/verify-email', [AuthController::class, 'verifyEmail']);
    Route::post('/resend-code', [AuthController::class, 'resendCode']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/login-fingerprint', [AuthController::class, 'loginWithFingerprint']);
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/enroll-fingerprint', [AuthController::class, 'enrollFingerprint']);
        Route::post('/disable-fingerprint', [AuthController::class, 'disableFingerprint']);
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

    //Calculator routes
    Route::prefix('calculator')->group(function () {
        Route::post('/rank-boost', [CalculatorController::class, 'rankBoost']);
    });

    //Grind Routes
    Route::prefix('grinds')->group(function () {
        Route::get('/', [GrindController::class, 'index']);
        Route::post('/', [GrindController::class, 'store']);
        Route::get('/{id}', [GrindController::class, 'show']);
        Route::put('/{id}/progress', [GrindController::class, 'updateProgress']);
        Route::post('/{id}/complete', [GrindController::class, 'complete']);
        Route::delete('/{id}', [GrindController::class, 'destroy']);
    });

    //Game Rank routes
    Route::prefix('games')->group(function () {
        Route::get('/', [GameRankController::class, 'games']);
        Route::get('/{game}/ranks', [GameRankController::class, 'index']);
    });

    //Payment Methods routes
    Route::get('/payment-methods/available', [PaymentMethodController::class, 'getAvailableMethods']);
    Route::get('/payment-methods/for-grind', [PaymentMethodController::class, 'getForGrindLogging']);
    Route::get('/payment-methods', [PaymentMethodController::class, 'getUserMethods']);
    Route::post('/payment-methods', [PaymentMethodController::class, 'store']);
    Route::patch('/payment-methods/{id}', [PaymentMethodController::class, 'update']);
    Route::patch('/payment-methods/{id}/toggle', [PaymentMethodController::class, 'toggle']);
    Route::patch('/payment-methods/{id}/set-preferred', [PaymentMethodController::class, 'setPreferred']);
    Route::delete('/payment-methods/{id}', [PaymentMethodController::class, 'destroy']);
});