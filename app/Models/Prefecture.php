<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PrefectureFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['name'])]
class Prefecture extends Model
{
    /** @use HasFactory<PrefectureFactory> */
    use HasFactory;

    public $timestamps = false;

    /**
     * @return HasOne<ShippingSetting, $this>
     */
    public function shippingSetting(): HasOne
    {
        return $this->hasOne(ShippingSetting::class);
    }
}
