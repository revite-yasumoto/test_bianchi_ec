<?php

declare(strict_types=1);

namespace App\Actions\Front\Auth;

use App\Enums\UserStatus;
use App\Mail\Front\RegistrationCompleted;
use App\Models\User;
use App\Services\Mail\NotificationMailer;
use Illuminate\Support\Facades\DB;

class RegisterUser
{
    /** 会員登録で作る配送先の表示名。入力欄を置かないため固定値を入れる */
    private const DEFAULT_ADDRESS_LABEL = '自宅';

    public function __construct(
        private readonly GenerateMemberCode $generateMemberCode,
        private readonly NotificationMailer $notificationMailer,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes  会員の登録内容
     * @param  array<string, mixed>  $address  既定の配送先にする住所（宛名・電話番号・表示名は会員の入力から補う）
     */
    public function __invoke(array $attributes, array $address): User
    {
        $user = DB::transaction(function () use ($attributes, $address): User {
            /** @var User $user */
            $user = User::query()->create([
                ...$attributes,
                'member_code' => ($this->generateMemberCode)(),
                'status' => UserStatus::Active,
            ]);

            $user->addresses()->create([
                ...$address,
                'label' => self::DEFAULT_ADDRESS_LABEL,
                'recipient_name' => $user->name,
                'tel' => $user->tel,
                'is_default' => true,
            ]);

            return $user;
        });

        $this->notificationMailer->send($user->email, new RegistrationCompleted($user));

        return $user;
    }
}
