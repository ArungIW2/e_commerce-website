@extends('layouts.admin')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-2xl font-bold">Reports & Analytics</h1>
            <p class="text-sm text-gray-600">Sales and order performance for the selected period.</p>
        </div>
        <form method="GET" class="flex flex-wrap gap-2 items-end">
            <label class="text-sm">From<input type="date" name="from" value="{{ $from->toDateString() }}" class="block rounded border p-2"></label>
            <label class="text-sm">To<input type="date" name="to" value="{{ $to->toDateString() }}" class="block rounded border p-2"></label>
            <button class="rounded bg-black px-4 py-2 text-white">Apply</button>
        </form>
    </div>

    <div class="grid gap-4 md:grid-cols-4">
        <div class="rounded border bg-white p-4"><div class="text-sm text-gray-500">Completed sales</div><div class="text-2xl font-bold">Rp {{ number_format($sales, 0, ',', '.') }}</div></div>
        <div class="rounded border bg-white p-4"><div class="text-sm text-gray-500">Orders</div><div class="text-2xl font-bold">{{ $orderCount }}</div></div>
        <div class="rounded border bg-white p-4"><div class="text-sm text-gray-500">Completed</div><div class="text-2xl font-bold">{{ $completedCount }}</div></div>
        <div class="rounded border bg-white p-4"><div class="text-sm text-gray-500">Average completed order</div><div class="text-2xl font-bold">Rp {{ number_format($averageOrder, 0, ',', '.') }}</div></div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="rounded border bg-white p-4">
            <h2 class="mb-4 font-semibold">Daily performance</h2>
            <div class="overflow-x-auto"><table class="w-full text-sm"><thead><tr class="border-b text-left"><th class="p-2">Date</th><th class="p-2">Orders</th><th class="p-2">Revenue</th></tr></thead><tbody>
                @forelse($daily as $row)<tr class="border-b"><td class="p-2">{{ $row->day }}</td><td class="p-2">{{ $row->orders }}</td><td class="p-2">Rp {{ number_format($row->revenue, 0, ',', '.') }}</td></tr>@empty<tr><td colspan="3" class="p-4 text-center text-gray-500">No data.</td></tr>@endforelse
            </tbody></table></div>
        </section>

        <section class="rounded border bg-white p-4">
            <h2 class="mb-4 font-semibold">Top products</h2>
            <div class="overflow-x-auto"><table class="w-full text-sm"><thead><tr class="border-b text-left"><th class="p-2">Product</th><th class="p-2">Qty</th><th class="p-2">Revenue</th></tr></thead><tbody>
                @forelse($topProducts as $product)<tr class="border-b"><td class="p-2">{{ $product->product_name }}</td><td class="p-2">{{ $product->quantity }}</td><td class="p-2">Rp {{ number_format($product->revenue, 0, ',', '.') }}</td></tr>@empty<tr><td colspan="3" class="p-4 text-center text-gray-500">No completed sales.</td></tr>@endforelse
            </tbody></table></div>
        </section>
    </div>

    <section class="rounded border bg-white p-4">
        <h2 class="mb-4 font-semibold">Order status breakdown</h2>
        <div class="flex flex-wrap gap-3">@foreach($statusBreakdown as $status)<div class="rounded border px-4 py-3"><span class="font-medium">{{ ucfirst($status->status) }}</span><span class="ml-2 text-gray-600">{{ $status->total }}</span></div>@endforeach</div>
    </section>
</div>
@endsection
