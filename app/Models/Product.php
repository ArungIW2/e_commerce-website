<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = ['category_id','name','slug','sku','description','price','stock','image','is_active'];
    protected $casts = ['price' => 'decimal:2', 'is_active' => 'boolean'];
    public function category(): BelongsTo { return $this->belongsTo(Category::class); }
    public function cartItems(): HasMany { return $this->hasMany(CartItem::class); }
    public function variants(): HasMany { return $this->hasMany(ProductVariant::class); }
    public function hasVariants(): bool { return $this->variants()->where('is_active', true)->exists(); }
}
