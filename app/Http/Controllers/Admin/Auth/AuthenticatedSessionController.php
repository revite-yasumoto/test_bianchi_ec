<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('admin/Auth/Login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        return redirect()->to(self::landingUrl());
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    /**
     * ログイン成功時／ログイン画面へのゲスト制限時の遷移先。
     * 単位07（注文管理）・単位12（ダッシュボード）が未実装の間は、
     * どちらの named route も存在しないため admin.home（暫定ホーム画面）へ遷移する。
     */
    public static function landingUrl(): string
    {
        return match (true) {
            Route::has('admin.dashboard') => route('admin.dashboard'),
            Route::has('admin.orders.index') => route('admin.orders.index'),
            default => route('admin.home'),
        };
    }
}
