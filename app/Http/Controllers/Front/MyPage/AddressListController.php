<?php

declare(strict_types=1);

namespace App\Http\Controllers\Front\MyPage;

use App\Http\Controllers\Controller;
use App\Models\Prefecture;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AddressListController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user('web');

        $addresses = $user->addresses()
            ->with('prefecture')
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->get();

        return Inertia::render('front/MyPage/Addresses', [
            'addresses' => $addresses->map(fn (UserAddress $address): array => [
                'id' => $address->id,
                'label' => $address->label,
                'recipient_name' => $address->recipient_name,
                'postal_code' => $address->postal_code,
                'prefecture_id' => $address->prefecture_id,
                'prefecture_name' => $address->prefecture->name,
                'city' => $address->city,
                'address_line1' => $address->address_line1,
                'address_line2' => $address->address_line2,
                'tel' => $address->tel,
                'is_default' => $address->is_default,
            ])->all(),
            'prefectures' => Prefecture::query()->orderBy('id')->get(['id', 'name'])->all(),
        ]);
    }
}
