<x-mail::message>
# ご注文を承りました

{{ $order->customer_name }} 様

このたびはご注文いただきありがとうございます。以下の内容で承りました。

- 注文番号: {{ $order->order_number }}
- ご注文日時: {{ $order->ordered_at->format('Y年n月j日 H:i') }}

## ご注文内容

<x-mail::table>
| 商品 | サイズ・カラー | 単価 | 数量 | 小計 |
|:---|:---|---:|---:|---:|
@foreach ($order->items as $item)
| {{ $item->product_name }} | {{ implode(' / ', array_filter([$item->size_name, $item->color_name], fn (?string $value): bool => $value !== null)) ?: '—' }} | {{ number_format($item->unit_price) }}円 | {{ $item->quantity }} | {{ number_format($item->subtotal) }}円 |
@endforeach
</x-mail::table>

- 商品合計: {{ number_format($order->subtotal) }}円
- 送料: {{ number_format($order->shipping_fee) }}円
@if ($order->cod_fee > 0)
- 代引き手数料: {{ number_format($order->cod_fee) }}円
@endif
- **合計金額: {{ number_format($order->total) }}円**

## お届け先

- お名前: {{ $order->shipping_recipient_name }} 様
- 郵便番号: 〒{{ $order->shipping_postal_code }}
- ご住所: {{ $order->shipping_prefecture_name }}{{ $order->shipping_city }}{{ $order->shipping_address_line1 }} {{ $order->shipping_address_line2 }}
- お電話番号: {{ $order->shipping_tel }}
- お届け予定日: {{ $order->estimated_delivery_date->format('Y年n月j日') }}

## お支払い方法

{{ $order->payment_method->label() }}

@if ($bankTransferNote)
{{ $bankTransferNote }}
@endif

<x-mail::button :url="route('mypage.orders.show', $order)">
ご注文の詳細を見る
</x-mail::button>

本メールは送信専用です。ご返信いただいてもお答えできません。

{{ config('app.name') }}
</x-mail::message>
