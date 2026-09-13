@extends('admin.layout')

@section('content')
<div class="container">
    <h1>Pesanan</h1>
    <table style="width:100%;border-collapse:collapse">
        <thead><tr><th>Order</th><th>Pelanggan</th><th>Total</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @foreach($orders as $order)
            <tr>
                <td>{{ $order->order_number }}</td>
                <td>{{ $order->user->name }}<br>{{ $order->user->email }}</td>
                <td>Rp {{ number_format($order->total, 0, ',', '.') }}</td>
                <td>{{ ucfirst($order->status) }}</td>
                <td><a href="{{ route('admin.orders.show', $order) }}">Detail</a></td>
            </tr>
        @endforeach
        </tbody>
    </table>
    {{ $orders->links() }}
</div>
@endsection
