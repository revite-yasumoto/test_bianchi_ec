<?php

declare(strict_types=1);

namespace App\Http\Controllers\Front\MyPage;

use App\Actions\Front\MyPage\WithdrawUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Front\MyPage\WithdrawRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class WithdrawalController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('front/MyPage/Withdrawal');
    }

    public function store(WithdrawRequest $request, WithdrawUser $withdrawUser): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user('web');

        $withdrawUser($user);

        // ログアウトは記憶トークンも作り直すため、他端末での自動ログインもここで断たれる
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('top')
            ->with('success', '退会手続きが完了しました。ご利用ありがとうございました。');
    }
}
