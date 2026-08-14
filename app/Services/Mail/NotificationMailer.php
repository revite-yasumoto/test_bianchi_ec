<?php

declare(strict_types=1);

namespace App\Services\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * 通知メールの送信口。キュー基盤を運用しないため同期送信になり、送信の失敗が
 * 会員登録・注文の成立を妨げないよう例外をここで止めてログにのみ残す。
 */
class NotificationMailer
{
    /**
     * @param  string|list<string>  $to
     */
    public function send(string|array $to, Mailable $mailable): void
    {
        $recipients = array_values(array_filter((array) $to));

        if ($recipients === []) {
            return;
        }

        try {
            Mail::to($recipients)->send($mailable);
        } catch (Throwable $exception) {
            Log::error('通知メールの送信に失敗しました。', [
                'mailable' => $mailable::class,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function sendToAdmin(Mailable $mailable): void
    {
        /** @var list<string> $addresses */
        $addresses = config('mail.admin_addresses', []);

        $this->send($addresses, $mailable);
    }
}
