<x-mail::message>
# お問い合わせを受け付けました

- 問い合わせ番号: {{ $contact->contact_number }}
- 受付日時: {{ $contact->created_at->format('Y年n月j日 H:i') }}
- お名前: {{ $name }}
- メールアドレス: {{ $contact->email }}
- 対象商品: {{ $productName ?? '—' }}

**お問い合わせ内容**

{{ $body }}

<x-mail::button :url="route('admin.contacts.show', $contact)">
お問い合わせ詳細を開く
</x-mail::button>

返信は上記のメールアドレス宛に直接お送りください。対応状況は管理画面に記録してください。

{{ config('app.name') }}
</x-mail::message>
