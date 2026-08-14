<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Order\UpdateOrderStatusRequest;
use App\Models\Admin;
use App\Models\Order;
use App\Services\Admin\Order\UpdateOrderStatusService;
use Illuminate\Http\RedirectResponse;

class OrderStatusController extends Controller
{
    public function update(
        UpdateOrderStatusRequest $request,
        Order $order,
        UpdateOrderStatusService $service,
    ): RedirectResponse {
        /** @var Admin|null $admin */
        $admin = $request->user('admin');

        $service->update($order, $request->enum('status', OrderStatus::class), $admin);

        return redirect()->route('admin.orders.show', $order);
    }
}
