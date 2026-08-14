<?php

declare(strict_types=1);

namespace App\Actions\Front\MyPage;

use App\Enums\UserStatus;
use App\Mail\Front\WithdrawalCompleted;
use App\Models\User;
use App\Services\Mail\NotificationMailer;

class WithdrawUser
{
    public function __construct(private readonly NotificationMailer $notificationMailer) {}

    public function __invoke(User $user): void
    {
        $user->update(['status' => UserStatus::Withdrawn]);

        $this->notificationMailer->send($user->email, new WithdrawalCompleted($user));
    }
}
