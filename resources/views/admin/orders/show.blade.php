@extends('admin.layout')

@section('content')
<div class="container">
    <h1>{{ $order->order_number }}</h1>
    <div class="card">
        <p>Pelanggan: {{ $order->user->name }} ({{ $order->user->email }})</p>
        <p>Total: <strong>Rp {{ number_format($order->total, 0, ',', '.') }}</strong></p>
        <form method="POST" action="{{ route('admin.orders.update', $order) }}">
            @csrf @method('PATCH')
            <label>Status</label><select name="status">@foreach(\App\Models\Order::STATUSES as $status)<option value="{{ $status }}" @selected($order->status === $status)>{{ ucfirst($status) }}</option>@endforeach</select>
            <label>Pembayaran</label><select name="payment_status">@foreach(\App\Models\Order::PAYMENT_STATUSES as $status)<option value="{{ $status }}" @selected($order->payment_status === $status)>{{ ucfirst($status) }}</option>@endforeach</select>
            <label>Nomor resi</label><input name="tracking_number" value="{{ $order->tracking_number }}" placeholder="Nomor resi (opsional)">
            <button type="submit">Simpan Status</button>
        </form>
        <h2>Alamat</h2><p>{{ $order->recipient_name }} — {{ $order->phone }}<br>{{ $order->address_line }}<br>{{ $order->city }}, {{ $order->state }} {{ $order->postal_code }}</p>
        <h2>Ringkasan</h2><p>Pengiriman: {{ ucfirst($order->shipping_method) }} — Rp {{ number_format($order->shipping_cost, 0, ',', '.') }}</p><p>Pembayaran: {{ ucfirst($order->payment_method) }} / {{ ucfirst($order->payment_status) }}</p><p>Kupon: {{ $order->coupon_code ?: '-' }} — Diskon Rp {{ number_format($order->discount, 0, ',', '.') }}</p>
        <h2>Item</h2>@foreach($order->items as $item)<p>{{ $item->product_name }} × {{ $item->quantity }} — Rp {{ number_format($item->line_total, 0, ',', '.') }}</p>@endforeach
    </div>
</div>
@endsection
