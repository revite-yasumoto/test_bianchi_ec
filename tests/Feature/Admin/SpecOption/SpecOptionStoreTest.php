<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\SpecOption;

use App\Enums\SpecOptionType;
use App\Models\Admin;
use App\Models\SpecOption;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SpecOptionStoreTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function サイズを追加できる(): void
    {
        $admin = Admin::factory()->create();

        $response = $this->actingAs($admin, 'admin')->post(
            route('admin.spec-options.store'),
            ['type' => SpecOptionType::Size->value, 'name' => 'XXL'],
        );

        $response->assertRedirect(route('admin.spec-options.index'));
        $this->assertDatabaseHas('spec_options', [
            'type' => SpecOptionType::Size->value,
            'name' => 'XXL',
        ]);
    }

    #[Test]
    public function カラーを追加できる(): void
    {
        $admin = Admin::factory()->create();

        $response = $this->actingAs($admin, 'admin')->post(
            route('admin.spec-options.store'),
            ['type' => SpecOptionType::Color->value, 'name' => 'ネイビー'],
        );

        $response->assertRedirect(route('admin.spec-options.index'));
        $this->assertDatabaseHas('spec_options', [
            'type' => SpecOptionType::Color->value,
            'name' => 'ネイビー',
        ]);
    }

    #[Test]
    public function 表示順は同じ種別の最大値の次になる(): void
    {
        $admin = Admin::factory()->create();
        SpecOption::factory()->create([
            'type' => SpecOptionType::Size,
            'name' => 'M',
            'sort_order' => 3,
        ]);
        SpecOption::factory()->create([
            'type' => SpecOptionType::Color,
            'name' => 'ネイビー',
            'sort_order' => 9,
        ]);

        $this->actingAs($admin, 'admin')->post(
            route('admin.spec-options.store'),
            ['type' => SpecOptionType::Size->value, 'name' => 'XXL'],
        );

        $this->assertDatabaseHas('spec_options', ['name' => 'XXL', 'sort_order' => 4]);
    }

    #[Test]
    public function 同じ種別で同名の規格は追加できない(): void
    {
        $admin = Admin::factory()->create();
        SpecOption::factory()->create(['type' => SpecOptionType::Size, 'name' => 'M']);

        $response = $this->actingAs($admin, 'admin')->post(
            route('admin.spec-options.store'),
            ['type' => SpecOptionType::Size->value, 'name' => 'M'],
        );

        $response->assertSessionHasErrors('name');
        $this->assertDatabaseCount('spec_options', 1);
    }

    #[Test]
    public function 種別が違えば同名の規格を追加できる(): void
    {
        $admin = Admin::factory()->create();
        SpecOption::factory()->create(['type' => SpecOptionType::Size, 'name' => 'フリー']);

        $response = $this->actingAs($admin, 'admin')->post(
            route('admin.spec-options.store'),
            ['type' => SpecOptionType::Color->value, 'name' => 'フリー'],
        );

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseCount('spec_options', 2);
    }

    #[Test]
    public function 種別が不正な値では追加できない(): void
    {
        $admin = Admin::factory()->create();

        $response = $this->actingAs($admin, 'admin')->post(
            route('admin.spec-options.store'),
            ['type' => 'material', 'name' => 'カーボン'],
        );

        $response->assertSessionHasErrors('type');
        $this->assertDatabaseCount('spec_options', 0);
    }

    #[Test]
    public function 規格値が50文字を超えると追加できない(): void
    {
        $admin = Admin::factory()->create();

        $response = $this->actingAs($admin, 'admin')->post(
            route('admin.spec-options.store'),
            ['type' => SpecOptionType::Size->value, 'name' => str_repeat('あ', 51)],
        );

        $response->assertSessionHasErrors('name');
        $this->assertDatabaseCount('spec_options', 0);
    }

    #[Test]
    public function 未認証は規格を追加できない(): void
    {
        $this->post(
            route('admin.spec-options.store'),
            ['type' => SpecOptionType::Size->value, 'name' => 'XXL'],
        )->assertRedirect(route('admin.login'));

        $this->assertDatabaseCount('spec_options', 0);
    }
}
