<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\EcSettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['free_shipping_threshold', 'cod_fee', 'bank_transfer_note'])]
class EcSetting extends Model
{
    /** @use HasFactory<EcSettingFactory> */
    use HasFactory;
}
