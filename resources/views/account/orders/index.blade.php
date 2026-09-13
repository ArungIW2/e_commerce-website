@extends('store.layout')

@section('content')
<div class="container">
    <h1>Pesanan Saya</h1>
    @forelse($orders as $order)
        <div class="card" style="margin-bottom:1rem">
            <div style="display:flex;justify-content:space-between;gap:1rem;flex-wrap:wrap">
                <div><strong>{{ $order->order_number }}</strong><br><small>{{ $order->created_at->format('d M Y H:i') }}</small></div>
                <div>{{ ucfirst($order->status) }}<br><strong>Rp {{ number_format($order->total, 0, ',', '.') }}</strong></div>
                <a href="{{ route('orders.show', $order) }}">Lihat detail</a>
            </div>
        </div>
    @empty
        <p>Belum ada pesanan.</p>
    @endforelse
    {{ $orders->links() }}
</div>
@endsection
