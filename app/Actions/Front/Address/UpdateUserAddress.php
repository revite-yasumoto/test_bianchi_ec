<?php

declare(strict_types=1);

namespace App\Actions\Front\Address;

use App\Models\UserAddress;
use Illuminate\Support\Facades\DB;

class UpdateUserAddress
{
    /**
     * 配送先を更新する。既定に指定した場合は他の住所の既定を解除し、既定が常に1件以下になるようにする。
     *
     * @param  array<string, mixed>  $attributes
     */
    public function __invoke(UserAddress $address, array $attributes): void
    {
        DB::transaction(function () use ($address, $attributes): void {
            $address->update($attributes);

            if ($address->is_default) {
                UserAddress::query()
                    ->where('user_id', $address->user_id)
                    ->whereKeyNot($address->id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }
        });
    }
}
