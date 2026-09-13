<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CartController extends Controller
{
    public function index(Request $request): View
    {
        $items = $this->items($request);
        $subtotal = $items->sum(fn ($item) => $item['product']->price * $item['quantity']);

        return view('store.cart', compact('items', 'subtotal'));
    }

    public function add(Request $request, Product $product): RedirectResponse
    {
        abort_unless($product->is_active, 404);

        if ($product->stock < 1) {
            return back()->withErrors(['cart' => 'Produk sedang habis.']);
        }

        $quantity = max(1, min($request->integer('quantity', 1), $product->stock));

        if (Auth::check()) {
            $cart = $this->databaseCart();
            $item = $cart->items()->firstOrNew(['product_id' => $product->id]);
            $item->quantity = min(($item->quantity ?? 0) + $quantity, $product->stock);
            $item->save();
        } else {
            $cart = $request->session()->get('cart', []);
            $cart[$product->id] = min(($cart[$product->id] ?? 0) + $quantity, $product->stock);
            $request->session()->put('cart', $cart);
        }

        return redirect()->route('cart')->with('status', 'Produk ditambahkan ke keranjang.');
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $quantity = $request->integer('quantity');

        if ($quantity < 1) {
            return $this->remove($request, $product);
        }

        if (Auth::check()) {
            $item = $this->databaseCart()->items()->where('product_id', $product->id)->firstOrFail();
            $item->update(['quantity' => min($quantity, $product->stock)]);
        } else {
            $cart = $request->session()->get('cart', []);
            if (isset($cart[$product->id])) {
                $cart[$product->id] = min($quantity, $product->stock);
                $request->session()->put('cart', $cart);
            }
        }

        return back()->with('status', 'Jumlah produk diperbarui.');
    }

    public function remove(Request $request, Product $product): RedirectResponse
    {
        if (Auth::check()) {
            $this->databaseCart()->items()->where('product_id', $product->id)->delete();
        } else {
            $cart = $request->session()->get('cart', []);
            unset($cart[$product->id]);
            $request->session()->put('cart', $cart);
        }

        return back()->with('status', 'Produk dihapus dari keranjang.');
    }

    public function clear(Request $request): RedirectResponse
    {
        if (Auth::check()) {
            $this->databaseCart()->items()->delete();
        } else {
            $request->session()->forget('cart');
        }

        return back()->with('status', 'Keranjang dikosongkan.');
    }

    public function mergeSessionCart(Request $request): void
    {
        $sessionCart = $request->session()->pull('cart', []);
        if (! Auth::check() || empty($sessionCart)) {
            return;
        }

        $cart = $this->databaseCart();
        $products = Product::whereIn('id', array_keys($sessionCart))->where('is_active', true)->get()->keyBy('id');

        foreach ($sessionCart as $productId => $quantity) {
            $product = $products->get($productId);
            if (! $product || $product->stock < 1) continue;

            $item = $cart->items()->firstOrNew(['product_id' => $product->id]);
            $item->quantity = min(($item->quantity ?? 0) + (int) $quantity, $product->stock);
            $item->save();
        }
    }

    private function databaseCart(): Cart
    {
        return Cart::firstOrCreate(['user_id' => Auth::id()]);
    }

    private function items(Request $request)
    {
        if (Auth::check()) {
            return $this->databaseCart()->items()->with('product')->get()->map(fn ($item) => [
                'product' => $item->product,
                'quantity' => min($item->quantity, $item->product->stock),
            ])->filter(fn ($item) => $item['product']?->is_active && $item['product']->stock > 0)->values();
        }

        $cart = $request->session()->get('cart', []);
        $products = Product::whereIn('id', array_keys($cart))->where('is_active', true)->get()->keyBy('id');

        return collect($cart)->map(function ($quantity, $productId) use ($products) {
            $product = $products->get($productId);
            return $product && $product->stock > 0 ? [
                'product' => $product,
                'quantity' => min((int) $quantity, $product->stock),
            ] : null;
        })->filter()->values();
    }
}
