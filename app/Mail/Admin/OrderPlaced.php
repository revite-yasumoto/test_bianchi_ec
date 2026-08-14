<?php

declare(strict_types=1);

namespace App\Mail\Admin;

use App\Models\Order;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class OrderPlaced extends Mailable
{
    public function __construct(public readonly Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: sprintf('【%s】新規注文（%s）', config('app.name'), $this->order->order_number),
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.admin.order-placed');
    }
}
