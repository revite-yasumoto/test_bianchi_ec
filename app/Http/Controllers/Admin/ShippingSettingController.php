<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\ShippingSetting\BulkUpdateShippingSettings;
use App\Enums\Region;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ShippingSetting\UpdateShippingSettingsRequest;
use App\Models\ShippingSetting;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ShippingSettingController extends Controller
{
    public function index(): Response
    {
        $settings = ShippingSetting::query()
            ->with('prefecture')
            ->orderBy('prefecture_id')
            ->get()
            ->map(fn (ShippingSetting $setting): array => [
                'id' => $setting->id,
                'prefecture_id' => $setting->prefecture_id,
                'prefecture_name' => $setting->prefecture->name,
                'region' => Region::of($setting->prefecture_id)->value,
                'fee' => $setting->fee,
                'delivery_days' => $setting->delivery_days,
            ])
            ->all();

        return Inertia::render('admin/ShippingSetting/Index', [
            'settings' => $settings,
            'regions' => Region::options(),
        ]);
    }

    public function update(UpdateShippingSettingsRequest $request, BulkUpdateShippingSettings $bulkUpdate): RedirectResponse
    {
        /** @var array<int, array{id: int|string, fee: int|string, delivery_days: int|string}> $settings */
        $settings = $request->validated('settings');

        $bulkUpdate($settings);

        return back();
    }
}
