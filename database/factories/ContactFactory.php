<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ContactStatus;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contact>
 */
class ContactFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // 接頭辞は固定の架空値。受信月との整合は取らない（採番の検証は
            // Tests\Unit\GenerateContactNumberActionTest が担う）
            'contact_number' => 'INQ-2608-'.fake()->unique()->numerify('####'),
            'user_id' => null,
            'product_id' => null,
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'product_name' => null,
            'product_code' => null,
            'body' => fake()->paragraph(),
            'status' => ContactStatus::Unhandled,
            'admin_note' => null,
            'handled_admin_id' => null,
            'handled_at' => null,
        ];
    }
}
