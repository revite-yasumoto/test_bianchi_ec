<x-mail::message>
# お問い合わせを承りました

{{ $name }} 様

お問い合わせいただきありがとうございます。以下の内容で承りました。3営業日以内にご返信いたします。

**問い合わせ番号: {{ $contact->contact_number }}**

お問い合わせについてお尋ねの際は、この番号をお知らせください。

@if ($productName)
**対象商品**

{{ $productName }}

@endif
**お問い合わせ内容**

{{ $body }}

本メールは送信専用です。ご返信いただいてもお答えできません。

{{ config('app.name') }}
</x-mail::message>
