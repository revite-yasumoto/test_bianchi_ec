<?php

declare(strict_types=1);

namespace App\Mail\Front;

use App\Models\Order;
use App\Support\MarkdownText;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class OrderShipped extends Mailable
{
    public function __construct(public readonly Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: sprintf('【%s】商品を発送しました（%s）', config('app.name'), $this->order->order_number),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.front.order-shipped',
            with: [
                'trackingNumber' => $this->order->tracking_number === null
                    ? null
                    : MarkdownText::escape($this->order->tracking_number),
            ],
        );
    }
}
