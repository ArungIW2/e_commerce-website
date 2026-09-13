<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Product;
use App\Models\WishlistItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class WishlistController extends Controller
{
    public function index(): View
    {
        $items = Auth::user()->wishlistItems()
            ->with(['product.category', 'product.images', 'product.variants'])
            ->latest()
            ->get()
            ->filter(fn ($item) => $item->product?->is_active)
            ->values();

        return view('store.wishlist', compact('items'));
    }

    public function toggle(Product $product): RedirectResponse
    {
        abort_unless($product->is_active, 404);

        $item = WishlistItem::where('user_id', Auth::id())
            ->where('product_id', $product->id)
            ->first();

        if ($item) {
            $item->delete();
            return back()->with('status', 'Produk dihapus dari wishlist.');
        }

        WishlistItem::create([
            'user_id' => Auth::id(),
            'product_id' => $product->id,
        ]);

        return back()->with('status', 'Produk ditambahkan ke wishlist.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        WishlistItem::where('user_id', Auth::id())
            ->where('product_id', $product->id)
            ->delete();

        return back()->with('status', 'Produk dihapus dari wishlist.');
    }

    public function addToCart(Request $request, Product $product): RedirectResponse
    {
        abort_unless($product->is_active, 404);

        if ($product->variants()->where('is_active', true)->exists()) {
            return redirect()->route('products.show', $product)
                ->with('status', 'Pilih varian produk terlebih dahulu sebelum menambahkannya ke cart.');
        }

        if ($product->stock < 1) {
            return back()->withErrors(['cart' => 'Produk sedang habis.']);
        }

        $cart = Cart::firstOrCreate(['user_id' => Auth::id()]);
        $item = $cart->items()->firstOrNew([
            'product_id' => $product->id,
            'product_variant_id' => null,
        ]);
        $item->quantity = min(($item->quantity ?? 0) + 1, $product->stock);
        $item->save();

        return redirect()->route('cart')->with('status', 'Produk ditambahkan ke keranjang.');
    }
}
