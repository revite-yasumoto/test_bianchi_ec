<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'order_number', 'user_id', 'status', 'payment_method', 'ordered_at', 'cancelled_at',
    'member_code_snapshot', 'customer_name', 'customer_name_kana', 'customer_email', 'customer_tel',
    'shipping_recipient_name', 'shipping_postal_code', 'shipping_prefecture_name', 'shipping_city',
    'shipping_address_line1', 'shipping_address_line2', 'shipping_tel',
    'subtotal', 'shipping_fee', 'cod_fee', 'total',
    'free_shipping_threshold', 'shipping_fee_base', 'delivery_days', 'estimated_delivery_date', 'bank_transfer_note',
])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'payment_method' => PaymentMethod::class,
            'ordered_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'estimated_delivery_date' => 'date',
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
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * @return HasMany<OrderStatusHistory, $this>
     */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class);
    }
}
