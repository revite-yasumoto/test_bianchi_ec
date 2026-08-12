<?php

use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminUserCsvController;
use App\Http\Controllers\Admin\Auth\AuthenticatedSessionController as AdminAuthenticatedSessionController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EcSettingController;
use App\Http\Controllers\Admin\MemberController;
use App\Http\Controllers\Admin\MemberCsvController;
use App\Http\Controllers\Admin\MemberStatusController;
use App\Http\Controllers\Admin\NewsController;
use App\Http\Controllers\Admin\NoticeController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\OrderStatusController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductCsvController;
use App\Http\Controllers\Admin\ShippingSettingController;
use App\Http\Controllers\Admin\SpecOptionController;
use App\Http\Controllers\Admin\StockController;
use App\Http\Controllers\Front\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Front\Auth\RegisteredUserController;
use App\Http\Controllers\Front\CartController;
use App\Http\Controllers\Front\CartItemController;
use App\Http\Controllers\Front\FavoriteController;
use App\Http\Controllers\Front\ProductController as FrontProductController;
use App\Http\Controllers\Front\TopController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::middleware('guest:admin')->group(function (): void {
        Route::get('login', [AdminAuthenticatedSessionController::class, 'create'])->name('login');
        Route::post('login', [AdminAuthenticatedSessionController::class, 'store'])->name('login.store');
    });

    Route::middleware('auth:admin')->group(function (): void {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::post('logout', [AdminAuthenticatedSessionController::class, 'destroy'])->name('logout');

        // CSVのルートは `{user}` `{admin}` `{product}` として解釈されないよう、各リソースより先に定義する
        Route::post('members/csv/import', [MemberCsvController::class, 'import'])->name('members.csv.import');
        Route::get('members/csv/export', [MemberCsvController::class, 'export'])->name('members.csv.export');
        Route::post('admins/csv/import', [AdminUserCsvController::class, 'import'])->name('admins.csv.import');
        Route::get('admins/csv/export', [AdminUserCsvController::class, 'export'])->name('admins.csv.export');
        Route::get('products/csv', [ProductCsvController::class, 'index'])->name('products.csv.index');
        Route::post('products/csv/import', [ProductCsvController::class, 'import'])->name('products.csv.import');
        Route::get('products/csv/export', [ProductCsvController::class, 'export'])->name('products.csv.export');
        Route::get('products/csv/template', [ProductCsvController::class, 'template'])->name('products.csv.template');

        Route::get('members', [MemberController::class, 'index'])->name('members.index');
        Route::get('members/{user}', [MemberController::class, 'show'])->name('members.show');
        Route::put('members/{user}/status', [MemberStatusController::class, 'update'])->name('members.status.update');

        Route::resource('admins', AdminUserController::class)->except(['show'])->parameters(['admins' => 'admin']);

        Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::put('orders/{order}/status', [OrderStatusController::class, 'update'])->name('orders.status.update');

        Route::resource('products', ProductController::class)->except(['show']);

        // bulk は {stock} にマッチしてしまうため、個別更新より先に定義する
        Route::put('stocks/bulk', [StockController::class, 'bulkUpdate'])->name('stocks.bulk-update');
        Route::get('stocks', [StockController::class, 'index'])->name('stocks.index');
        Route::put('stocks/{stock}', [StockController::class, 'update'])->name('stocks.update');

        Route::get('shipping-settings', [ShippingSettingController::class, 'index'])->name('shipping-settings.index');
        Route::put('shipping-settings', [ShippingSettingController::class, 'update'])->name('shipping-settings.update');

        Route::get('ec-settings', [EcSettingController::class, 'edit'])->name('ec-settings.edit');
        Route::put('ec-settings', [EcSettingController::class, 'update'])->name('ec-settings.update');

        Route::resource('news', NewsController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('notices', NoticeController::class)->only(['index', 'store', 'update', 'destroy']);

        Route::resource('categories', CategoryController::class)->only(['index', 'store', 'destroy']);
        // 既定のパラメータ名 spec_option ではメソッド引数 $specOption とバインドされないため明示する
        Route::resource('spec-options', SpecOptionController::class)
            ->only(['index', 'store', 'destroy'])
            ->parameters(['spec-options' => 'specOption']);
    });
});

Route::get('/', [TopController::class, 'index'])->name('top');

// 商品の閲覧は未ログインでも可能。購入導線（カート投入・お気に入り）からログインを求める
Route::get('products', [FrontProductController::class, 'index'])->name('products.index');
Route::get('products/{product}', [FrontProductController::class, 'show'])->name('products.show');

Route::middleware('guest')->group(function (): void {
    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store'])->name('register.store');
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('cart/items', [CartItemController::class, 'store'])->name('cart.items.store');
    Route::put('cart/items/{cartItem}', [CartItemController::class, 'update'])
        ->name('cart.items.update')
        ->can('update', 'cartItem');
    Route::delete('cart/items/{cartItem}', [CartItemController::class, 'destroy'])
        ->name('cart.items.destroy')
        ->can('delete', 'cartItem');

    Route::post('favorites', [FavoriteController::class, 'store'])->name('favorites.store');
    Route::delete('favorites/{product}', [FavoriteController::class, 'destroy'])->name('favorites.destroy');
});
