@extends('store.layout')
@section('content')
<div class="mx-auto max-w-6xl px-6 py-12">
    <h1 class="text-4xl font-black">Checkout</h1>
    <div class="mt-10 grid gap-8 lg:grid-cols-[1.5fr_1fr]">
        <section class="rounded-2xl border bg-white p-6 shadow-sm">
            <h2 class="text-xl font-black">Alamat Pengiriman</h2>
            @if($addresses->isNotEmpty())
                <label class="mt-5 block text-sm font-bold">Alamat tersimpan</label>
                <select id="saved-address" name="address_id" form="checkout-form" class="mt-2 w-full rounded-xl border px-4 py-3">
                    <option value="">Gunakan alamat baru</option>
                    @foreach($addresses as $address)
                        <option value="{{ $address->id }}" data-postal-code="{{ $address->postal_code }}" @selected($address->is_default)>{{ $address->label }} — {{ $address->recipient_name }}, {{ $address->city }}</option>
                    @endforeach
                </select>
            @endif
            <form id="checkout-form" method="POST" action="{{ route('checkout.store') }}" class="mt-5 space-y-4">
                @csrf
                <p class="font-bold">Atau isi alamat baru</p>
                <input name="label" placeholder="Label, mis. Rumah" class="w-full rounded-xl border px-4 py-3">
                <input name="recipient_name" placeholder="Nama penerima" class="w-full rounded-xl border px-4 py-3">
                <input name="phone" placeholder="Nomor telepon" class="w-full rounded-xl border px-4 py-3">
                <textarea name="address_line" placeholder="Alamat lengkap" class="w-full rounded-xl border px-4 py-3"></textarea>
                <div class="grid gap-4 sm:grid-cols-2">
                    <input name="city" placeholder="Kota/Kabupaten" class="rounded-xl border px-4 py-3">
                    <input name="state" placeholder="Provinsi" class="rounded-xl border px-4 py-3">
                    <input id="postal-code" name="postal_code" placeholder="Kode pos" inputmode="numeric" maxlength="5" class="rounded-xl border px-4 py-3">
                </div>

                <h2 class="pt-4 text-xl font-black">Pengiriman</h2>
                <p class="text-sm text-slate-500">Tarif dihitung dari alamat tujuan dan isi keranjang menggunakan Biteship.</p>
                <div id="shipping-loading" class="hidden rounded-xl bg-slate-50 p-4 text-sm">Mengambil tarif kurir…</div>
                <select id="shipping-method" name="shipping_method" required class="w-full rounded-xl border px-4 py-3">
                    <option value="">Pilih layanan pengiriman</option>
                </select>
                <input type="hidden" id="shipping-courier" name="shipping_courier">
                <input type="hidden" id="shipping-service-code" name="shipping_service_code">
                <p id="shipping-error" class="hidden rounded-xl bg-red-50 p-4 text-sm text-red-700"></p>

                <h2 class="pt-4 text-xl font-black">Pembayaran</h2>
                <select id="payment-method" name="payment_method" required class="w-full rounded-xl border px-4 py-3">
                    @foreach($paymentMethods as $key=>$label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                </select>
                <p class="rounded-xl bg-indigo-50 p-4 text-sm text-indigo-700">Midtrans menyediakan QRIS, e-wallet, virtual account, kartu, dan metode pembayaran lain sesuai konfigurasi akun merchant.</p>

                <h2 class="pt-4 text-xl font-black">Kupon</h2>
                <input name="coupon_code" placeholder="Kode kupon (opsional)" class="w-full rounded-xl border px-4 py-3">
                <button type="submit" class="w-full rounded-xl bg-slate-950 px-6 py-4 font-bold text-white">Buat Pesanan</button>
            </form>
        </section>

        <aside class="h-fit rounded-2xl border bg-white p-6 shadow-sm">
            <h2 class="text-xl font-black">Ringkasan</h2>
            @foreach($items as $item)
                <div class="mt-4 flex justify-between gap-4 text-sm"><span>{{ $item->product->name }} × {{ $item->quantity }}</span><span>Rp {{ number_format(($item->variant?->price??$item->product->price)*$item->quantity,0,',','.') }}</span></div>
            @endforeach
            <hr class="my-5">
            <div class="flex justify-between font-black"><span>Subtotal</span><span>Rp {{ number_format($subtotal,0,',','.') }}</span></div>
            <div id="shipping-summary" class="mt-2 flex justify-between text-sm"><span>Pengiriman</span><span>—</span></div>
        </aside>
    </div>
</div>

<script>
(() => {
    const postal = document.getElementById('postal-code');
    const address = document.getElementById('saved-address');
    const payment = document.getElementById('payment-method');
    const shipping = document.getElementById('shipping-method');
    const courier = document.getElementById('shipping-courier');
    const service = document.getElementById('shipping-service-code');
    const loading = document.getElementById('shipping-loading');
    const error = document.getElementById('shipping-error');
    const summary = document.getElementById('shipping-summary').querySelector('span:last-child');
    let timer;

    function selectedPostal() {
        const option = address?.selectedOptions?.[0];
        return option?.value ? option.dataset.postalCode : postal.value;
    }

    async function loadRates() {
        const postalCode = selectedPostal();
        shipping.innerHTML = '<option value="">Pilih layanan pengiriman</option>';
        courier.value = '';
        service.value = '';
        summary.textContent = '—';
        error.classList.add('hidden');
        if (!/^\d{5}$/.test(postalCode || '')) return;

        loading.classList.remove('hidden');
        try {
            const url = new URL('{{ route('shipping.rates') }}', window.location.origin);
            url.searchParams.set('postal_code', postalCode);
            url.searchParams.set('payment_method', payment.value);
            const response = await fetch(url, { headers: { Accept: 'application/json' } });
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'Tarif pengiriman tidak tersedia.');
            if (!data.rates?.length) throw new Error('Tidak ada layanan kurir untuk alamat tersebut.');

            data.rates.forEach(rate => {
                const option = document.createElement('option');
                option.value = rate.id;
                option.dataset.courier = rate.courier_code;
                option.dataset.service = rate.service_code;
                option.dataset.price = rate.price;
                option.textContent = `${rate.courier_name} — ${rate.service_name} · Rp ${Number(rate.price).toLocaleString('id-ID')}${rate.duration ? ` · ${rate.duration}` : ''}`;
                shipping.appendChild(option);
            });
        } catch (e) {
            error.textContent = e.message;
            error.classList.remove('hidden');
        } finally {
            loading.classList.add('hidden');
        }
    }

    shipping.addEventListener('change', () => {
        const option = shipping.selectedOptions[0];
        courier.value = option?.dataset.courier || '';
        service.value = option?.dataset.service || '';
        summary.textContent = option?.dataset.price ? `Rp ${Number(option.dataset.price).toLocaleString('id-ID')}` : '—';
    });
    payment.addEventListener('change', loadRates);
    address?.addEventListener('change', loadRates);
    postal.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(loadRates, 400); });
    loadRates();
})();
</script>
@endsection
