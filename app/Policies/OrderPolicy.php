<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function view(User $user, Order $order): bool
    {
        return $order->user_id === $user->id;
    }

    /**
     * ステータスによるキャンセル可否は CancelOrderService が判定する。ここは所有者だけを見る。
     */
    public function cancel(User $user, Order $order): bool
    {
        return $order->user_id === $user->id;
    }
}
