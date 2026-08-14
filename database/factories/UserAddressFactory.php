<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Prefecture;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserAddress>
 */
class UserAddressFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'label' => '自宅',
            'recipient_name' => fake()->name(),
            'postal_code' => fake()->numerify('###-####'),
            'prefecture_id' => Prefecture::factory(),
            'city' => fake()->city(),
            'address_line1' => fake()->streetAddress(),
            'address_line2' => null,
            'tel' => fake()->numerify('0#0-####-####'),
            'is_default' => true,
        ];
    }
}
