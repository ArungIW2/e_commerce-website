<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'sku' => $this->sku,
            'description' => $this->description,
            'price' => $this->price,
            'in_stock' => $this->stock > 0,
            'image' => $this->image,
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ]),
            'variants' => $this->whenLoaded('variants', fn () => $this->variants
                ->where('is_active', true)
                ->values()
                ->map(fn ($variant) => [
                    'id' => $variant->id,
                    'sku' => $variant->sku,
                    'price' => $variant->price,
                    'in_stock' => $variant->stock > 0,
                    'attributes' => $variant->relationLoaded('attributeValues')
                        ? $variant->attributeValues->map(fn ($value) => [
                            'name' => $value->attribute?->name,
                            'value' => $value->value,
                        ])->values()
                        : [],
                ])),
            'images' => $this->whenLoaded('images', fn () => $this->images->map(fn ($image) => [
                'id' => $image->id,
                'path' => $image->path,
                'is_primary' => (bool) $image->is_primary,
            ])->values()),
        ];
    }
}
