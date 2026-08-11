<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\EcSetting;

use App\Models\Admin;
use App\Models\EcSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EcSettingEditTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function 保存済みの基本設定が表示される(): void
    {
        $admin = Admin::factory()->create();
        // EC基本設定は単一行（id=1）のため、最初の1件がその設定になる
        EcSetting::factory()->create([
            'free_shipping_threshold' => 12000,
            'cod_fee' => 440,
            'bank_transfer_note' => 'デモ銀行 デモ支店 普通 0000000',
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.ec-settings.edit'))
            ->assertInertia(fn ($page) => $page
                ->component('admin/EcSetting/Edit')
                ->where('setting.free_shipping_threshold', 12000)
                ->where('setting.cod_fee', 440)
                ->where('setting.bank_transfer_note', 'デモ銀行 デモ支店 普通 0000000')
            );
    }

    #[Test]
    public function 未認証はログイン画面へリダイレクトされる(): void
    {
        $this->get(route('admin.ec-settings.edit'))
            ->assertRedirect(route('admin.login'));
    }
}
