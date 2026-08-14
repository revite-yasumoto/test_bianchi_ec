<?php

declare(strict_types=1);

namespace App\Http\Controllers\Front\MyPage;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Front\MyPage\WithdrawRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class WithdrawalController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('front/MyPage/Withdrawal');
    }

    public function store(WithdrawRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user('web');

        // 記憶トークンを作り直し、他端末の「ログイン状態を保持」による再ログインを断つ
        $user->update([
            'status' => UserStatus::Withdrawn,
            'remember_token' => Str::random(60),
        ]);

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('top')
            ->with('success', '退会手続きが完了しました。ご利用ありがとうございました。');
    }
}
