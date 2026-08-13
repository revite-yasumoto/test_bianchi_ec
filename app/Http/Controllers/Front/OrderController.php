<?php

declare(strict_types=1);

namespace App\Http\Controllers\Front;

use App\Exceptions\OrderNotPlaceableException;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Services\Front\Checkout\CheckoutService;
use App\Services\Front\Order\PlaceOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function __construct(
        private readonly CheckoutService $checkoutService,
        private readonly PlaceOrderService $placeOrderService,
    ) {}

    public function store(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user('web');

        $address = $this->checkoutService->selectedAddress($user);
        $paymentMethod = $this->checkoutService->selectedPaymentMethod();

        if (! $address || ! $paymentMethod) {
            return redirect()->route('checkout.index');
        }

        try {
            $order = $this->placeOrderService->place($user, $address->id, $paymentMethod);
        } catch (OrderNotPlaceableException $exception) {
            return redirect()->route($exception->redirectRouteName)->with('error', $exception->getMessage());
        }

        $request->session()->forget([
            CheckoutService::SESSION_ADDRESS_ID,
            CheckoutService::SESSION_PAYMENT_METHOD,
        ]);

        // POST-Redirect-GET で二重注文を防ぐ。注文完了画面は注文IDで開くためリロードしても再注文にならない
        return redirect()->route('orders.complete', [$order]);
    }

    public function complete(Order $order): Response
    {
        return Inertia::render('front/Order/Complete', [
            'order' => [
                'order_number' => $order->order_number,
                'estimated_delivery_date' => $order->estimated_delivery_date->toDateString(),
                'payment_method' => $order->payment_method->value,
                'bank_transfer_note' => $order->bank_transfer_note,
            ],
        ]);
    }
}
