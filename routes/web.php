<?php

use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminUserCsvController;
use App\Http\Controllers\Admin\Auth\AuthenticatedSessionController as AdminAuthenticatedSessionController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ContactController as AdminContactController;
use App\Http\Controllers\Admin\ContactCsvController;
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
use App\Http\Controllers\Front\Auth\NewPasswordController;
use App\Http\Controllers\Front\Auth\PasswordResetLinkController;
use App\Http\Controllers\Front\Auth\RegisteredUserController;
use App\Http\Controllers\Front\CartController;
use App\Http\Controllers\Front\CartItemController;
use App\Http\Controllers\Front\CheckoutController;
use App\Http\Controllers\Front\ContactController;
use App\Http\Controllers\Front\FavoriteController;
use App\Http\Controllers\Front\MyPage\AddressListController;
use App\Http\Controllers\Front\MyPage\FavoriteListController;
use App\Http\Controllers\Front\MyPage\OrderCancelController;
use App\Http\Controllers\Front\MyPage\OrderHistoryController;
use App\Http\Controllers\Front\MyPage\PasswordController;
use App\Http\Controllers\Front\MyPage\ProfileController;
use App\Http\Controllers\Front\MyPage\WithdrawalController;
use App\Http\Controllers\Front\NewsController as FrontNewsController;
use App\Http\Controllers\Front\NoticeController as FrontNoticeController;
use App\Http\Controllers\Front\OrderController as FrontOrderController;
use App\Http\Controllers\Front\PostalCodeController;
use App\Http\Controllers\Front\ProductController as FrontProductController;
use App\Http\Controllers\Front\StaticPageController;
use App\Http\Controllers\Front\TopController;
use App\Http\Controllers\Front\UserAddressController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::middleware('guest:admin')->group(function (): void {
        Route::get('login', [AdminAuthenticatedSessionController::class, 'create'])->name('login');
        Route::post('login', [AdminAuthenticatedSessionController::class, 'store'])->name('login.store');
    });

    Route::middleware('auth:admin')->group(function (): void {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::post('logout', [AdminAuthenticatedSessionController::class, 'destroy'])->name('logout');

        // CSVのルートは `{user}` `{admin}` `{product}` `{contact}` として解釈されないよう、各リソースより先に定義する
        Route::post('members/csv/import', [MemberCsvController::class, 'import'])->name('members.csv.import');
        Route::get('members/csv/export', [MemberCsvController::class, 'export'])->name('members.csv.export');
        Route::post('admins/csv/import', [AdminUserCsvController::class, 'import'])->name('admins.csv.import');
        Route::get('admins/csv/export', [AdminUserCsvController::class, 'export'])->name('admins.csv.export');
        Route::get('products/csv', [ProductCsvController::class, 'index'])->name('products.csv.index');
        Route::post('products/csv/import', [ProductCsvController::class, 'import'])->name('products.csv.import');
        Route::get('products/csv/export', [ProductCsvController::class, 'export'])->name('products.csv.export');
        Route::get('products/csv/template', [ProductCsvController::class, 'template'])->name('products.csv.template');
        Route::get('contacts/csv/export', [ContactCsvController::class, 'export'])->name('contacts.csv.export');

        Route::get('members', [MemberController::class, 'index'])->name('members.index');
        Route::get('members/{user}', [MemberController::class, 'show'])->name('members.show');
        Route::put('members/{user}/status', [MemberStatusController::class, 'update'])->name('members.status.update');

        Route::resource('admins', AdminUserController::class)->except(['show'])->parameters(['admins' => 'admin']);

        Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::put('orders/{order}/status', [OrderStatusController::class, 'update'])->name('orders.status.update');

        Route::resource('products', ProductController::class)->except(['show']);

        Route::resource('contacts', AdminContactController::class)->only(['index', 'show', 'update']);

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

Route::get('news', [FrontNewsController::class, 'index'])->name('news.index');
Route::get('news/{news}', [FrontNewsController::class, 'show'])->name('news.show');
Route::get('notices', [FrontNoticeController::class, 'index'])->name('notices.index');
Route::get('notices/{notice}', [FrontNoticeController::class, 'show'])->name('notices.show');

Route::get('guide', [StaticPageController::class, 'guide'])->name('guide');
Route::get('legal/tokushoho', [StaticPageController::class, 'tokushoho'])->name('legal.tokushoho');
Route::get('legal/privacy', [StaticPageController::class, 'privacy'])->name('legal.privacy');
Route::get('legal/terms', [StaticPageController::class, 'terms'])->name('legal.terms');

Route::get('contact', [ContactController::class, 'create'])->name('contact');
// 未認証で投稿できるため、同一IPからの連投を抑える
Route::post('contact', [ContactController::class, 'store'])
    ->name('contact.store')
    ->middleware('throttle:10,60');

Route::middleware('guest')->group(function (): void {
    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    // 1リクエストで会員の作成とメール送信が起きるため、連投を抑える
    Route::post('register', [RegisteredUserController::class, 'store'])
        ->name('register.store')
        ->middleware('throttle:5,60');
    Route::get('register/complete', [RegisteredUserController::class, 'complete'])->name('register.complete');
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store'])->name('login.store');

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])->name('password.update');
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

    Route::get('checkout', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('checkout', [CheckoutController::class, 'store'])->name('checkout.store');
    Route::get('checkout/confirm', [CheckoutController::class, 'confirm'])->name('checkout.confirm');

    Route::post('orders', [FrontOrderController::class, 'store'])->name('orders.store');
    Route::get('orders/{order}/complete', [FrontOrderController::class, 'complete'])
        ->name('orders.complete')
        ->can('view', 'order');

    Route::post('addresses', [UserAddressController::class, 'store'])->name('addresses.store');
    Route::put('addresses/{address}', [UserAddressController::class, 'update'])
        ->name('addresses.update')
        ->can('update', 'address');
    Route::delete('addresses/{address}', [UserAddressController::class, 'destroy'])
        ->name('addresses.destroy')
        ->can('delete', 'address');

    Route::get('postal-codes/{postalCode}', PostalCodeController::class)
        ->name('postal-codes.show')
        ->where('postalCode', '[0-9]{7}')
        ->middleware('throttle:60,1');

    Route::get('mypage', [OrderHistoryController::class, 'index'])->name('mypage.index');
    Route::get('mypage/orders/{order}', [OrderHistoryController::class, 'show'])
        ->name('mypage.orders.show')
        ->can('view', 'order');
    Route::post('mypage/orders/{order}/cancel', [OrderCancelController::class, 'store'])
        ->name('mypage.orders.cancel')
        ->can('cancel', 'order');
    Route::get('mypage/favorites', [FavoriteListController::class, 'index'])->name('mypage.favorites');
    Route::get('mypage/addresses', [AddressListController::class, 'index'])->name('mypage.addresses');
    Route::get('mypage/profile', [ProfileController::class, 'edit'])->name('mypage.profile');
    Route::put('mypage/profile', [ProfileController::class, 'update'])->name('mypage.profile.update');
    Route::get('mypage/withdrawal', [WithdrawalController::class, 'create'])->name('mypage.withdrawal');
    Route::post('mypage/withdrawal', [WithdrawalController::class, 'store'])->name('mypage.withdrawal.store');
    Route::get('mypage/password', [PasswordController::class, 'edit'])->name('mypage.password');
    Route::put('mypage/password', [PasswordController::class, 'update'])->name('mypage.password.update');
});
