<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    protected $fillable = ['category_id','name','slug','sku','description','price','stock','image','is_active'];
    protected $casts = ['price' => 'decimal:2', 'is_active' => 'boolean'];
    public function category(): BelongsTo { return $this->belongsTo(Category::class); }
}
