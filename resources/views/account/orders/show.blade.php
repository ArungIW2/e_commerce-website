@extends('store.layout')

@section('content')
<div class="container">
    <h1>Detail Pesanan</h1>
    <div class="card">
        <p><strong>{{ $order->order_number }}</strong></p>
        <p>Status: <strong>{{ ucfirst($order->status) }}</strong></p>
        <p>Dibuat: {{ $order->created_at->format('d M Y H:i') }}</p>
        <h2>Pengiriman</h2>
        <p>{{ $order->recipient_name }} — {{ $order->phone }}<br>{{ $order->address_line }}<br>{{ $order->city }}, {{ $order->state }} {{ $order->postal_code }}</p>
        <h2>Produk</h2>
        @foreach($order->items as $item)
            <div style="display:flex;justify-content:space-between;border-bottom:1px solid #ddd;padding:.75rem 0">
                <span>{{ $item->product_name }} × {{ $item->quantity }}</span>
                <span>Rp {{ number_format($item->line_total, 0, ',', '.') }}</span>
            </div>
        @endforeach
        <p><strong>Total: Rp {{ number_format($order->total, 0, ',', '.') }}</strong></p>
    </div>
</div>
@endsection
