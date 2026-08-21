<?php

declare(strict_types=1);

namespace App\Actions\Front\Contact;

use App\Mail\Admin\ContactReceived;
use App\Mail\Front\ContactAcknowledgement;
use App\Models\Contact;
use App\Services\Mail\NotificationMailer;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class SubmitContact
{
    public function __construct(
        private readonly NotificationMailer $notificationMailer,
        private readonly ResolveContactProduct $resolveProduct,
        private readonly GenerateContactNumber $generateNumber,
    ) {}

    /**
     * 対象商品が確定している問い合わせは、送信された商品名を使わず商品マスタの名称・商品識別コードで保存する。
     * 画面上の編集不可は表示の制御にすぎず、POST は直接呼び出せるため。
     *
     * @param  array<string, mixed>  $attributes
     */
    public function __invoke(array $attributes, ?int $userId): Contact
    {
        $product = ($this->resolveProduct)($attributes['product_id'] ?? null);

        // 採番は当月の最大番号を押さえてから行うため、保存と同じトランザクションに入れる
        $contact = DB::transaction(fn (): Contact => Contact::query()->create([
            ...$attributes,
            'contact_number' => ($this->generateNumber)(CarbonImmutable::now()),
            'user_id' => $userId,
            'product_id' => $product?->id,
            'product_name' => $product?->name ?? ($attributes['product_name'] ?? null),
            'product_code' => $product?->product_code,
        ]));

        $this->notificationMailer->sendToAdmin(new ContactReceived($contact));
        $this->notificationMailer->send($contact->email, new ContactAcknowledgement($contact));

        return $contact;
    }
}
