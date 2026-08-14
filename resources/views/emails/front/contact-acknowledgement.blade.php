<x-mail::message>
# お問い合わせを承りました

{{ $name }} 様

お問い合わせいただきありがとうございます。以下の内容で承りました。3営業日以内にご返信いたします。

@if ($productName)
**対象商品**

{{ $productName }}

@endif
**お問い合わせ内容**

{{ $body }}

本メールは送信専用です。ご返信いただいてもお答えできません。

{{ config('app.name') }}
</x-mail::message>
