<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Csv;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminUserCsvImportTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::factory()->create(['admin_code' => 'A-001']);
    }

    /**
     * @param  array<int, string>  $lines
     */
    private function import(array $lines): TestResponse
    {
        $header = '管理者ID,氏名,メールアドレス,初期パスワード';
        $content = implode("\r\n", array_merge([$header], $lines))."\r\n";

        return $this->actingAs($this->admin, 'admin')->post(
            route('admin.admins.csv.import'),
            ['file' => UploadedFile::fake()->createWithContent('admins.csv', $content)],
        );
    }

    #[Test]
    public function 管理者を新規登録できる(): void
    {
        $this->import([',運用 花子,ops@bianchi.test,password123']);

        $this->assertDatabaseHas('admins', [
            'name' => '運用 花子',
            'email' => 'ops@bianchi.test',
        ]);
    }

    #[Test]
    public function 管理者番号を省略すると採番される(): void
    {
        $this->import([',運用 花子,ops@bianchi.test,password123']);

        $this->assertDatabaseHas('admins', [
            'email' => 'ops@bianchi.test',
            'admin_code' => 'A-002',
        ]);
    }

    #[Test]
    public function 取り込んだ管理者でログインできる(): void
    {
        $this->import([',運用 花子,ops@bianchi.test,password123']);

        $created = Admin::query()->where('email', 'ops@bianchi.test')->sole();

        $this->assertTrue(Hash::check('password123', $created->password));

        $this->post(route('admin.login.store'), [
            'login_id' => 'ops@bianchi.test',
            'password' => 'password123',
        ])->assertSessionHasNoErrors();
    }

    #[Test]
    public function 既存管理者を更新してもパスワードは変わらない(): void
    {
        $target = Admin::factory()->create([
            'admin_code' => 'A-002',
            'name' => '旧氏名',
            'email' => 'ops@bianchi.test',
        ]);
        $before = $target->password;

        $this->import(['A-002,運用 花子,ops@bianchi.test,']);

        $target->refresh();

        $this->assertSame('運用 花子', $target->name);
        $this->assertSame($before, $target->password);
        $this->assertDatabaseCount('admins', 2);
    }

    #[Test]
    public function 新規登録で初期パスワードが空ならエラーになる(): void
    {
        $this->import([',運用 花子,ops@bianchi.test,']);

        $this->assertDatabaseCount('admins', 1);
    }

    #[Test]
    public function 他の管理者と重複するメールアドレスはエラーになる(): void
    {
        $this->import([',運用 花子,'.$this->admin->email.',password123']);

        $this->assertDatabaseCount('admins', 1);
    }

    #[Test]
    public function 複数行をまとめて取り込める(): void
    {
        $this->import([
            ',運用 花子,ops@bianchi.test,password123',
            ',在庫 次郎,stock@bianchi.test,password123',
        ]);

        $this->assertDatabaseCount('admins', 3);
        $this->assertDatabaseHas('admins', ['admin_code' => 'A-002']);
        $this->assertDatabaseHas('admins', ['admin_code' => 'A-003']);
    }

    #[Test]
    public function 未認証は取り込めない(): void
    {
        $header = '管理者ID,氏名,メールアドレス,初期パスワード';
        $content = $header."\r\n".',運用 花子,ops@bianchi.test,password123'."\r\n";

        $this->post(route('admin.admins.csv.import'), [
            'file' => UploadedFile::fake()->createWithContent('admins.csv', $content),
        ])->assertRedirect(route('admin.login'));

        $this->assertDatabaseCount('admins', 1);
    }

    #[Test]
    public function 管理者の書き出しにパスワードハッシュが含まれない(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.admins.csv.export'));

        $response->assertOk();
        $content = $response->streamedContent();

        $this->assertStringContainsString('A-001', $content);
        $this->assertStringNotContainsString($this->admin->password, $content);
    }
}
