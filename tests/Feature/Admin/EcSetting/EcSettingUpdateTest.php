<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\EcSetting;

use App\Models\Admin;
use App\Models\EcSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EcSettingUpdateTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::factory()->create();
        // EC基本設定は単一行（id=1）のため、最初の1件がその設定になる
        EcSetting::factory()->create();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'free_shipping_threshold' => 15000,
            'cod_fee' => 550,
            'bank_transfer_note' => 'ご注文後7日以内にお振込みください。',
            ...$overrides,
        ];
    }

    #[Test]
    public function 送料無料しきい値と代引き手数料と案内文が更新される(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->put(route('admin.ec-settings.update'), $this->payload());

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('ec_settings', [
            'id' => 1,
            'free_shipping_threshold' => 15000,
            'cod_fee' => 550,
            'bank_transfer_note' => 'ご注文後7日以内にお振込みください。',
        ]);
    }

    #[Test]
    public function 上限を超える送料無料しきい値は弾かれる(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->put(route('admin.ec-settings.update'), $this->payload(['free_shipping_threshold' => 1000001]))
            ->assertSessionHasErrors('free_shipping_threshold');

        $this->assertDatabaseHas('ec_settings', ['id' => 1, 'free_shipping_threshold' => 10000]);
    }

    #[Test]
    public function 負の代引き手数料は弾かれる(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->put(route('admin.ec-settings.update'), $this->payload(['cod_fee' => -1]))
            ->assertSessionHasErrors('cod_fee');
    }

    #[Test]
    public function 案内文が2000文字を超えると弾かれる(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->put(route('admin.ec-settings.update'), $this->payload([
                'bank_transfer_note' => str_repeat('あ', 2001),
            ]))
            ->assertSessionHasErrors('bank_transfer_note');
    }

    #[Test]
    public function 案内文は必須(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->put(route('admin.ec-settings.update'), $this->payload(['bank_transfer_note' => '']))
            ->assertSessionHasErrors('bank_transfer_note');
    }

    #[Test]
    public function 未認証はログイン画面へリダイレクトされる(): void
    {
        $this->put(route('admin.ec-settings.update'), $this->payload())
            ->assertRedirect(route('admin.login'));
    }
}
