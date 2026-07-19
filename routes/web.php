<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\Agent\ClientController as AgentClient;
use App\Http\Controllers\Agent\DashboardController as AgentDashboard;
use App\Http\Controllers\Agent\OrderController as AgentOrder;
use App\Http\Controllers\Purchaser\BuyerController as PurchaserBuyer;
use App\Http\Controllers\Purchaser\DashboardController as PurchaserDashboard;
use App\Http\Controllers\Purchaser\OrderController as PurchaserOrder;
use App\Http\Controllers\CartController;
use App\Http\Controllers\Manage\AuthController as ManageAuth;
use App\Http\Controllers\Manage\OrderController as ManageOrder;
use App\Http\Controllers\Manage\PartnerController;
use App\Http\Controllers\Manage\ProductController as ManageProduct;
use App\Http\Controllers\SellerController;
use App\Http\Controllers\ShopController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ShopController::class, 'home'])->name('home');
Route::get('/search', [ShopController::class, 'search'])->name('search');

Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/add/{product}', [CartController::class, 'add'])->name('cart.add');
Route::patch('/cart/update/{product}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/remove/{product}', [CartController::class, 'remove'])->name('cart.remove');

Route::get('/category/{category}', [ShopController::class, 'category'])->name('category.show');
Route::get('/product/{product}', [ShopController::class, 'product'])->name('product.show');

/*
|--------------------------------------------------------------------------
| 관리 영역 (본사 / 입점 판매점)
|--------------------------------------------------------------------------
*/

// 관리 로그인 (본사·판매점 공통)
Route::get('admin/login', [ManageAuth::class, 'showLogin'])->name('manage.login');
Route::post('admin/login', [ManageAuth::class, 'login'])->name('manage.login.post');
Route::post('manage/logout', [ManageAuth::class, 'logout'])->name('manage.logout');

// 입점 신청 (공개)
Route::get('partner/apply', [PartnerController::class, 'showApply'])->name('partner.apply');
Route::post('partner/apply', [PartnerController::class, 'apply'])->name('partner.apply.post');

// 협력사 신청 (공개)
Route::get('agent/apply', [PartnerController::class, 'showAgentApply'])->name('agent.apply');
Route::post('agent/apply', [PartnerController::class, 'agentApply'])->name('agent.apply.post');

// 구매 대행자 신청 (공개)
Route::get('purchaser/apply', [PartnerController::class, 'showPurchaserApply'])->name('purchaser.apply');
Route::post('purchaser/apply', [PartnerController::class, 'purchaserApply'])->name('purchaser.apply.post');

// 본사 콘솔
Route::prefix('admin')->middleware('role:hq_admin')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('admin.index');
    Route::get('sellers', [AdminController::class, 'sellers'])->name('admin.sellers');
    Route::post('sellers/{seller}/status', [AdminController::class, 'updateSellerStatus'])->name('admin.sellers.status');
    Route::get('agents', [AdminController::class, 'agents'])->name('admin.agents');
    Route::post('agents/{agent}/status', [AdminController::class, 'updateAgentStatus'])->name('admin.agents.status');
    Route::post('agents/{agent}/commission', [AdminController::class, 'updateAgentCommission'])->name('admin.agents.commission');
    Route::get('commissions', [AdminController::class, 'commissions'])->name('admin.commissions');
    Route::post('commissions/{order}/pay', [AdminController::class, 'payCommission'])->name('admin.commissions.pay');
    Route::get('purchasers', [AdminController::class, 'purchasers'])->name('admin.purchasers');
    Route::post('purchasers/{purchaser}/status', [AdminController::class, 'updatePurchaserStatus'])->name('admin.purchasers.status');
    Route::post('purchasers/{purchaser}/cashback', [AdminController::class, 'updatePurchaserCashback'])->name('admin.purchasers.cashback');
    Route::get('cashbacks', [AdminController::class, 'cashbacks'])->name('admin.cashbacks');
    Route::post('cashbacks/{order}/pay', [AdminController::class, 'payCashback'])->name('admin.cashbacks.pay');
    Route::get('login-logs', [AdminController::class, 'loginLogs'])->name('admin.login-logs');
    Route::get('settings', [AdminController::class, 'settings'])->name('admin.settings');
    Route::post('settings', [AdminController::class, 'updateSettings'])->name('admin.settings.update');
});

// 판매점 콘솔
Route::prefix('seller')->middleware('role:seller')->group(function () {
    Route::get('/', [SellerController::class, 'index'])->name('seller.index');
});

// 협력사(Agent) 콘솔
Route::prefix('agent')->middleware('role:agent')->group(function () {
    Route::get('/', [AgentDashboard::class, 'index'])->name('agent.index');

    Route::get('clients', [AgentClient::class, 'index'])->name('agent.clients.index');
    Route::get('clients/create', [AgentClient::class, 'create'])->name('agent.clients.create');
    Route::post('clients', [AgentClient::class, 'store'])->name('agent.clients.store');
    Route::get('clients/{client}/edit', [AgentClient::class, 'edit'])->name('agent.clients.edit');
    Route::put('clients/{client}', [AgentClient::class, 'update'])->name('agent.clients.update');
    Route::delete('clients/{client}', [AgentClient::class, 'destroy'])->name('agent.clients.destroy');

    Route::get('orders', [AgentOrder::class, 'index'])->name('agent.orders.index');
    Route::get('orders/create', [AgentOrder::class, 'create'])->name('agent.orders.create');
    Route::get('orders/search', [AgentOrder::class, 'searchProducts'])->name('agent.orders.search');
    Route::post('orders', [AgentOrder::class, 'store'])->name('agent.orders.store');
    Route::get('orders/{order}', [AgentOrder::class, 'show'])->name('agent.orders.show');
    Route::post('orders/{order}/status', [AgentOrder::class, 'updateStatus'])->name('agent.orders.status');
});

// 구매 대행자(Purchasing Agent) 콘솔
Route::prefix('purchaser')->middleware('role:purchaser')->group(function () {
    Route::get('/', [PurchaserDashboard::class, 'index'])->name('purchaser.index');

    Route::get('buyers', [PurchaserBuyer::class, 'index'])->name('purchaser.buyers.index');
    Route::get('buyers/create', [PurchaserBuyer::class, 'create'])->name('purchaser.buyers.create');
    Route::post('buyers', [PurchaserBuyer::class, 'store'])->name('purchaser.buyers.store');
    Route::get('buyers/{buyer}/edit', [PurchaserBuyer::class, 'edit'])->name('purchaser.buyers.edit');
    Route::put('buyers/{buyer}', [PurchaserBuyer::class, 'update'])->name('purchaser.buyers.update');
    Route::delete('buyers/{buyer}', [PurchaserBuyer::class, 'destroy'])->name('purchaser.buyers.destroy');

    Route::get('orders', [PurchaserOrder::class, 'index'])->name('purchaser.orders.index');
    Route::get('orders/create', [PurchaserOrder::class, 'create'])->name('purchaser.orders.create');
    Route::get('orders/search', [PurchaserOrder::class, 'searchProducts'])->name('purchaser.orders.search');
    Route::post('orders', [PurchaserOrder::class, 'store'])->name('purchaser.orders.store');
    Route::get('orders/{order}', [PurchaserOrder::class, 'show'])->name('purchaser.orders.show');
    Route::post('orders/{order}/status', [PurchaserOrder::class, 'updateStatus'])->name('purchaser.orders.status');
});

// 공통 상품/주문 관리 (본사 + 판매점, 각자 자기 스토어로 스코프)
Route::prefix('manage')->middleware('role:hq_admin,seller')->group(function () {
    Route::get('products', [ManageProduct::class, 'index'])->name('manage.products.index');
    Route::get('products/create', [ManageProduct::class, 'create'])->name('manage.products.create');
    Route::post('products', [ManageProduct::class, 'store'])->name('manage.products.store');
    Route::get('products/{product}/edit', [ManageProduct::class, 'edit'])->name('manage.products.edit');
    Route::put('products/{product}', [ManageProduct::class, 'update'])->name('manage.products.update');
    Route::delete('products/{product}', [ManageProduct::class, 'destroy'])->name('manage.products.destroy');
    Route::get('products/{product}/image', [ManageProduct::class, 'editImage'])->name('manage.products.image');
    Route::post('products/{product}/image', [ManageProduct::class, 'saveImage'])->name('manage.products.image.save');
    Route::get('orders', [ManageOrder::class, 'index'])->name('manage.orders');
});
