<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductAttribute;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AttributeController extends Controller
{
    public function index(): View { return view('admin.attributes.index', ['attributes' => ProductAttribute::with('values')->orderBy('name')->get()]); }
    public function store(Request $request): RedirectResponse {
        $data = $request->validate(['name' => ['required','string','max:100','unique:product_attributes,name']]);
        ProductAttribute::create($data);
        return back()->with('success', 'Attribute created successfully.');
    }
    public function update(Request $request, ProductAttribute $attribute): RedirectResponse {
        $data = $request->validate(['name' => ['required','string','max:100','unique:product_attributes,name,'.$attribute->id]]);
        $attribute->update($data);
        return back()->with('success', 'Attribute updated successfully.');
    }
    public function destroy(ProductAttribute $attribute): RedirectResponse { $attribute->delete(); return back()->with('success', 'Attribute deleted successfully.'); }
    public function storeValue(Request $request, ProductAttribute $attribute): RedirectResponse {
        $data = $request->validate(['value' => ['required','string','max:100']]);
        $attribute->values()->firstOrCreate(['value' => $data['value']]);
        return back()->with('success', 'Attribute value saved.');
    }
    public function destroyValue(ProductAttribute $attribute, int $value): RedirectResponse {
        $attribute->values()->whereKey($value)->firstOrFail()->delete();
        return back()->with('success', 'Attribute value deleted.');
    }
}
