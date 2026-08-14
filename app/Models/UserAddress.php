<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\UserAddressFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id', 'label', 'recipient_name', 'postal_code', 'prefecture_id',
    'city', 'address_line1', 'address_line2', 'tel', 'is_default',
])]
class UserAddress extends Model
{
    /** @use HasFactory<UserAddressFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Prefecture, $this>
     */
    public function prefecture(): BelongsTo
    {
        return $this->belongsTo(Prefecture::class);
    }
}
