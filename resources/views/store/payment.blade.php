@extends('store.layout', ['title' => 'Pembayaran'])
@section('content')
<section class="mx-auto max-w-2xl px-6 py-16 text-center">
    <p class="text-sm font-bold uppercase tracking-widest text-indigo-600">Pembayaran</p>
    <h1 class="mt-3 text-4xl font-black">Selesaikan pembayaran</h1>
    <p class="mt-4 text-slate-600">Pesanan {{ $order->order_number }} · Rp {{ number_format($order->total, 0, ',', '.') }}</p>
    <button id="pay-button" class="mt-8 rounded-xl bg-slate-950 px-8 py-4 font-bold text-white">Bayar sekarang</button>
    <p class="mt-5 text-sm text-slate-500">Jangan tutup halaman sampai proses pembayaran selesai.</p>
</section>
<script src="{{ config('services.midtrans.is_production') ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js' }}" data-client-key="{{ config('services.midtrans.client_key') }}"></script>
<script>
document.getElementById('pay-button').addEventListener('click', function () {
    window.snap.pay(@json($payment['token']), {
        onSuccess: () => window.location.href = @json(route('orders.show', $order)),
        onPending: () => window.location.href = @json(route('orders.show', $order)),
        onError: () => window.location.href = @json(route('orders.show', $order)),
        onClose: () => {}
    });
});
</script>
@endsection
