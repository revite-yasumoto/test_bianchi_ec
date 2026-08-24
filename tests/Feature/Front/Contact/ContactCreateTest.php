<?php

declare(strict_types=1);

namespace Tests\Feature\Front\Contact;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactCreateTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function 未ログインでもお問い合わせフォームを開ける(): void
    {
        $this->get(route('contact'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('front/Contact/Create')
                ->where('defaults.name', '')
                ->where('defaults.email', '')
                ->where('defaults.product_name', '')
                ->where('product', null)
            );
    }

    #[Test]
    public function ログイン中は会員の氏名とメールアドレスが初期値に入る(): void
    {
        $user = User::factory()->create([
            'name' => '架空 太郎',
            'email' => 'taro@example.test',
        ]);

        $this->actingAs($user)
            ->get(route('contact'))
            ->assertInertia(fn ($page) => $page
                ->where('defaults.name', '架空 太郎')
                ->where('defaults.email', 'taro@example.test')
            );
    }

    #[Test]
    public function クエリの商品の指定で公開商品を引き当てる(): void
    {
        $product = Product::factory()->create([
            'name' => '架空ジャージ 2026',
            'is_published' => true,
        ]);

        $this->get(route('contact', ['product_id' => $product->id]))
            ->assertInertia(fn ($page) => $page
                ->where('product.id', $product->id)
                ->where('product.name', '架空ジャージ 2026')
            );
    }

    #[Test]
    public function 非公開商品を指定したときは対象商品なしとして開ける(): void
    {
        $product = Product::factory()->create(['is_published' => false]);

        $this->get(route('contact', ['product_id' => $product->id]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('product', null));
    }

    #[Test]
    public function 存在しない商品を指定したときは対象商品なしとして開ける(): void
    {
        $this->get(route('contact', ['product_id' => 999999]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('product', null));
    }

    #[Test]
    public function 商品の指定に文字列や配列を渡してもフォームを開ける(): void
    {
        $this->get('/contact?product_id=abc')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('product', null));

        $this->get('/contact?product_id[]=1&product_id[]=2')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('product', null));
    }
}
