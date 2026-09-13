<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductImageController extends Controller
{
    public function store(Request $request, Product $product): RedirectResponse
    {
        $data = $request->validate([
            'image' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
            'alt_text' => ['nullable', 'string', 'max:255'],
        ]);

        $path = $request->file('image')->store('products/images', 'public');
        $isPrimary = $product->images()->count() === 0;

        $image = $product->images()->create([
            'path' => $path,
            'alt_text' => $data['alt_text'] ?? $product->name,
            'sort_order' => (int) ($product->images()->max('sort_order') ?? -1) + 1,
            'is_primary' => $isPrimary,
        ]);

        if ($isPrimary) {
            $this->syncLegacyImage($product, $image);
        }

        return back()->with('success', 'Product image uploaded successfully.');
    }

    public function primary(Product $product, ProductImage $image): RedirectResponse
    {
        abort_unless($image->product_id === $product->id, 404);
        $product->images()->update(['is_primary' => false]);
        $image->update(['is_primary' => true]);
        $this->syncLegacyImage($product, $image);

        return back()->with('success', 'Primary image updated.');
    }

    public function destroy(Product $product, ProductImage $image): RedirectResponse
    {
        abort_unless($image->product_id === $product->id, 404);
        $wasPrimary = $image->is_primary;
        Storage::disk('public')->delete($image->path);
        $image->delete();

        if ($wasPrimary) {
            $next = $product->images()->orderBy('sort_order')->first();
            $product->images()->update(['is_primary' => false]);
            if ($next) {
                $next->update(['is_primary' => true]);
                $this->syncLegacyImage($product, $next);
            } else {
                $product->update(['image' => null]);
            }
        }

        return back()->with('success', 'Product image deleted.');
    }

    private function syncLegacyImage(Product $product, ProductImage $image): void
    {
        $product->update(['image' => $image->url()]);
    }
}
