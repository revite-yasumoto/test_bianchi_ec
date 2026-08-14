<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\SpecOption;

use App\Enums\SpecOptionType;
use App\Models\Admin;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SpecOption;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SpecOptionDestroyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function 規格を削除できる(): void
    {
        $admin = Admin::factory()->create();
        $specOption = SpecOption::factory()->create([
            'type' => SpecOptionType::Size,
            'name' => 'M',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->delete(route('admin.spec-options.destroy', $specOption));

        $response->assertRedirect(route('admin.spec-options.index'));
        $this->assertDatabaseMissing('spec_options', ['id' => $specOption->id]);
    }

    #[Test]
    public function 規格を削除しても同じ規格値を使う商品のバリエーションは残る(): void
    {
        $admin = Admin::factory()->create();
        $specOption = SpecOption::factory()->create([
            'type' => SpecOptionType::Size,
            'name' => 'M',
        ]);
        $product = Product::factory()->create(['has_sku' => true]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'size_name' => 'M',
            'color_name' => 'ネイビー',
        ]);

        $this->actingAs($admin, 'admin')
            ->delete(route('admin.spec-options.destroy', $specOption));

        $this->assertDatabaseMissing('spec_options', ['id' => $specOption->id]);
        $this->assertDatabaseHas('product_variants', [
            'id' => $variant->id,
            'size_name' => 'M',
        ]);
    }

    #[Test]
    public function 未認証は規格を削除できない(): void
    {
        $specOption = SpecOption::factory()->create();

        $this->delete(route('admin.spec-options.destroy', $specOption))
            ->assertRedirect(route('admin.login'));

        $this->assertDatabaseHas('spec_options', ['id' => $specOption->id]);
    }
}
