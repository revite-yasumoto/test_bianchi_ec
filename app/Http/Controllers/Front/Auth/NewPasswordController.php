<?php

declare(strict_types=1);

namespace App\Http\Controllers\Front\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Front\Auth\NewPasswordRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class NewPasswordController extends Controller
{
    public function create(Request $request, string $token): Response
    {
        $email = $request->query('email');

        return Inertia::render('front/Auth/ResetPassword', [
            'token' => $token,
            'email' => is_string($email) ? $email : '',
        ]);
    }

    public function store(NewPasswordRequest $request): RedirectResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user) use ($request): void {
                $user->forceFill([
                    'password' => $request->string('password')->toString(),
                    'remember_token' => Str::random(60),
                ])->save();
            },
        );

        if ($status !== Password::PasswordReset) {
            // トークンの不一致・期限切れ・アドレスの不一致を区別せず返す
            throw ValidationException::withMessages([
                'email' => 'このリンクは無効か、有効期限が切れています。お手数ですが再度お申し込みください。',
            ]);
        }

        return redirect()->route('login')
            ->with('success', 'パスワードを変更しました。新しいパスワードでログインしてください。');
    }
}
