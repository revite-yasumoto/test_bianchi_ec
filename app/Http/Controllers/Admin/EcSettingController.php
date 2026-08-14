<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EcSetting\UpdateEcSettingRequest;
use App\Services\Setting\EcSettingProvider;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class EcSettingController extends Controller
{
    public function __construct(private readonly EcSettingProvider $ecSettingProvider) {}

    public function edit(): Response
    {
        $setting = $this->ecSettingProvider->get();

        return Inertia::render('admin/EcSetting/Edit', [
            'setting' => [
                'free_shipping_threshold' => $setting->free_shipping_threshold,
                'cod_fee' => $setting->cod_fee,
                'bank_transfer_note' => $setting->bank_transfer_note,
            ],
        ]);
    }

    public function update(UpdateEcSettingRequest $request): RedirectResponse
    {
        $this->ecSettingProvider->get()->update($request->validated());

        return back();
    }
}
