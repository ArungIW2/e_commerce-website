<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
        $orders = Order::with('user')->latest()->paginate(15);
        return view('admin.orders.index', compact('orders'));
    }

    public function show(Order $order): View
    {
        $order->load(['user', 'items']);
        return view('admin.orders.show', compact('order'));
    }

    public function update(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:'.implode(',', Order::STATUSES)],
            'payment_status' => ['required', 'in:'.implode(',', Order::PAYMENT_STATUSES)],
            'tracking_number' => ['nullable', 'string', 'max:100'],
        ]);

        $newStatus = $validated['status'];
        if ($newStatus === 'cancelled' && $order->status !== 'cancelled') {
            if (!in_array($order->status, ['pending', 'processing'], true)) {
                return back()->withErrors(['status' => 'Pesanan hanya dapat dibatalkan saat pending atau processing.']);
            }
            DB::transaction(function () use ($order, $validated) {
                $order->load('items');
                foreach ($order->items as $item) {
                    if ($item->product_variant_id) {
                        ProductVariant::whereKey($item->product_variant_id)->lockForUpdate()->first()?->increment('stock', $item->quantity);
                    } elseif ($item->product_id) {
                        Product::whereKey($item->product_id)->lockForUpdate()->first()?->increment('stock', $item->quantity);
                    }
                }
                $order->update([
                    'status' => 'cancelled', 'cancelled_at' => now(),
                    'payment_status' => $validated['payment_status'], 'tracking_number' => $validated['tracking_number'],
                ]);
            });
        } else {
            $order->update([
                'status' => $newStatus,
                'payment_status' => $validated['payment_status'],
                'tracking_number' => $validated['tracking_number'],
            ]);
        }

        return back()->with('status', 'Status pesanan diperbarui.');
    }
}
