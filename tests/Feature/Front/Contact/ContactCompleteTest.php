<?php

declare(strict_types=1);

namespace Tests\Feature\Front\Contact;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactCompleteTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        return [
            'name' => '架空 太郎',
            'email' => 'taro@example.test',
            'product_name' => '架空ジャージ 2026',
            'body' => 'サイズ展開について教えてください。',
        ];
    }

    #[Test]
    public function 送信直後は問い合わせ番号付きで完了画面を開ける(): void
    {
        Carbon::setTestNow('2026-08-21 10:00:00');

        $this->post(route('contact.store'), $this->payload());

        $this->get(route('contact.complete'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('front/Contact/Complete')
                ->where('contactNumber', 'INQ-2608-0001')
            );
    }

    #[Test]
    public function 送信を経ずに完了画面を開くとお問い合わせフォームへ戻される(): void
    {
        $this->get(route('contact.complete'))->assertRedirect(route('contact'));
    }

    #[Test]
    public function 完了画面は再読み込みすると開けない(): void
    {
        $this->post(route('contact.store'), $this->payload());
        $this->get(route('contact.complete'))->assertOk();

        $this->get(route('contact.complete'))->assertRedirect(route('contact'));
    }
}
