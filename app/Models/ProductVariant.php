<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProductVariant extends Model
{
    protected $fillable = ['product_id', 'sku', 'price', 'stock', 'is_active'];
    protected $casts = ['price' => 'decimal:2', 'is_active' => 'boolean', 'stock' => 'integer'];
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function attributeValues(): BelongsToMany { return $this->belongsToMany(ProductAttributeValue::class, 'product_variant_attribute_value'); }
    public function displayName(): string { return $this->attributeValues->sortBy(fn ($v) => $v->attribute?->name)->map(fn ($v) => $v->attribute?->name.': '.$v->value)->implode(' / '); }
}
