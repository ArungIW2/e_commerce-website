<div class="grid grid-2">
<div><div class="form-group"><label for="name">Name</label><input class="input" id="name" name="name" value="{{ old('name', $product->name) }}" required></div>
<div class="form-group"><label for="sku">SKU</label><input class="input" id="sku" name="sku" value="{{ old('sku', $product->sku) }}" required></div>
<div class="form-group"><label for="slug">Slug <span class="muted">(optional)</span></label><input class="input" id="slug" name="slug" value="{{ old('slug', $product->slug) }}" placeholder="product-name"></div>
<div class="form-group"><label for="category_id">Category</label><select class="select" id="category_id" name="category_id"><option value="">Uncategorized</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected(old('category_id', $product->category_id) == $category->id)>{{ $category->name }}</option>@endforeach</select></div></div>
<div><div class="form-group"><label for="price">Price (IDR)</label><input class="input" id="price" type="number" name="price" value="{{ old('price', $product->price) }}" min="0" step="0.01" required></div>
<div class="form-group"><label for="stock">Stock Quantity</label><input class="input" id="stock" type="number" name="stock" value="{{ old('stock', $product->stock ?? 0) }}" min="0" required></div>
<div class="form-group"><label for="image">Image Path <span class="muted">(optional for now)</span></label><input class="input" id="image" name="image" value="{{ old('image', $product->image) }}" placeholder="products/example.jpg"></div>
<label class="checkbox"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product->exists ? $product->is_active : true))> Active</label></div>
</div>
<div class="form-group"><label for="description">Description</label><textarea class="textarea" id="description" name="description">{{ old('description', $product->description) }}</textarea></div>
<div class="actions"><button class="btn" type="submit">{{ $product->exists ? 'Update Product' : 'Create Product' }}</button><a class="btn secondary" href="{{ route('admin.products.index') }}">Cancel</a></div>