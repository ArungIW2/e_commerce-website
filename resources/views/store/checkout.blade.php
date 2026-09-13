@extends('store.layout')

@section('content')
<div class="container">
    <h1>Checkout</h1>
    <div class="checkout-grid">
        <section>
            <h2>Alamat Pengiriman</h2>
            @if($addresses->isNotEmpty())
                <label>Alamat tersimpan</label>
                <select name="address_id" form="checkout-form">
                    <option value="">Gunakan alamat baru</option>
                    @foreach($addresses as $address)
                        <option value="{{ $address->id }}" @selected($address->is_default)>{{ $address->label }} — {{ $address->recipient_name }}, {{ $address->city }}</option>
                    @endforeach
                </select>
            @endif

            <form id="checkout-form" method="POST" action="{{ route('checkout.store') }}">
                @csrf
                <p><strong>Atau isi alamat baru</strong></p>
                <input name="label" placeholder="Label, mis. Rumah">
                <input name="recipient_name" placeholder="Nama penerima">
                <input name="phone" placeholder="Nomor telepon">
                <textarea name="address_line" placeholder="Alamat lengkap"></textarea>
                <input name="city" placeholder="Kota/Kabupaten">
                <input name="state" placeholder="Provinsi">
                <input name="postal_code" placeholder="Kode pos">
                <button type="submit">Buat Pesanan</button>
            </form>
        </section>

        <aside class="card">
            <h2>Ringkasan</h2>
            @foreach($items as $item)
                <div class="summary-row">
                    <span>{{ $item->product->name }} × {{ $item->quantity }}</span>
                    <span>Rp {{ number_format($item->product->price * $item->quantity, 0, ',', '.') }}</span>
                </div>
            @endforeach
            <hr>
            <div class="summary-row"><strong>Total</strong><strong>Rp {{ number_format($subtotal, 0, ',', '.') }}</strong></div>
            <p>Biaya pengiriman dan pembayaran akan ditambahkan pada tahap berikutnya.</p>
        </aside>
    </div>
</div>
@endsection
