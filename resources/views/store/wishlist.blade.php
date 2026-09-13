@extends('store.layout', ['title' => 'Wishlist'])
@section('content')
<section class="mx-auto max-w-7xl px-6 py-12">
    <div class="flex items-end justify-between gap-4">
        <div><p class="text-sm font-bold uppercase tracking-widest text-indigo-600">Saved products</p><h1 class="mt-2 text-4xl font-black">Wishlist</h1></div>
        <span class="rounded-full bg-slate-100 px-3 py-1 text-sm font-bold">{{ $items->count() }} item</span>
    </div>

    @if($items->isEmpty())
        <div class="mt-10 rounded-2xl border bg-white p-10 text-center shadow-sm">
            <h2 class="text-xl font-bold">Wishlist masih kosong</h2>
            <p class="mt-2 text-slate-500">Simpan produk yang kamu inginkan agar mudah ditemukan lagi.</p>
            <a href="{{ route('products.index') }}" class="mt-6 inline-block rounded-xl bg-slate-950 px-6 py-3 font-bold text-white">Browse products</a>
        </div>
    @else
        <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach($items as $item)
                @php($product=$item->product)
                <article class="rounded-2xl border bg-white p-5 shadow-sm">
                    <a href="{{ route('products.show', $product) }}" class="block">
                        <div class="aspect-square overflow-hidden rounded-xl bg-slate-100">
                            @if($product->primaryImage())<img src="{{ $product->primaryImage()->url() }}" alt="{{ $product->primaryImage()->alt_text ?: $product->name }}" class="h-full w-full object-cover">@elseif($product->image)<img src="{{ $product->image }}" alt="{{ $product->name }}" class="h-full w-full object-cover">@endif
                        </div>
                        <p class="mt-4 text-xs font-bold uppercase tracking-widest text-indigo-600">{{ $product->category?->name }}</p>
                        <h2 class="mt-1 font-bold">{{ $product->name }}</h2>
                        <p class="mt-2 font-black">Rp {{ number_format($product->price, 0, ',', '.') }}</p>
                    </a>
                    <div class="mt-4 flex gap-2">
                        <form method="POST" action="{{ route('wishlist.cart', $product) }}" class="flex-1">@csrf<button class="w-full rounded-xl bg-slate-950 px-3 py-2 text-sm font-bold text-white">@if($product->variants->where('is_active', true)->isNotEmpty()) Choose variant @else Add to cart @endif</button></form>
                        <form method="POST" action="{{ route('wishlist.destroy', $product) }}">@csrf @method('DELETE')<button aria-label="Remove {{ $product->name }}" class="rounded-xl border px-3 py-2 text-sm font-bold">×</button></form>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</section>
@endsection
