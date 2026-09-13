<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductAttributeValue;
use App\Models\ProductVariant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VariantController extends Controller
{
    public function store(Request $request, Product $product): RedirectResponse {
        $data = $request->validate([
            'sku' => ['required','string','max:255','unique:product_variants,sku'],
            'price' => ['nullable','numeric','min:0'], 'stock' => ['required','integer','min:0'], 'is_active' => ['nullable','boolean'],
            'attribute_value_ids' => ['nullable','array'], 'attribute_value_ids.*' => ['integer','exists:product_attribute_values,id'],
        ]);
        $variant = DB::transaction(function () use ($product, $data) {
            $variant = $product->variants()->create(['sku'=>$data['sku'],'price'=>$data['price'] ?? null,'stock'=>$data['stock'],'is_active'=>request()->boolean('is_active')]);
            $ids = $data['attribute_value_ids'] ?? [];
            if ($ids) $variant->attributeValues()->sync(ProductAttributeValue::whereIn('id',$ids)->pluck('id'));
            return $variant;
        });
        return back()->with('success', 'Variant '.$variant->sku.' created successfully.');
    }
    public function update(Request $request, Product $product, ProductVariant $variant): RedirectResponse {
        abort_unless($variant->product_id === $product->id, 404);
        $data = $request->validate([
            'sku' => ['required','string','max:255','unique:product_variants,sku,'.$variant->id],
            'price' => ['nullable','numeric','min:0'], 'stock' => ['required','integer','min:0'], 'is_active' => ['nullable','boolean'],
            'attribute_value_ids' => ['nullable','array'], 'attribute_value_ids.*' => ['integer','exists:product_attribute_values,id'],
        ]);
        DB::transaction(function () use ($variant, $data) {
            $variant->update(['sku'=>$data['sku'],'price'=>$data['price'] ?? null,'stock'=>$data['stock'],'is_active'=>request()->boolean('is_active')]);
            $variant->attributeValues()->sync($data['attribute_value_ids'] ?? []);
        });
        return back()->with('success', 'Variant updated successfully.');
    }
    public function destroy(Product $product, ProductVariant $variant): RedirectResponse { abort_unless($variant->product_id === $product->id,404); $variant->delete(); return back()->with('success','Variant deleted successfully.'); }
}
