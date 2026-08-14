<x-mail::message>
# 商品を発送しました

{{ $order->customer_name }} 様

ご注文いただいた商品を発送しました。お届けまで今しばらくお待ちください。

- 注文番号: {{ $order->order_number }}
@if ($trackingNumber)
- 送り状番号: {{ $trackingNumber }}
@endif
- お届け予定日: {{ $order->estimated_delivery_date->format('Y年n月j日') }}

## お届け先

- お名前: {{ $order->shipping_recipient_name }} 様
- 郵便番号: 〒{{ $order->shipping_postal_code }}
- ご住所: {{ $order->shipping_prefecture_name }}{{ $order->shipping_city }}{{ $order->shipping_address_line1 }} {{ $order->shipping_address_line2 }}

<x-mail::button :url="route('mypage.orders.show', $order)">
ご注文の詳細を見る
</x-mail::button>

本メールは送信専用です。ご返信いただいてもお答えできません。

{{ config('app.name') }}
</x-mail::message>
