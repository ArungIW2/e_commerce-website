<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Coupon extends Model
{
    protected $fillable = [
        'code', 'type', 'value', 'minimum_order', 'maximum_discount',
        'usage_limit', 'used_count', 'expires_at', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'minimum_order' => 'decimal:2',
            'maximum_discount' => 'decimal:2',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function isValidFor(float $subtotal): bool
    {
        return $this->is_active
            && $subtotal >= (float) $this->minimum_order
            && (! $this->expires_at || $this->expires_at->isFuture())
            && (! $this->usage_limit || $this->used_count < $this->usage_limit);
    }

    public function calculateDiscount(float $subtotal): float
    {
        if (! $this->isValidFor($subtotal)) return 0;

        $discount = $this->type === 'percentage'
            ? $subtotal * ((float) $this->value / 100)
            : (float) $this->value;

        if ($this->maximum_discount !== null) {
            $discount = min($discount, (float) $this->maximum_discount);
        }

        return min(max($discount, 0), $subtotal);
    }
}
