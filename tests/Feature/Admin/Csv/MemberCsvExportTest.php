<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Csv;

use App\Enums\UserStatus;
use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MemberCsvExportTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::factory()->create();
    }

    private function exportContent(): string
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.members.csv.export'));

        $response->assertOk();

        return $response->streamedContent();
    }

    #[Test]
    public function 会員が書き出される(): void
    {
        User::factory()->create([
            'member_code' => 'M-100238',
            'name' => '山田 太郎',
            'email' => 'taro@example.test',
            'status' => UserStatus::Active,
        ]);

        $content = $this->exportContent();

        $this->assertStringContainsString('M-100238', $content);
        $this->assertStringContainsString('山田 太郎', $content);
        $this->assertStringContainsString('taro@example.test', $content);
        $this->assertStringContainsString('有効', $content);
    }

    #[Test]
    public function パスワードハッシュは書き出されない(): void
    {
        $user = User::factory()->create(['email' => 'taro@example.test']);

        $content = $this->exportContent();

        $this->assertStringNotContainsString($user->password, $content);
        $this->assertStringNotContainsString('初期パスワード', $content);
    }

    #[Test]
    public function 記憶トークンは書き出されない(): void
    {
        $user = User::factory()->create(['remember_token' => 'secret-token-value']);

        $this->assertStringNotContainsString(
            (string) $user->remember_token,
            $this->exportContent(),
        );
    }

    #[Test]
    public function 先頭にバイトオーダーマークが付く(): void
    {
        User::factory()->create();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $this->exportContent());
    }

    #[Test]
    public function 未認証は書き出せない(): void
    {
        $this->get(route('admin.members.csv.export'))
            ->assertRedirect(route('admin.login'));
    }
}
