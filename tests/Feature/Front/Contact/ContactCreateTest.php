<?php

declare(strict_types=1);

namespace Tests\Feature\Front\Contact;

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
    public function クエリの商品名が対象商品の初期値に入る(): void
    {
        $this->get(route('contact', ['product_name' => '架空ジャージ 2026']))
            ->assertInertia(fn ($page) => $page
                ->where('defaults.product_name', '架空ジャージ 2026')
            );
    }

    #[Test]
    public function 上限を超える商品名は切り詰められる(): void
    {
        $this->get(route('contact', ['product_name' => str_repeat('あ', 300)]))
            ->assertInertia(fn ($page) => $page
                ->where('defaults.product_name', str_repeat('あ', 255))
            );
    }

    #[Test]
    public function 商品名に文字列以外を渡してもフォームを開ける(): void
    {
        $this->get('/contact?product_name[]=a&product_name[]=b')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('defaults.product_name', ''));
    }
}
