<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $orders = $request->user()->orders()->latest()->paginate(10);
        return view('account.orders.index', compact('orders'));
    }

    public function show(Request $request, Order $order): View
    {
        abort_unless($order->user_id === $request->user()->id, 404);
        $order->load('items');
        return view('account.orders.show', compact('order'));
    }

    public function cancel(Request $request, Order $order): RedirectResponse
    {
        abort_unless($order->user_id === $request->user()->id, 404);
        if (!in_array($order->status, ['pending', 'processing'], true)) {
            return back()->withErrors(['order' => 'Pesanan ini sudah tidak dapat dibatalkan.']);
        }

        DB::transaction(function () use ($order) {
            $order->load('items');
            foreach ($order->items as $item) {
                if ($item->product_variant_id) {
                    ProductVariant::whereKey($item->product_variant_id)->lockForUpdate()->first()?->increment('stock', $item->quantity);
                } elseif ($item->product_id) {
                    Product::whereKey($item->product_id)->lockForUpdate()->first()?->increment('stock', $item->quantity);
                }
            }
            $order->update(['status' => 'cancelled', 'cancelled_at' => now()]);
            $order->recordStatusChange('cancelled', 'customer', 'Order cancelled by customer.');
        });

        return back()->with('status', 'Pesanan dibatalkan dan stok dikembalikan.');
    }
}
