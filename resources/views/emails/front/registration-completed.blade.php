<x-mail::message>
# 会員登録が完了しました

{{ $user->name }} 様

このたびは {{ config('app.name') }} にご登録いただきありがとうございます。以下の内容で会員登録が完了しました。

- 会員ID: {{ $user->member_code }}
- メールアドレス: {{ $user->email }}

マイページから、ご注文の履歴・お届け先・会員情報をご確認いただけます。

<x-mail::button :url="route('mypage.index')">
マイページを開く
</x-mail::button>

本メールは送信専用です。ご返信いただいてもお答えできません。ご不明な点は[お問い合わせフォーム]({{ route('contact') }})よりお願いいたします。

{{ config('app.name') }}
</x-mail::message>
