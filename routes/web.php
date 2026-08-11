<?php

use App\Http\Controllers\Admin\Auth\AuthenticatedSessionController as AdminAuthenticatedSessionController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\HomeController as AdminHomeController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\OrderStatusController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\SpecOptionController;
use App\Http\Controllers\Admin\StockController;
use App\Http\Controllers\Front\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Front\Auth\RegisteredUserController;
use App\Http\Controllers\Front\TopController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::middleware('guest:admin')->group(function (): void {
        Route::get('login', [AdminAuthenticatedSessionController::class, 'create'])->name('login');
        Route::post('login', [AdminAuthenticatedSessionController::class, 'store'])->name('login.store');
    });

    Route::middleware('auth:admin')->group(function (): void {
        Route::get('/', [AdminHomeController::class, 'index'])->name('home');
        Route::post('logout', [AdminAuthenticatedSessionController::class, 'destroy'])->name('logout');

        Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::put('orders/{order}/status', [OrderStatusController::class, 'update'])->name('orders.status.update');

        Route::resource('products', ProductController::class)->except(['show']);

        // bulk は {stock} にマッチしてしまうため、個別更新より先に定義する
        Route::put('stocks/bulk', [StockController::class, 'bulkUpdate'])->name('stocks.bulk-update');
        Route::get('stocks', [StockController::class, 'index'])->name('stocks.index');
        Route::put('stocks/{stock}', [StockController::class, 'update'])->name('stocks.update');

        Route::resource('categories', CategoryController::class)->only(['index', 'store', 'destroy']);
        // 既定のパラメータ名 spec_option ではメソッド引数 $specOption とバインドされないため明示する
        Route::resource('spec-options', SpecOptionController::class)
            ->only(['index', 'store', 'destroy'])
            ->parameters(['spec-options' => 'specOption']);
    });
});

// 単位14でTOPページの中身を実装するまでは、共通レイアウトの表示とログイン後の遷移先を兼ねる暫定ページを返す
Route::get('/', [TopController::class, 'index'])->name('top');

Route::middleware('guest')->group(function (): void {
    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store'])->name('register.store');
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
