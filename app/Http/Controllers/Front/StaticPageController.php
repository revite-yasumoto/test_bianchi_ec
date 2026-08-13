<?php

declare(strict_types=1);

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\ShippingSetting;
use App\Services\Setting\EcSettingProvider;
use Inertia\Inertia;
use Inertia\Response;

class StaticPageController extends Controller
{
    /**
     * 買い物ガイドの送料・支払い方法の案内は、設定変更が反映されるよう現在値から組み立てる。
     */
    public function guide(EcSettingProvider $ecSettingProvider): Response
    {
        $ecSetting = $ecSettingProvider->get();

        return Inertia::render('front/Static/Guide', [
            'shippingTable' => $this->shippingTable(),
            'ecSetting' => [
                'free_shipping_threshold' => $ecSetting->free_shipping_threshold,
                'cod_fee' => $ecSetting->cod_fee,
            ],
        ]);
    }

    public function tokushoho(): Response
    {
        return Inertia::render('front/Static/Tokushoho');
    }

    public function privacy(): Response
    {
        return Inertia::render('front/Static/Privacy');
    }

    public function terms(): Response
    {
        return Inertia::render('front/Static/Terms');
    }

    /**
     * @return array<int, array{prefecture_name: string, fee: int, delivery_days: int}>
     */
    private function shippingTable(): array
    {
        return ShippingSetting::query()
            ->with('prefecture')
            ->orderBy('prefecture_id')
            ->get()
            ->map(fn (ShippingSetting $setting): array => [
                'prefecture_name' => $setting->prefecture->name,
                'fee' => $setting->fee,
                'delivery_days' => $setting->delivery_days,
            ])
            ->all();
    }
}
