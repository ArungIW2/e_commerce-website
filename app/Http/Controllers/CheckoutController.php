<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\BiteshipShippingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class CheckoutController extends Controller
{
    private const SHIPPING_METHODS = [
        'standard' => ['label' => 'Standard (2–5 hari)', 'cost' => 15000],
        'express' => ['label' => 'Express (1–2 hari)', 'cost' => 30000],
        'pickup' => ['label' => 'Ambil di toko', 'cost' => 0],
    ];

    private const PAYMENT_METHODS = [
        'midtrans' => 'Midtrans (QRIS, e-wallet, VA, kartu)',
        'cod' => 'Cash on Delivery (COD)',
        'manual' => 'Transfer bank / pembayaran manual',
    ];

    public function show(Request $request): View|RedirectResponse
    {
        $cart = Cart::where('user_id', Auth::id())
            ->with('items.product', 'items.variant.attributeValues.attribute')
            ->first();
        $items = $cart?->items
            ->filter(fn ($i) => $i->product?->is_active && ($i->variant?->stock ?? $i->product->stock) > 0)
            ->values() ?? collect();

        if ($items->isEmpty()) {
            return redirect()->route('cart')->withErrors(['cart' => 'Keranjang masih kosong.']);
        }

        $items->each(fn ($i) => $i->quantity = min($i->quantity, $i->variant?->stock ?? $i->product->stock));
        $subtotal = $items->sum(fn ($i) => (float) ($i->variant?->price ?? $i->product->price) * $i->quantity);
        $addresses = Auth::user()->addresses()->latest('is_default')->latest()->get();

        return view('store.checkout', [
            'items' => $items,
            'subtotal' => $subtotal,
            'addresses' => $addresses,
            'shippingMethods' => self::SHIPPING_METHODS,
            'paymentMethods' => self::PAYMENT_METHODS,
        ]);
    }

    public function store(Request $request, BiteshipShippingService $shipping): RedirectResponse
    {
        $validated = $request->validate([
            'address_id' => ['nullable', 'integer', 'exists:addresses,id'],
            'label' => ['nullable', 'string', 'max:50'],
            'recipient_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address_line' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'digits:5'],
            'shipping_method' => ['required', 'string', 'max:100'],
            'shipping_courier' => ['nullable', 'string', 'max:50', 'required_if:shipping_method,biteship'],
            'shipping_service_code' => ['nullable', 'string', 'max:100', 'required_if:shipping_method,biteship'],
            'payment_method' => ['required', 'in:' . implode(',', array_keys(self::PAYMENT_METHODS))],
            'coupon_code' => ['nullable', 'string', 'max:50'],
        ]);

        try {
            $order = DB::transaction(function () use ($request, $validated, $shipping) {
                $user = $request->user();
                $address = !empty($validated['address_id'])
                    ? $user->addresses()->findOrFail($validated['address_id'])
                    : null;

                if (!$address) {
                    $data = collect($validated)->only(['label', 'recipient_name', 'phone', 'address_line', 'city', 'state', 'postal_code'])->all();
                    foreach (['recipient_name', 'phone', 'address_line', 'city', 'postal_code'] as $field) {
                        if (empty($data[$field])) abort(422, 'Data alamat belum lengkap.');
                    }
                    $data['label'] = $data['label'] ?? 'Rumah';
                    $data['user_id'] = $user->id;
                    $data['is_default'] = !$user->addresses()->exists();
                    if ($data['is_default']) $user->addresses()->update(['is_default' => false]);
                    $address = Address::create($data);
                }

                $cart = Cart::where('user_id', $user->id)
                    ->with('items.product', 'items.variant.attributeValues.attribute')
                    ->firstOrFail();
                if ($cart->items->isEmpty()) abort(422, 'Keranjang kosong.');

                $orderLines = [];
                $subtotal = 0;
                foreach ($cart->items as $cartItem) {
                    $product = Product::whereKey($cartItem->product_id)->lockForUpdate()->first();
                    $variant = $cartItem->product_variant_id
                        ? ProductVariant::whereKey($cartItem->product_variant_id)->lockForUpdate()->first()
                        : null;
                    if (!$product || !$product->is_active || ($cartItem->product_variant_id && (!$variant || $variant->product_id !== $product->id || !$variant->is_active)) || ($variant ? $variant->stock : $product->stock) < $cartItem->quantity) {
                        abort(422, 'Stok produk/varian tidak mencukupi.');
                    }
                    $price = (float) ($variant?->price ?? $product->price);
                    $lineTotal = $price * $cartItem->quantity;
                    $subtotal += $lineTotal;
                    $orderLines[] = compact('product', 'variant', 'cartItem', 'lineTotal');
                }

                $coupon = null;
                $discount = 0;
                if (!empty($validated['coupon_code'])) {
                    $coupon = Coupon::where('code', Str::upper(trim($validated['coupon_code'])))->lockForUpdate()->first();
                    if (!$coupon || !$coupon->isValidFor((float) $subtotal)) abort(422, 'Kupon tidak valid, sudah habis, atau tidak memenuhi syarat.');
                    $discount = $coupon->calculateDiscount((float) $subtotal);
                }

                $shippingCost = 0;
                $shippingCourier = null;
                $shippingService = null;
                $shippingMethod = $validated['shipping_method'];

                if ($shippingMethod === 'biteship') {
                    try {
                        $rates = $shipping->rates(
                            $address->postal_code,
                            $cart->items,
                            $validated['payment_method'] === 'cod' ? (int) round($subtotal - $discount) : null,
                        );
                    } catch (Throwable $e) {
                        report($e);
                        abort(503, 'Tarif pengiriman sedang tidak tersedia. Silakan coba lagi.');
                    }

                    $rate = collect($rates)->first(fn (array $rate) =>
                        $rate['courier_code'] === $validated['shipping_courier']
                        && $rate['service_code'] === $validated['shipping_service_code']
                    );

                    if (!$rate || ($validated['payment_method'] === 'cod' && !$rate['available_for_cod'])) {
                        abort(422, 'Layanan pengiriman yang dipilih sudah tidak tersedia. Silakan pilih ulang.');
                    }

                    $shippingCost = $rate['price'];
                    $shippingCourier = $rate['courier_code'];
                    $shippingService = $rate['service_name'];
                    $shippingMethod = 'biteship:' . $rate['courier_code'] . ':' . $rate['service_code'];
                } elseif (isset(self::SHIPPING_METHODS[$shippingMethod])) {
                    $shippingCost = self::SHIPPING_METHODS[$shippingMethod]['cost'];
                } else {
                    abort(422, 'Metode pengiriman tidak valid.');
                }

                $total = max(0, $subtotal + $shippingCost - $discount);
                $order = Order::create([
                    'user_id' => $user->id,
                    'order_number' => 'TMP-' . Str::upper(Str::random(16)),
                    'status' => 'pending',
                    'subtotal' => $subtotal,
                    'shipping_cost' => $shippingCost,
                    'shipping_method' => $shippingMethod,
                    'shipping_courier' => $shippingCourier,
                    'shipping_service' => $shippingService,
                    'discount' => $discount,
                    'coupon_code' => $coupon?->code,
                    'total' => $total,
                    'payment_method' => $validated['payment_method'],
                    'payment_status' => 'unpaid',
                    'recipient_name' => $address->recipient_name,
                    'phone' => $address->phone,
                    'address_line' => $address->address_line,
                    'city' => $address->city,
                    'state' => $address->state,
                    'postal_code' => $address->postal_code,
                ]);
                $order->update(['order_number' => 'ORD-' . now()->format('Ymd') . '-' . str_pad((string) $order->id, 6, '0', '0')]);

                foreach ($orderLines as $line) {
                    $product = $line['product'];
                    $variant = $line['variant'];
                    $quantity = $line['cartItem']->quantity;
                    $order->items()->create([
                        'product_id' => $product->id,
                        'product_variant_id' => $variant?->id,
                        'product_name' => $product->name,
                        'sku' => $variant?->sku ?? $product->sku,
                        'variant_name' => $variant?->displayName(),
                        'price' => $variant?->price ?? $product->price,
                        'quantity' => $quantity,
                        'line_total' => $line['lineTotal'],
                    ]);
                    if ($variant) $variant->decrement('stock', $quantity); else $product->decrement('stock', $quantity);
                }

                if ($coupon) $coupon->increment('used_count');
                $cart->items()->delete();
                return $order;
            });
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            throw $e;
        }

        if ($order->payment_method === 'midtrans') {
            return redirect()->route('payments.show', $order)->with('status', 'Pesanan dibuat. Lanjutkan pembayaran melalui Midtrans.');
        }

        return redirect()->route('orders.show', $order)->with('status', 'Pesanan berhasil dibuat.');
    }
}
