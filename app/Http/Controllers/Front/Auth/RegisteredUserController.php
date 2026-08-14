<?php

declare(strict_types=1);

namespace App\Http\Controllers\Front\Auth;

use App\Actions\Front\Auth\RegisterUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Front\Auth\RegisterRequest;
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

    public function store(RegisterRequest $request, RegisterUser $registerUser): RedirectResponse
    {
        $user = $registerUser([
            'name' => $request->string('name')->toString(),
            'name_kana' => $request->input('name_kana'),
            'email' => $request->string('email')->toString(),
            'password' => $request->string('password')->toString(),
        ]);

        Auth::guard('web')->login($user);

        $request->session()->regenerate();

        return redirect()->intended(route('top'))
            ->with('success', '会員登録が完了しました。');
    }
}
