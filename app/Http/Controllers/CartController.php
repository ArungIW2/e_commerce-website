<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CartController extends Controller
{
    public function index(Request $request): View {
        $items = $this->items($request);
        $subtotal = $items->sum(fn ($item) => $item['unit_price'] * $item['quantity']);
        return view('store.cart', compact('items', 'subtotal'));
    }
    public function add(Request $request, Product $product): RedirectResponse {
        abort_unless($product->is_active, 404);
        $variant = null;
        if ($product->variants()->where('is_active', true)->exists()) {
            $variantId = $request->integer('variant_id');
            $variant = $product->variants()->whereKey($variantId)->where('is_active', true)->with('attributeValues.attribute')->first();
            if (!$variant) return back()->withErrors(['variant_id' => 'Pilih varian produk terlebih dahulu.']);
        }
        $stock = $variant?->stock ?? $product->stock;
        if ($stock < 1) return back()->withErrors(['cart' => 'Produk/varian sedang habis.']);
        $quantity = max(1, min($request->integer('quantity', 1), $stock));
        if (Auth::check()) {
            $cart = $this->databaseCart();
            $item = $cart->items()->firstOrNew(['product_id' => $product->id, 'product_variant_id' => $variant?->id]);
            $item->quantity = min(($item->quantity ?? 0) + $quantity, $stock); $item->save();
        } else {
            $cart = $request->session()->get('cart', []);
            $key = $product->id.':'.($variant?->id ?? 0);
            $cart[$key] = min(($cart[$key] ?? 0) + $quantity, $stock); $request->session()->put('cart', $cart);
        }
        return redirect()->route('cart')->with('status', 'Produk ditambahkan ke keranjang.');
    }
    public function update(Request $request, Product $product): RedirectResponse {
        $quantity = $request->integer('quantity'); $variantId = $request->integer('variant_id') ?: null;
        $variant = $variantId ? $product->variants()->findOrFail($variantId) : null; $stock = $variant?->stock ?? $product->stock;
        if ($quantity < 1) return $this->remove($request, $product);
        if (Auth::check()) $this->databaseCart()->items()->where('product_id',$product->id)->where('product_variant_id',$variantId)->firstOrFail()->update(['quantity'=>min($quantity,$stock)]);
        else { $cart=$request->session()->get('cart',[]); $key=$product->id.':'.($variantId??0); if(isset($cart[$key])){$cart[$key]=min($quantity,$stock);$request->session()->put('cart',$cart);} }
        return back()->with('status', 'Jumlah produk diperbarui.');
    }
    public function remove(Request $request, Product $product): RedirectResponse {
        $variantId = $request->integer('variant_id') ?: null;
        if (Auth::check()) $this->databaseCart()->items()->where('product_id',$product->id)->where('product_variant_id',$variantId)->delete();
        else { $cart=$request->session()->get('cart',[]); unset($cart[$product->id.':'.($variantId??0)]); $request->session()->put('cart',$cart); }
        return back()->with('status', 'Produk dihapus dari keranjang.');
    }
    public function clear(Request $request): RedirectResponse { if(Auth::check())$this->databaseCart()->items()->delete();else$request->session()->forget('cart'); return back()->with('status','Keranjang dikosongkan.'); }
    public function mergeSessionCart(Request $request): void {
        $sessionCart=$request->session()->pull('cart',[]); if(!Auth::check()||empty($sessionCart))return; $cart=$this->databaseCart();
        foreach($sessionCart as $key=>$quantity){ [$productId,$variantId]=array_pad(explode(':',(string)$key,2),2,0); $product=Product::whereKey($productId)->where('is_active',true)->first(); if(!$product)continue; $variant=(int)$variantId ? $product->variants()->whereKey((int)$variantId)->where('is_active',true)->first() : null; $stock=$variant?->stock??$product->stock; if($stock<1)continue; $item=$cart->items()->firstOrNew(['product_id'=>$product->id,'product_variant_id'=>$variant?->id]); $item->quantity=min(($item->quantity??0)+(int)$quantity,$stock);$item->save(); }
    }
    private function databaseCart(): Cart { return Cart::firstOrCreate(['user_id'=>Auth::id()]); }
    private function items(Request $request) {
        if(Auth::check()) return $this->databaseCart()->items()->with('product','variant.attributeValues.attribute')->get()->map(fn($item)=>$this->formatItem($item))->filter()->values();
        $cart=$request->session()->get('cart',[]); $out=[]; foreach($cart as $key=>$quantity){[$productId,$variantId]=array_pad(explode(':',(string)$key,2),2,0);$product=Product::whereKey($productId)->where('is_active',true)->first();if(!$product)continue;$variant=(int)$variantId?$product->variants()->whereKey((int)$variantId)->where('is_active',true)->with('attributeValues.attribute')->first():null;$stock=$variant?->stock??$product->stock;if($stock>0)$out[]=['product'=>$product,'variant'=>$variant,'quantity'=>min((int)$quantity,$stock),'unit_price'=>(float)($variant?->price??$product->price)];}return collect($out);
    }
    private function formatItem($item): ?array { $stock=$item->variant?->stock??$item->product?->stock; if(!$item->product?->is_active||$stock<1)return null; return ['product'=>$item->product,'variant'=>$item->variant,'quantity'=>min($item->quantity,$stock),'unit_price'=>(float)($item->variant?->price??$item->product->price)]; }
}
