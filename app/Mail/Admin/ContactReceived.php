<?php

declare(strict_types=1);

namespace App\Mail\Admin;

use App\Models\Contact;
use App\Support\MarkdownText;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ContactReceived extends Mailable
{
    public function __construct(public readonly Contact $contact) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: sprintf('【%s】お問い合わせを受け付けました', config('app.name')),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.admin.contact-received',
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
