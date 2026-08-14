<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Shipping;

use App\Enums\PaymentMethod;
use App\Models\EcSetting;
use App\Models\Prefecture;
use App\Models\ShippingSetting;
use App\Services\Shipping\ShippingCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ShippingCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private Prefecture $prefecture;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prefecture = Prefecture::factory()->create();
        ShippingSetting::factory()->create([
            'prefecture_id' => $this->prefecture->id,
            'fee' => 500,
            'delivery_days' => 3,
        ]);
        // EC基本設定は単一行（id=1）のため、最初の1件がその設定になる
        EcSetting::factory()->create([
            'free_shipping_threshold' => 10000,
            'cod_fee' => 330,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function calculator(): ShippingCalculator
    {
        return $this->app->make(ShippingCalculator::class);
    }

    #[Test]
    public function しきい値未満のときは都道府県の送料が適用される(): void
    {
        $calculation = $this->calculator()->calculate($this->prefecture->id, 9999, PaymentMethod::BankTransfer);

        $this->assertSame(500, $calculation->feeBase);
        $this->assertSame(500, $calculation->fee);
    }

    #[Test]
    public function しきい値以上のときは送料が無料になる(): void
    {
        $calculation = $this->calculator()->calculate($this->prefecture->id, 10001, PaymentMethod::BankTransfer);

        $this->assertSame(500, $calculation->feeBase);
        $this->assertSame(0, $calculation->fee);
    }

    #[Test]
    public function しきい値と同額のときは送料が無料になる(): void
    {
        $calculation = $this->calculator()->calculate($this->prefecture->id, 10000, PaymentMethod::BankTransfer);

        $this->assertSame(0, $calculation->fee);
    }

    #[Test]
    public function 代引きのときは代引き手数料が適用される(): void
    {
        $calculation = $this->calculator()->calculate($this->prefecture->id, 5000, PaymentMethod::Cod);

        $this->assertSame(330, $calculation->codFee);
    }

    #[Test]
    public function 銀行振込のときは代引き手数料が0になる(): void
    {
        $calculation = $this->calculator()->calculate($this->prefecture->id, 5000, PaymentMethod::BankTransfer);

        $this->assertSame(0, $calculation->codFee);
    }

    #[Test]
    public function 配達予定日は当日に配送予定日数を暦日で加算した日付になる(): void
    {
        Carbon::setTestNow('2026-08-12 23:30:00');

        $calculation = $this->calculator()->calculate($this->prefecture->id, 5000, PaymentMethod::BankTransfer);

        $this->assertSame(3, $calculation->deliveryDays);
        $this->assertSame('2026-08-15', $calculation->estimatedDeliveryDate->toDateString());
    }

    #[Test]
    public function 合計は商品合計と適用送料と代引き手数料の合算になる(): void
    {
        $calculation = $this->calculator()->calculate($this->prefecture->id, 5000, PaymentMethod::Cod);

        $this->assertSame(5830, $calculation->total);
    }

    #[Test]
    public function 送料無料が適用されると合計に送料が含まれない(): void
    {
        $calculation = $this->calculator()->calculate($this->prefecture->id, 12000, PaymentMethod::Cod);

        $this->assertSame(12330, $calculation->total);
        $this->assertSame(10000, $calculation->freeShippingThreshold);
    }
}
