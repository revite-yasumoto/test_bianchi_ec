<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\UserAddress;

class UserAddressPolicy
{
    public function update(User $user, UserAddress $address): bool
    {
        return $address->user_id === $user->id;
    }

    /** 会員登録で作られた既定の配送先を残すため、既定は削除させない（画面のボタンを外しても DELETE は直接呼べる） */
    public function delete(User $user, UserAddress $address): bool
    {
        return $address->user_id === $user->id && ! $address->is_default;
    }
}
