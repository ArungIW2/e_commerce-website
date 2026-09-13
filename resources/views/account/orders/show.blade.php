@extends('store.layout')

@section('content')
<div class="container">
    <h1>Detail Pesanan</h1>
    <div class="card">
        <p><strong>{{ $order->order_number }}</strong></p>
        <p>Status: <strong>{{ ucfirst($order->status) }}</strong></p>
        <p>Pembayaran: {{ ucfirst($order->payment_status) }} · {{ $order->payment_method }}</p>
        <p>Pengiriman: {{ ucfirst($order->shipping_method) }}{{ $order->tracking_number ? ' · Resi: '.$order->tracking_number : '' }}</p>
        @if($order->shipping_status)
            <p>Status kurir: <strong>{{ $order->shipping_status }}</strong>{{ $order->shipping_courier ? ' · '.$order->shipping_courier : '' }}{{ $order->shipping_service ? ' / '.$order->shipping_service : '' }}</p>
        @endif
        @if($order->shipping_tracking_url)
            <p><a href="{{ $order->shipping_tracking_url }}" target="_blank" rel="noopener">Lacak paket</a></p>
        @endif
        <p>Dibuat: {{ $order->created_at->format('d M Y H:i') }}</p>
        <h2>Pengiriman</h2>
        <p>{{ $order->recipient_name }} — {{ $order->phone }}<br>{{ $order->address_line }}<br>{{ $order->city }}, {{ $order->state }} {{ $order->postal_code }}</p>
        <h2>Produk</h2>
        @foreach($order->items as $item)<div style="display:flex;justify-content:space-between;border-bottom:1px solid #ddd;padding:.75rem 0"><span>{{ $item->product_name }} × {{ $item->quantity }}</span><span>Rp {{ number_format($item->line_total, 0, ',', '.') }}</span></div>@endforeach
        <p>Subtotal: Rp {{ number_format($order->subtotal, 0, ',', '.') }}</p>
        <p>Pengiriman: Rp {{ number_format($order->shipping_cost, 0, ',', '.') }}</p>
        <p>Diskon: Rp {{ number_format($order->discount, 0, ',', '.') }}{{ $order->coupon_code ? ' ('.$order->coupon_code.')' : '' }}</p>
        <p><strong>Total: Rp {{ number_format($order->total, 0, ',', '.') }}</strong></p>
        @if(in_array($order->status, ['pending','processing'], true))
            <form method="POST" action="{{ route('orders.cancel', $order) }}" onsubmit="return confirm('Batalkan pesanan ini?')">@csrf @method('PATCH')<button type="submit">Batalkan Pesanan</button></form>
        @endif
    </div>
</div>
@endsection
