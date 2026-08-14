<?php

declare(strict_types=1);

namespace App\Http\Controllers\Front\MyPage;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Front\Order\CancelOrderService;
use Illuminate\Http\RedirectResponse;

class OrderCancelController extends Controller
{
    public function store(Order $order, CancelOrderService $service): RedirectResponse
    {
        if (! $service->cancel($order)) {
            return back()->with('error', 'この注文はキャンセルできません');
        }

        return redirect()
            ->route('mypage.orders.show', [$order])
            ->with('success', '注文をキャンセルしました');
    }
}
