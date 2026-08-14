<?php

declare(strict_types=1);

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Requests\Front\Checkout\StoreCheckoutRequest;
use App\Models\User;
use App\Services\Front\Checkout\CheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CheckoutController extends Controller
{
    public function __construct(private readonly CheckoutService $service) {}

    public function index(Request $request): Response|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user('web');
        $rows = $this->service->cartRows($user);

        if ($reason = $this->service->blockingReason($rows)) {
            return redirect()->route('cart.index')->with('error', $reason);
        }

        return Inertia::render('front/Checkout/Index', $this->service->buildIndex($user, $rows));
    }

    public function store(StoreCheckoutRequest $request): RedirectResponse
    {
        $request->session()->put([
            CheckoutService::SESSION_ADDRESS_ID => $request->integer('address_id'),
            CheckoutService::SESSION_PAYMENT_METHOD => $request->string('payment_method')->value(),
        ]);

        return redirect()->route('checkout.confirm');
    }

    public function confirm(Request $request): Response|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user('web');
        $rows = $this->service->cartRows($user);

        if ($reason = $this->service->blockingReason($rows)) {
            return redirect()->route('cart.index')->with('error', $reason);
        }

        $address = $this->service->selectedAddress($user);
        $paymentMethod = $this->service->selectedPaymentMethod();

        // セッションを持たずに直接開かれた場合と、選択後に住所が削除された場合は購入手続きからやり直す
        if (! $address || ! $paymentMethod) {
            return redirect()->route('checkout.index');
        }

        return Inertia::render(
            'front/Checkout/Confirm',
            $this->service->buildConfirm($rows, $address, $paymentMethod),
        );
    }
}
