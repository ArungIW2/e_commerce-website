<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        $cart = Cart::where('user_id', Auth::id())->with('items.product')->first();
        $items = $cart?->items->filter(fn ($item) => $item->product?->is_active && $item->product->stock > 0)->values() ?? collect();

        if ($items->isEmpty()) {
            return redirect()->route('cart')->withErrors(['cart' => 'Keranjang masih kosong.']);
        }

        $items->each(fn ($item) => $item->quantity = min($item->quantity, $item->product->stock));
        $subtotal = $items->sum(fn ($item) => $item->product->price * $item->quantity);
        $addresses = Auth::user()->addresses()->latest('is_default')->latest()->get();

        return view('store.checkout', compact('items', 'subtotal', 'addresses'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'address_id' => ['nullable', 'integer', 'exists:addresses,id'],
            'label' => ['nullable', 'string', 'max:50'],
            'recipient_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address_line' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:10'],
        ]);

        $order = DB::transaction(function () use ($request, $validated) {
            $user = $request->user();
            $address = null;

            if (! empty($validated['address_id'])) {
                $address = $user->addresses()->findOrFail($validated['address_id']);
            } else {
                $data = collect($validated)->only(['label', 'recipient_name', 'phone', 'address_line', 'city', 'state', 'postal_code'])->all();
                foreach (['recipient_name', 'phone', 'address_line', 'city', 'postal_code'] as $field) {
                    if (empty($data[$field])) abort(422, 'Data alamat belum lengkap.');
                }
                $data['label'] = $data['label'] ?? 'Rumah';
                $data['user_id'] = $user->id;
                $data['is_default'] = ! $user->addresses()->exists();
                if ($data['is_default']) $user->addresses()->update(['is_default' => false]);
                $address = Address::create($data);
            }

            $cart = Cart::where('user_id', $user->id)->with('items')->firstOrFail();
            $cartItems = $cart->items;
            if ($cartItems->isEmpty()) abort(422, 'Keranjang kosong.');

            $orderLines = [];
            $subtotal = 0;
            foreach ($cartItems as $cartItem) {
                $product = Product::whereKey($cartItem->product_id)->lockForUpdate()->first();
                if (! $product || ! $product->is_active || $product->stock < $cartItem->quantity) {
                    abort(422, "Stok produk {$cartItem->product_id} tidak mencukupi.");
                }

                $lineTotal = $product->price * $cartItem->quantity;
                $subtotal += $lineTotal;
                $orderLines[] = compact('product', 'cartItem', 'lineTotal');
            }

            $order = Order::create([
                'user_id' => $user->id,
                'order_number' => 'TMP-' . Str::upper(Str::random(16)),
                'status' => 'pending',
                'subtotal' => $subtotal,
                'shipping_cost' => 0,
                'discount' => 0,
                'total' => $subtotal,
                'recipient_name' => $address->recipient_name,
                'phone' => $address->phone,
                'address_line' => $address->address_line,
                'city' => $address->city,
                'state' => $address->state,
                'postal_code' => $address->postal_code,
            ]);

            $order->update(['order_number' => 'ORD-' . now()->format('Ymd') . '-' . str_pad((string) $order->id, 6, '0', STR_PAD_LEFT)]);

            foreach ($orderLines as $line) {
                $product = $line['product'];
                $quantity = $line['cartItem']->quantity;
                $order->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'sku' => $product->sku,
                    'price' => $product->price,
                    'quantity' => $quantity,
                    'line_total' => $line['lineTotal'],
                ]);
                $product->decrement('stock', $quantity);
            }

            $cart->items()->delete();
            return $order;
        });

        return redirect()->route('orders.show', $order)->with('status', 'Pesanan berhasil dibuat.');
    }
}
