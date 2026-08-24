<?php

declare(strict_types=1);

namespace App\Mail\Front;

use App\Models\Contact;
use App\Support\MarkdownText;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ContactAcknowledgement extends Mailable
{
    public function __construct(public readonly Contact $contact) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: sprintf('【%s】お問い合わせを承りました（%s）', config('app.name'), $this->contact->contact_number),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.front.contact-acknowledgement',
            with: [
                'name' => MarkdownText::escape($this->contact->name),
                'body' => MarkdownText::escape($this->contact->body),
                'productName' => $this->contact->product_name === null
                    ? null
                    : MarkdownText::escape($this->contact->product_name),
            ],
        );
    }
}
