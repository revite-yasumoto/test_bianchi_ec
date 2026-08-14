<x-mail::message>
# お問い合わせを受け付けました

- 受付日時: {{ $contact->created_at->format('Y年n月j日 H:i') }}
- お名前: {{ $name }}
- メールアドレス: {{ $contact->email }}
- 対象商品: {{ $productName ?? '—' }}

**お問い合わせ内容**

{{ $body }}

返信は上記のメールアドレス宛に直接お送りください。

{{ config('app.name') }}
</x-mail::message>
