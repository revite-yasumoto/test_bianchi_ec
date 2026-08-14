<?php

declare(strict_types=1);

namespace App\Mail\Front;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class PasswordResetLink extends Mailable
{
    public function __construct(
        public readonly User $user,
        public readonly string $token,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: sprintf('【%s】パスワード再設定のご案内', config('app.name')),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.front.password-reset-link',
            with: [
                'resetUrl' => route('password.reset', [
                    'token' => $this->token,
                    'email' => $this->user->email,
                ]),
                'expiresInMinutes' => config('auth.passwords.users.expire'),
            ],
        );
    }
}
