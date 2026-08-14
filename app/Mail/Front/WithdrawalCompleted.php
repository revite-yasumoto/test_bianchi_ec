<?php

declare(strict_types=1);

namespace App\Mail\Front;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class WithdrawalCompleted extends Mailable
{
    public function __construct(public readonly User $user) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: sprintf('【%s】退会手続きが完了しました', config('app.name')),
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.front.withdrawal-completed');
    }
}
