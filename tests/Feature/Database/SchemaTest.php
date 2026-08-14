<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use App\Models\Admin;
use App\Models\Category;
use App\Models\EcSetting;
use App\Models\Prefecture;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingSetting;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SchemaTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function all_expected_tables_exist(): void
    {
        $tables = [
            'users', 'admins', 'prefectures', 'shipping_settings', 'user_addresses',
            'categories', 'spec_options', 'ec_settings',
            'products', 'product_images', 'product_specs', 'product_variants', 'stocks',
            'cart_items', 'favorites', 'browsing_histories',
            'orders', 'order_items', 'order_status_histories',
            'news', 'notices', 'product_rankings', 'banners', 'contacts',
        ];

        foreach ($tables as $table) {
            $this->assertTrue(
                Schema::hasTable($table),
                "テーブル {$table} が存在しません"
            );
        }
    }

    #[Test]
    public function users_member_code_is_unique(): void
    {
        User::factory()->create(['member_code' => 'M-000001']);

        $this->expectException(QueryException::class);

        User::factory()->create(['member_code' => 'M-000001']);
    }

    #[Test]
    public function admins_email_is_unique(): void
    {
        Admin::factory()->create(['email' => 'dup@example.com']);

        $this->expectException(QueryException::class);

        Admin::factory()->create(['email' => 'dup@example.com']);
    }

    #[Test]
    public function shipping_settings_prefecture_id_is_unique(): void
    {
        $prefecture = Prefecture::factory()->create();
        ShippingSetting::factory()->create(['prefecture_id' => $prefecture->id]);

        $this->expectException(QueryException::class);

        ShippingSetting::factory()->create(['prefecture_id' => $prefecture->id]);
    }

    #[Test]
    public function product_variants_unique_combination_per_product(): void
    {
        $product = Product::factory()->create();
        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'size_name' => 'M',
            'color_name' => 'ブラック',
        ]);

        $this->expectException(QueryException::class);

        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'size_name' => 'M',
            'color_name' => 'ブラック',
        ]);
    }

    #[Test]
    public function stock_product_variant_id_is_unique(): void
    {
        $variant = ProductVariant::factory()->create();
        Stock::factory()->create(['product_variant_id' => $variant->id]);

        $this->expectException(QueryException::class);

        Stock::factory()->create(['product_variant_id' => $variant->id]);
    }

    #[Test]
    public function deleting_category_with_products_is_restricted(): void
    {
        $category = Category::factory()->create();
        Product::factory()->create(['category_id' => $category->id]);

        $this->expectException(QueryException::class);

        $category->delete();
    }

    #[Test]
    public function deleting_product_cascades_to_variants_and_stock(): void
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
        $stock = Stock::factory()->create(['product_variant_id' => $variant->id]);

        $product->delete();

        $this->assertDatabaseMissing('product_variants', ['id' => $variant->id]);
        $this->assertDatabaseMissing('stocks', ['id' => $stock->id]);
    }

    #[Test]
    public function ec_settings_can_hold_a_single_row(): void
    {
        EcSetting::factory()->create();

        $this->assertDatabaseCount('ec_settings', 1);
    }
}
