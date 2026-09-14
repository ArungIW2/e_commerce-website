<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'status' => $this->status,
            'subtotal' => $this->subtotal,
            'shipping_cost' => $this->shipping_cost,
            'shipping_method' => $this->shipping_method,
            'shipping_courier' => $this->shipping_courier,
            'shipping_service' => $this->shipping_service,
            'discount' => $this->discount,
            'coupon_code' => $this->coupon_code,
            'total' => $this->total,
            'payment_method' => $this->payment_method,
            'payment_status' => $this->payment_status,
            'shipping_status' => $this->shipping_status,
            'tracking_number' => $this->tracking_number,
            'shipping_tracking_url' => $this->shipping_tracking_url,
            'shipped_at' => $this->shipped_at,
            'recipient_name' => $this->recipient_name,
            'phone' => $this->phone,
            'address_line' => $this->address_line,
            'city' => $this->city,
            'state' => $this->state,
            'postal_code' => $this->postal_code,
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_variant_id' => $item->product_variant_id,
                'product_name' => $item->product_name,
                'sku' => $item->sku,
                'variant_name' => $item->variant_name,
                'price' => $item->price,
                'quantity' => $item->quantity,
                'line_total' => $item->line_total,
            ])->values()),
        ];
    }
}
