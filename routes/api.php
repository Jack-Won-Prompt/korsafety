<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\Agent\ClientController as AgentClientController;
use App\Http\Controllers\Api\Agent\DashboardController as AgentDashboardController;
use App\Http\Controllers\Api\Agent\OrderController as AgentOrderController;
use App\Http\Controllers\Api\Seller\DashboardController as SellerDashboardController;
use App\Http\Controllers\Api\Seller\OrderController as SellerOrderController;
use App\Http\Controllers\Api\Seller\ProductController as SellerProductController;
use Illuminate\Support\Facades\Route;

Route::get('/ping', fn () => ['ok' => true, 'app' => config('app.name')]);

/*
|--------------------------------------------------------------------------
| 인증
|--------------------------------------------------------------------------
*/
Route::post('auth/register', [AuthController::class, 'register']);
Route::post('auth/login', [AuthController::class, 'login']);

/*
|--------------------------------------------------------------------------
| 공개 카탈로그 (게스트 탐색 허용)
|--------------------------------------------------------------------------
*/
Route::get('home', [ProductController::class, 'home']);
Route::get('categories', [ProductController::class, 'categories']);
Route::get('products', [ProductController::class, 'index']);
Route::get('products/{product}', [ProductController::class, 'show']);

/*
|--------------------------------------------------------------------------
| 인증 필요
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::get('auth/me', [AuthController::class, 'me']);
    Route::post('auth/logout', [AuthController::class, 'logout']);

    // 고객 주문 & 결제
    Route::get('orders', [OrderController::class, 'index']);
    Route::post('orders', [OrderController::class, 'store']);
    Route::get('orders/{order}', [OrderController::class, 'show']);
    Route::post('payments/confirm', [PaymentController::class, 'confirm']);
    Route::post('payments/fail', [PaymentController::class, 'fail']);

    // 협력사(Agent)
    Route::middleware('api.role:agent')->prefix('agent')->group(function () {
        Route::get('dashboard', [AgentDashboardController::class, 'index']);
        Route::get('clients', [AgentClientController::class, 'index']);
        Route::post('clients', [AgentClientController::class, 'store']);
        Route::put('clients/{client}', [AgentClientController::class, 'update']);
        Route::delete('clients/{client}', [AgentClientController::class, 'destroy']);

        Route::get('orders', [AgentOrderController::class, 'index']);
        Route::get('orders/search-products', [AgentOrderController::class, 'searchProducts']);
        Route::post('orders', [AgentOrderController::class, 'store']);
        Route::get('orders/{order}', [AgentOrderController::class, 'show']);
        Route::post('orders/{order}/status', [AgentOrderController::class, 'updateStatus']);
    });

    // 판매점(Seller) + 본사(hq_admin)
    Route::middleware('api.role:seller,hq_admin')->prefix('seller')->group(function () {
        Route::get('dashboard', [SellerDashboardController::class, 'index']);
        Route::get('products', [SellerProductController::class, 'index']);
        Route::post('products', [SellerProductController::class, 'store']);
        Route::put('products/{product}', [SellerProductController::class, 'update']);
        Route::delete('products/{product}', [SellerProductController::class, 'destroy']);
        Route::get('orders', [SellerOrderController::class, 'index']);
    });
});
