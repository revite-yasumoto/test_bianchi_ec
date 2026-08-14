<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Csv;

use App\Enums\UserStatus;
use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MemberCsvImportTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::factory()->create();
    }

    /**
     * @param  array<int, string>  $lines
     */
    private function import(array $lines): TestResponse
    {
        $header = '会員ID,氏名,氏名カナ,メールアドレス,電話番号,ステータス,初期パスワード';
        $content = implode("\r\n", array_merge([$header], $lines))."\r\n";

        return $this->actingAs($this->admin, 'admin')->post(
            route('admin.members.csv.import'),
            ['file' => UploadedFile::fake()->createWithContent('members.csv', $content)],
        );
    }

    #[Test]
    public function 会員を新規登録できる(): void
    {
        $this->import([',山田 太郎,ヤマダ タロウ,taro@example.test,090-0000-0000,有効,password123']);

        $this->assertDatabaseHas('users', [
            'name' => '山田 太郎',
            'name_kana' => 'ヤマダ タロウ',
            'email' => 'taro@example.test',
            'status' => UserStatus::Active->value,
        ]);
    }

    #[Test]
    public function 会員番号を省略すると採番される(): void
    {
        $this->import([',山田 太郎,,taro@example.test,,,password123']);

        $user = User::query()->where('email', 'taro@example.test')->sole();

        $this->assertSame('M-100001', $user->member_code);
    }

    #[Test]
    public function 会員番号を指定するとその値で登録される(): void
    {
        $this->import(['M-200001,山田 太郎,,taro@example.test,,,password123']);

        $this->assertDatabaseHas('users', [
            'member_code' => 'M-200001',
            'email' => 'taro@example.test',
        ]);
    }

    #[Test]
    public function 初期パスワードはハッシュ化して保存される(): void
    {
        $this->import([',山田 太郎,,taro@example.test,,,password123']);

        $user = User::query()->where('email', 'taro@example.test')->sole();

        $this->assertNotSame('password123', $user->password);
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    #[Test]
    public function 休会を指定して登録できる(): void
    {
        $this->import([',田中 美咲,,misaki@example.test,,休会,password123']);

        $this->assertDatabaseHas('users', [
            'email' => 'misaki@example.test',
            'status' => UserStatus::Suspended->value,
        ]);
    }

    #[Test]
    public function 既存会員を更新してもパスワードは変わらない(): void
    {
        $user = User::factory()->create([
            'member_code' => 'M-100238',
            'name' => '旧氏名',
            'email' => 'taro@example.test',
        ]);
        $before = $user->password;

        $this->import(['M-100238,山田 太郎,,taro@example.test,,,']);

        $user->refresh();

        $this->assertSame('山田 太郎', $user->name);
        $this->assertSame($before, $user->password);
        $this->assertDatabaseCount('users', 1);
    }

    #[Test]
    public function 新規登録で初期パスワードが空ならエラーになる(): void
    {
        $this->import([',山田 太郎,,taro@example.test,,,']);

        $this->assertDatabaseCount('users', 0);
    }

    #[Test]
    public function 他の会員と重複するメールアドレスはエラーになる(): void
    {
        User::factory()->create(['email' => 'taro@example.test']);

        $this->import([',山田 太郎,,taro@example.test,,,password123']);

        $this->assertDatabaseCount('users', 1);
    }

    #[Test]
    public function ファイル内でメールアドレスが重複するとエラーになる(): void
    {
        $this->import([
            ',山田 太郎,,taro@example.test,,,password123',
            ',佐藤 花子,,taro@example.test,,,password123',
        ]);

        $this->assertDatabaseCount('users', 0);
    }

    #[Test]
    public function 氏名が空の行はエラーになる(): void
    {
        $this->import([',,,taro@example.test,,,password123']);

        $this->assertDatabaseCount('users', 0);
    }

    #[Test]
    public function 未認証は取り込めない(): void
    {
        $header = '会員ID,氏名,氏名カナ,メールアドレス,電話番号,ステータス,初期パスワード';
        $content = $header."\r\n".',山田 太郎,,taro@example.test,,,password123'."\r\n";

        $this->post(route('admin.members.csv.import'), [
            'file' => UploadedFile::fake()->createWithContent('members.csv', $content),
        ])->assertRedirect(route('admin.login'));

        $this->assertDatabaseCount('users', 0);
    }
}
