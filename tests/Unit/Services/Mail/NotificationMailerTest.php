<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Mail;

use App\Mail\Front\RegistrationCompleted;
use App\Models\User;
use App\Services\Mail\NotificationMailer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class NotificationMailerTest extends TestCase
{
    private function mailable(): RegistrationCompleted
    {
        return new RegistrationCompleted(User::factory()->make());
    }

    #[Test]
    public function 宛先を指定すると送信される(): void
    {
        Mail::fake();

        (new NotificationMailer)->send('taro@example.test', $this->mailable());

        Mail::assertSent(
            RegistrationCompleted::class,
            fn (RegistrationCompleted $mail): bool => $mail->hasTo('taro@example.test'),
        );
    }

    #[Test]
    public function 宛先が空なら送信されない(): void
    {
        Mail::fake();

        (new NotificationMailer)->send([], $this->mailable());

        Mail::assertNothingSent();
    }

    #[Test]
    public function 管理者の宛先が未設定なら送信されない(): void
    {
        Mail::fake();
        config(['mail.admin_addresses' => []]);

        (new NotificationMailer)->sendToAdmin($this->mailable());

        Mail::assertNothingSent();
    }

    #[Test]
    public function 管理者の宛先を複数指定すると全てが宛先になる(): void
    {
        Mail::fake();
        config(['mail.admin_addresses' => ['uketsuke@example.test', 'tenpo@example.test']]);

        (new NotificationMailer)->sendToAdmin($this->mailable());

        Mail::assertSent(
            RegistrationCompleted::class,
            fn (RegistrationCompleted $mail): bool => $mail->hasTo('uketsuke@example.test')
                && $mail->hasTo('tenpo@example.test'),
        );
    }

    #[Test]
    public function 環境変数のカンマ区切りが宛先の配列に解析される(): void
    {
        // 解析後の配列を差し込むのではなく、phpunit.xml が与える環境変数からの解析結果を確認する
        $this->assertSame(
            ['admin@example.test', 'uketsuke@example.test'],
            config('mail.admin_addresses'),
        );
    }

    #[Test]
    public function 送信に失敗しても例外は伝播せずログに残る(): void
    {
        Log::spy();
        Mail::shouldReceive('to->send')->once()->andThrow(new RuntimeException('接続できません'));

        (new NotificationMailer)->send('taro@example.test', $this->mailable());

        Log::shouldHaveReceived('error')->once();
    }
}
