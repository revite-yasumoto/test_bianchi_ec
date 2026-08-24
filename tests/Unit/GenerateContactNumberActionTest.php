<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Actions\Front\Contact\GenerateContactNumber;
use App\Models\Contact;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * 既存の番号は Factory で用意する。送信の経路（`POST /contact`）で採番されることは
 * `Tests\Feature\Front\Contact\ContactStoreTest` が検証している。
 */
class GenerateContactNumberActionTest extends TestCase
{
    use RefreshDatabase;

    private function generate(string $sentAt): string
    {
        return app(GenerateContactNumber::class)(CarbonImmutable::parse($sentAt));
    }

    #[Test]
    public function その月の最初の問い合わせは連番が一になる(): void
    {
        $this->assertSame('INQ-2608-0001', $this->generate('2026-08-21 10:00:00'));
    }

    #[Test]
    public function 同じ月では連番が続く(): void
    {
        Contact::factory()->create(['contact_number' => 'INQ-2608-0001']);
        Contact::factory()->create(['contact_number' => 'INQ-2608-0002']);

        $this->assertSame('INQ-2608-0003', $this->generate('2026-08-21 10:00:00'));
    }

    #[Test]
    public function 月が変わると連番は一に戻る(): void
    {
        Contact::factory()->create(['contact_number' => 'INQ-2608-0007']);

        $this->assertSame('INQ-2609-0001', $this->generate('2026-09-01 00:00:00'));
    }

    #[Test]
    public function 欠番があっても最大の連番の次を採る(): void
    {
        Contact::factory()->create(['contact_number' => 'INQ-2608-0001']);
        Contact::factory()->create(['contact_number' => 'INQ-2608-0009']);

        $this->assertSame('INQ-2608-0010', $this->generate('2026-08-21 10:00:00'));
    }

    #[Test]
    public function 他の月の番号は連番に影響しない(): void
    {
        Contact::factory()->create(['contact_number' => 'INQ-2607-0042']);

        $this->assertSame('INQ-2608-0001', $this->generate('2026-08-21 10:00:00'));
    }

    #[Test]
    public function 三桁から四桁へ繰り上がっても順序が保たれる(): void
    {
        Contact::factory()->create(['contact_number' => 'INQ-2608-0099']);

        $this->assertSame('INQ-2608-0100', $this->generate('2026-08-21 10:00:00'));
    }
}
