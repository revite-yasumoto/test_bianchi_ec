<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\SpecOption;

use App\Enums\SpecOptionType;
use App\Models\Admin;
use App\Models\SpecOption;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SpecOptionIndexTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function 規格管理画面にサイズとカラーが種別ごとに分かれて表示される(): void
    {
        $admin = Admin::factory()->create();
        SpecOption::factory()->create(['type' => SpecOptionType::Size, 'name' => 'M']);
        SpecOption::factory()->create(['type' => SpecOptionType::Color, 'name' => 'ネイビー']);
        SpecOption::factory()->create(['type' => SpecOptionType::Color, 'name' => 'アクア']);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.spec-options.index'))
            ->assertInertia(fn ($page) => $page
                ->component('admin/SpecOption/Index')
                ->has('sizes', 1)
                ->has('colors', 2)
                ->where('sizes.0.name', 'M')
                ->where('colors.0.name', 'ネイビー')
            );
    }

    #[Test]
    public function 未認証はログイン画面へリダイレクトされる(): void
    {
        $this->get(route('admin.spec-options.index'))
            ->assertRedirect(route('admin.login'));
    }
}
