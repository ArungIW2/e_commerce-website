<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Services\BiteshipShippingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class ShippingController extends Controller
{
    public function rates(Request $request, BiteshipShippingService $shipping): JsonResponse
    {
        $validated = $request->validate([
            'postal_code' => ['required', 'digits:5'],
            'payment_method' => ['nullable', 'in:midtrans,cod,manual'],
        ]);

        $cart = Cart::where('user_id', $request->user()->id)
            ->with('items.product', 'items.variant.attributeValues.attribute')
            ->first();

        if (!$cart || $cart->items->isEmpty()) {
            return response()->json(['message' => 'Keranjang kosong.'], 422);
        }

        try {
            $rates = $shipping->rates(
                $validated['postal_code'],
                $cart->items,
                $validated['payment_method'] === 'cod' ? (int) round($cart->items->sum(fn ($item) => (float) ($item->variant?->price ?? $item->product->price) * $item->quantity)) : null,
            );
        } catch (Throwable $e) {
            report($e);
            return response()->json(['message' => 'Tarif pengiriman sedang tidak tersedia. Silakan coba lagi.'], 503);
        }

        return response()->json(['rates' => $rates]);
    }
}
