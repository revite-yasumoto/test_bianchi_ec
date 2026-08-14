<?php

declare(strict_types=1);

namespace App\Mail\Front;

use App\Models\Order;
use App\Support\MarkdownText;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class OrderReceived extends Mailable
{
    public function __construct(public readonly Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: sprintf('【%s】ご注文を承りました（%s）', config('app.name'), $this->order->order_number),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.front.order-received',
            with: [
                'bankTransferNote' => $this->order->bank_transfer_note === null
                    ? null
                    : MarkdownText::escape($this->order->bank_transfer_note),
            ],
        );
    }
}
