<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Product;

use App\Models\Admin;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Stock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductDestroyTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->admin = Admin::factory()->create();
    }

    private function makeProduct(): Product
    {
        return Product::factory()->create([
            'category_id' => Category::factory(),
        ]);
    }

    #[Test]
    public function 商品を削除できる(): void
    {
        $product = $this->makeProduct();

        $response = $this->actingAs($this->admin, 'admin')
            ->delete(route('admin.products.destroy', $product));

        $response->assertRedirect(route('admin.products.index'));
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    #[Test]
    public function 商品を削除するとバリエーションと在庫も消える(): void
    {
        $product = $this->makeProduct();
        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
        Stock::factory()->create(['product_variant_id' => $variant->id]);

        $this->actingAs($this->admin, 'admin')
            ->delete(route('admin.products.destroy', $product));

        $this->assertDatabaseMissing('product_variants', ['id' => $variant->id]);
        $this->assertDatabaseMissing('stocks', ['product_variant_id' => $variant->id]);
    }

    #[Test]
    public function 商品を削除すると画像の実ファイルも消える(): void
    {
        $product = $this->makeProduct();
        $path = "products/{$product->id}/main.png";
        Storage::disk('public')->put($path, 'dummy');
        ProductImage::factory()->create([
            'product_id' => $product->id,
            'path' => $path,
            'sort_order' => 0,
        ]);

        $this->actingAs($this->admin, 'admin')
            ->delete(route('admin.products.destroy', $product));

        Storage::disk('public')->assertMissing($path);
        $this->assertDatabaseCount('product_images', 0);
    }

    #[Test]
    public function 注文実績のある商品を削除しても注文明細は残る(): void
    {
        $product = $this->makeProduct();
        $order = Order::factory()->create();
        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
        ]);

        $this->actingAs($this->admin, 'admin')
            ->delete(route('admin.products.destroy', $product));

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
        $this->assertDatabaseHas('order_items', [
            'id' => $orderItem->id,
            'product_id' => null,
            'product_name' => $product->name,
        ]);
    }

    #[Test]
    public function 未認証は商品を削除できない(): void
    {
        $product = $this->makeProduct();

        $this->delete(route('admin.products.destroy', $product))
            ->assertRedirect(route('admin.login'));

        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }
}
