<?php

declare(strict_types=1);

namespace App\Actions\Admin\ShippingSetting;

use App\Models\ShippingSetting;
use Illuminate\Support\Facades\DB;

class BulkUpdateShippingSettings
{
    /**
     * @param  array<int, array{id: int|string, fee: int|string, delivery_days: int|string}>  $settings
     */
    public function __invoke(array $settings): void
    {
        DB::transaction(function () use ($settings): void {
            foreach ($settings as $setting) {
                ShippingSetting::query()
                    ->whereKey((int) $setting['id'])
                    ->update([
                        'fee' => (int) $setting['fee'],
                        'delivery_days' => (int) $setting['delivery_days'],
                    ]);
            }
        });
    }
}
