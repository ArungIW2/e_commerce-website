@extends('store.layout')
@section('content')
<div class="mx-auto max-w-6xl px-6 py-12">
    <h1 class="text-4xl font-black">Checkout</h1>
    <div class="mt-10 grid gap-8 lg:grid-cols-[1.5fr_1fr]">
        <section class="rounded-2xl border bg-white p-6 shadow-sm">
            <h2 class="text-xl font-black">Alamat Pengiriman</h2>
            @if($addresses->isNotEmpty())<label class="mt-5 block text-sm font-bold">Alamat tersimpan</label><select name="address_id" form="checkout-form" class="mt-2 w-full rounded-xl border px-4 py-3"><option value="">Gunakan alamat baru</option>@foreach($addresses as $address)<option value="{{ $address->id }}" @selected($address->is_default)>{{ $address->label }} — {{ $address->recipient_name }}, {{ $address->city }}</option>@endforeach</select>@endif
            <form id="checkout-form" method="POST" action="{{ route('checkout.store') }}" class="mt-5 space-y-4">@csrf
                <p class="font-bold">Atau isi alamat baru</p><input name="label" placeholder="Label, mis. Rumah" class="w-full rounded-xl border px-4 py-3"><input name="recipient_name" placeholder="Nama penerima" class="w-full rounded-xl border px-4 py-3"><input name="phone" placeholder="Nomor telepon" class="w-full rounded-xl border px-4 py-3"><textarea name="address_line" placeholder="Alamat lengkap" class="w-full rounded-xl border px-4 py-3"></textarea><div class="grid gap-4 sm:grid-cols-2"><input name="city" placeholder="Kota/Kabupaten" class="rounded-xl border px-4 py-3"><input name="state" placeholder="Provinsi" class="rounded-xl border px-4 py-3"><input name="postal_code" placeholder="Kode pos" class="rounded-xl border px-4 py-3"></div>
                <h2 class="pt-4 text-xl font-black">Pengiriman</h2><select name="shipping_method" required class="w-full rounded-xl border px-4 py-3">@foreach($shippingMethods as $key=>$method)<option value="{{ $key }}">{{ $method['label'] }} — Rp {{ number_format($method['cost'],0,',','.') }}</option>@endforeach</select>
                <h2 class="pt-4 text-xl font-black">Pembayaran</h2><select name="payment_method" required class="w-full rounded-xl border px-4 py-3">@foreach($paymentMethods as $key=>$label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select>
                <p class="rounded-xl bg-indigo-50 p-4 text-sm text-indigo-700">Midtrans menyediakan QRIS, e-wallet, virtual account, kartu, dan metode pembayaran lain sesuai konfigurasi akun merchant.</p>
                <h2 class="pt-4 text-xl font-black">Kupon</h2><input name="coupon_code" placeholder="Kode kupon (opsional)" class="w-full rounded-xl border px-4 py-3">
                <button type="submit" class="w-full rounded-xl bg-slate-950 px-6 py-4 font-bold text-white">Buat Pesanan</button>
            </form>
        </section>
        <aside class="h-fit rounded-2xl border bg-white p-6 shadow-sm"><h2 class="text-xl font-black">Ringkasan</h2>@foreach($items as $item)<div class="mt-4 flex justify-between gap-4 text-sm"><span>{{ $item->product->name }} × {{ $item->quantity }}</span><span>Rp {{ number_format(($item->variant?->price??$item->product->price)*$item->quantity,0,',','.') }}</span></div>@endforeach<hr class="my-5"><div class="flex justify-between font-black"><span>Subtotal</span><span>Rp {{ number_format($subtotal,0,',','.') }}</span></div></aside>
    </div>
</div>
@endsection
