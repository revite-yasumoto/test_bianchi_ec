<?php

declare(strict_types=1);

namespace App\Actions\Front\Auth;

use App\Enums\UserStatus;
use App\Mail\Front\RegistrationCompleted;
use App\Models\User;
use App\Services\Mail\NotificationMailer;

class RegisterUser
{
    public function __construct(
        private readonly GenerateMemberCode $generateMemberCode,
        private readonly NotificationMailer $notificationMailer,
    ) {}

    /**
     * @param  array{name: string, name_kana: string|null, email: string, password: string}  $attributes
     */
    public function __invoke(array $attributes): User
    {
        $user = User::query()->create([
            ...$attributes,
            'member_code' => ($this->generateMemberCode)(),
            'status' => UserStatus::Active,
        ]);

        $this->notificationMailer->send($user->email, new RegistrationCompleted($user));

        return $user;
    }
}
