<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->numberBetween(3000, 400000);
        $shippingFee = 500;

        return [
            'order_number' => 'BNC-'.fake()->unique()->numerify('####-####'),
            'user_id' => User::factory(),
            'status' => OrderStatus::Received,
            'payment_method' => PaymentMethod::BankTransfer,
            'ordered_at' => now(),
            'cancelled_at' => null,
            'member_code_snapshot' => 'M-'.fake()->numerify('######'),
            'customer_name' => fake()->name(),
            'customer_name_kana' => null,
            'customer_email' => fake()->unique()->safeEmail(),
            'customer_tel' => fake()->numerify('0#0-####-####'),
            'shipping_recipient_name' => fake()->name(),
            'shipping_postal_code' => fake()->numerify('###-####'),
            'shipping_prefecture_name' => '東京都',
            'shipping_city' => fake()->city(),
            'shipping_address_line1' => fake()->streetAddress(),
            'shipping_address_line2' => null,
            'shipping_tel' => fake()->numerify('0#0-####-####'),
            'subtotal' => $subtotal,
            'shipping_fee' => $shippingFee,
            'cod_fee' => 0,
            'total' => $subtotal + $shippingFee,
            'free_shipping_threshold' => 10000,
            'shipping_fee_base' => $shippingFee,
            'delivery_days' => 3,
            'estimated_delivery_date' => now()->addDays(3)->toDateString(),
            'bank_transfer_note' => 'ご注文後5営業日以内に下記口座へお振込みください。',
        ];
    }
}
