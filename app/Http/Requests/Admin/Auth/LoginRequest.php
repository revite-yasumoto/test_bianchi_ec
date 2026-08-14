<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'login_id' => ['required', 'string', 'max:191'],
            'password' => ['required', 'string', 'min:8'],
            'remember' => ['nullable', 'boolean'],
        ];
    }

    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $loginId = (string) $this->input('login_id');
        $field = str_contains($loginId, '@') ? 'email' : 'admin_code';

        $attempted = Auth::guard('admin')->attempt(
            [$field => $loginId, 'password' => (string) $this->input('password')],
            $this->boolean('remember'),
        );

        if (! $attempted) {
            RateLimiter::hit($this->throttleKey(), 60);

            throw ValidationException::withMessages([
                'login_id' => '管理者アカウントが見つからないか、パスワードが誤っています',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'login_id' => "ログイン試行回数が上限に達しました。{$seconds}秒後に再度お試しください。",
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower((string) $this->input('login_id')).'|'.$this->ip());
    }
}
