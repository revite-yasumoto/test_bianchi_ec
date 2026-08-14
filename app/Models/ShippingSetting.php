<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ShippingSettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['prefecture_id', 'fee', 'delivery_days'])]
class ShippingSetting extends Model
{
    /** @use HasFactory<ShippingSettingFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Prefecture, $this>
     */
    public function prefecture(): BelongsTo
    {
        return $this->belongsTo(Prefecture::class);
    }
}
