<?php

declare(strict_types=1);

namespace App\Http\Controllers\Front\Auth;

use App\Actions\Front\Auth\GenerateMemberCode;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Front\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('front/Auth/Register');
    }

    public function store(RegisterRequest $request, GenerateMemberCode $generateMemberCode): RedirectResponse
    {
        $user = User::query()->create([
            'member_code' => $generateMemberCode(),
            'name' => $request->string('name')->toString(),
            'name_kana' => $request->input('name_kana'),
            'email' => $request->string('email')->toString(),
            'password' => $request->string('password')->toString(),
            'status' => UserStatus::Active,
        ]);

        Auth::guard('web')->login($user);

        $request->session()->regenerate();

        return redirect()->intended(route('top'))
            ->with('success', '会員登録が完了しました。');
    }
}
