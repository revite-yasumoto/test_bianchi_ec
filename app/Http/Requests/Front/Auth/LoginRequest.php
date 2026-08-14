<?php

declare(strict_types=1);

namespace App\Http\Requests\Front\Auth;

use App\Enums\UserStatus;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /** ロックに至る連続失敗の回数 */
    private const MAX_FAILED_ATTEMPTS = 3;

    /** ロックが自動で解ける時間（分） */
    private const LOCK_MINUTES = 60;

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
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ];
    }

    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        // レート制限はメールアドレス＋IPで数えるため、送信元を変えた総当たりはアカウント単位で止める
        $target = User::query()->where('email', $this->string('email')->toString())->first();

        $this->ensureIsNotLocked($target);

        $attempted = Auth::guard('web')->attempt(
            $this->only('email', 'password'),
            $this->boolean('remember'),
        );

        if (! $attempted) {
            RateLimiter::hit($this->throttleKey(), 60);
            $this->recordFailedAttempt($target);

            throw ValidationException::withMessages([
                'email' => 'メールアドレスまたはパスワードが誤っています。',
            ]);
        }

        /** @var User $user */
        $user = Auth::guard('web')->user();

        if ($user->status === UserStatus::Suspended) {
            Auth::guard('web')->logout();

            // 資格情報自体は正しいため、失敗の記録は残さない
            $user->update(['failed_login_attempts' => 0, 'locked_until' => null]);

            throw ValidationException::withMessages([
                'email' => 'アカウントが利用停止中です。お問い合わせください。',
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        $user->update(['failed_login_attempts' => 0, 'locked_until' => null]);
    }

    private function ensureIsNotLocked(?User $user): void
    {
        if ($user?->locked_until === null || $user->locked_until->isPast()) {
            return;
        }

        throw ValidationException::withMessages([
            'email' => $this->lockedMessage($user->locked_until),
        ]);
    }

    /**
     * 失敗を数え、上限に達したらロックする。存在しないアドレスでは何もしない。
     *
     * 読み取りと書き込みを行ロックで挟む。挟まないと、同時に届いた試行がどれも同じ回数を
     * 読んで同じ値を書き、上限に達するまでのラウンド数だけ試行できてしまう。
     */
    private function recordFailedAttempt(?User $user): void
    {
        if ($user === null) {
            return;
        }

        $lockedUntil = DB::transaction(function () use ($user): ?CarbonInterface {
            $target = User::query()->whereKey($user->getKey())->lockForUpdate()->first();

            if ($target === null) {
                return null;
            }

            $attempts = $target->failed_login_attempts + 1;

            if ($attempts < self::MAX_FAILED_ATTEMPTS) {
                $target->update(['failed_login_attempts' => $attempts]);

                return null;
            }

            $until = now()->addMinutes(self::LOCK_MINUTES);
            $target->update(['failed_login_attempts' => 0, 'locked_until' => $until]);

            return $until;
        });

        if ($lockedUntil === null) {
            return;
        }

        throw ValidationException::withMessages([
            'email' => $this->lockedMessage($lockedUntil),
        ]);
    }

    private function lockedMessage(CarbonInterface $lockedUntil): string
    {
        $minutes = max(1, (int) ceil(abs(now()->diffInSeconds($lockedUntil)) / 60));

        return "アカウントを一時的にロックしています。約{$minutes}分後に再度お試しください。";
    }

    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => "ログイン試行回数が上限に達しました。{$seconds}秒後に再度お試しください。",
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower((string) $this->input('email')).'|'.$this->ip());
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'email' => 'メールアドレス',
            'password' => 'パスワード',
        ];
    }
}
