<x-mail::message>
# 新規のご注文が入りました

- 注文番号: {{ $order->order_number }}
- 注文日時: {{ $order->ordered_at->format('Y年n月j日 H:i') }}
- ご注文者: {{ $order->customer_name }} 様（{{ $order->member_code_snapshot }}）
- お支払い方法: {{ $order->payment_method->label() }}
- 合計金額: {{ number_format($order->total) }}円

<x-mail::button :url="route('admin.orders.show', $order)">
注文詳細を開く
</x-mail::button>

{{ config('app.name') }}
</x-mail::message>
