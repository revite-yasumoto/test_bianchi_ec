<?php

use App\Http\Controllers\Admin\Auth\AuthenticatedSessionController;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);

        // 未ログインで auth:admin ルートへアクセスした場合の遷移先
        // 'admin/*' は 'admin'（末尾セグメント無し。例: admin.home）にマッチしないため個別に判定する
        $middleware->redirectGuestsTo(
            fn (Request $request) => $request->is('admin/*') || $request->is('admin')
                ? route('admin.login')
                : route('login'),
        );

        // 認証済みで guest:admin ルート（管理者ログイン画面）へアクセスした場合の遷移先
        $middleware->redirectUsersTo(
            fn (Request $request) => $request->is('admin/*') || $request->is('admin')
                ? AuthenticatedSessionController::landingUrl()
                : route('dashboard'),
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
