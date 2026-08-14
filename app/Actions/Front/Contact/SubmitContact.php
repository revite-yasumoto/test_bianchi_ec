<?php

declare(strict_types=1);

namespace App\Actions\Front\Contact;

use App\Mail\Admin\ContactReceived;
use App\Mail\Front\ContactAcknowledgement;
use App\Models\Contact;
use App\Services\Mail\NotificationMailer;

class SubmitContact
{
    public function __construct(private readonly NotificationMailer $notificationMailer) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __invoke(array $attributes, ?int $userId): Contact
    {
        $contact = Contact::query()->create([
            ...$attributes,
            'user_id' => $userId,
        ]);

        $this->notificationMailer->sendToAdmin(new ContactReceived($contact));
        $this->notificationMailer->send($contact->email, new ContactAcknowledgement($contact));

        return $contact;
    }
}
