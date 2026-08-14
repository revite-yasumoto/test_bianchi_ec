<?php

declare(strict_types=1);

namespace App\Actions\Front\Address;

use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Support\Facades\DB;

class StoreUserAddress
{
    /**
     * 配送先を追加する。既定に指定した場合は他の住所の既定を解除し、既定が常に1件以下になるようにする。
     *
     * @param  array<string, mixed>  $attributes
     */
    public function __invoke(User $user, array $attributes): UserAddress
    {
        return DB::transaction(function () use ($user, $attributes): UserAddress {
            /** @var UserAddress $address */
            $address = $user->addresses()->create($attributes);

            if ($address->is_default) {
                $user->addresses()
                    ->whereKeyNot($address->id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }

            return $address;
        });
    }
}
