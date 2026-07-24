<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Api\Admin\PartnerController as AdminPartnerController;
use App\Http\Controllers\Api\Admin\PayoutController as AdminPayoutController;
use App\Http\Controllers\Api\Agent\ClientController as AgentClientController;
use App\Http\Controllers\Api\Agent\DashboardController as AgentDashboardController;
use App\Http\Controllers\Api\Agent\OrderController as AgentOrderController;
use App\Http\Controllers\Api\Purchaser\BuyerController as PurchaserBuyerController;
use App\Http\Controllers\Api\Purchaser\DashboardController as PurchaserDashboardController;
use App\Http\Controllers\Api\Purchaser\OrderController as PurchaserOrderController;
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

    // 구매처(Purchaser)
    Route::middleware('api.role:purchaser')->prefix('purchaser')->group(function () {
        Route::get('dashboard', [PurchaserDashboardController::class, 'index']);
        Route::get('buyers', [PurchaserBuyerController::class, 'index']);
        Route::post('buyers', [PurchaserBuyerController::class, 'store']);
        Route::put('buyers/{buyer}', [PurchaserBuyerController::class, 'update']);
        Route::delete('buyers/{buyer}', [PurchaserBuyerController::class, 'destroy']);

        Route::get('orders', [PurchaserOrderController::class, 'index']);
        Route::get('orders/search-products', [PurchaserOrderController::class, 'searchProducts']);
        Route::post('orders', [PurchaserOrderController::class, 'store']);
        Route::get('orders/{order}', [PurchaserOrderController::class, 'show']);
        Route::post('orders/{order}/status', [PurchaserOrderController::class, 'updateStatus']);
    });

    // 본사(hq_admin) 관리 콘솔
    Route::middleware('api.role:hq_admin')->prefix('admin')->group(function () {
        Route::get('dashboard', [AdminDashboardController::class, 'index']);

        Route::get('sellers', [AdminPartnerController::class, 'sellers']);
        Route::post('sellers/{seller}/status', [AdminPartnerController::class, 'updateSellerStatus']);

        Route::get('agents', [AdminPartnerController::class, 'agents']);
        Route::post('agents/{agent}/status', [AdminPartnerController::class, 'updateAgentStatus']);
        Route::post('agents/{agent}/rate', [AdminPartnerController::class, 'updateAgentRate']);

        Route::get('purchasers', [AdminPartnerController::class, 'purchasers']);
        Route::post('purchasers/{purchaser}/status', [AdminPartnerController::class, 'updatePurchaserStatus']);
        Route::post('purchasers/{purchaser}/rate', [AdminPartnerController::class, 'updatePurchaserRate']);

        Route::get('commissions', [AdminPayoutController::class, 'commissions']);
        Route::post('commissions/{order}/pay', [AdminPayoutController::class, 'payCommission']);
        Route::get('cashbacks', [AdminPayoutController::class, 'cashbacks']);
        Route::post('cashbacks/{order}/pay', [AdminPayoutController::class, 'payCashback']);
    });

    // 판매점(Seller) + 본사(hq_admin 직영 스토어)
    Route::middleware('api.role:seller,hq_admin')->prefix('seller')->group(function () {
        Route::get('dashboard', [SellerDashboardController::class, 'index']);
        Route::get('products', [SellerProductController::class, 'index']);
        Route::post('products', [SellerProductController::class, 'store']);
        Route::put('products/{product}', [SellerProductController::class, 'update']);
        Route::delete('products/{product}', [SellerProductController::class, 'destroy']);
        Route::get('orders', [SellerOrderController::class, 'index']);
    });
});
