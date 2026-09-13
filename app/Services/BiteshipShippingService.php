<?php

namespace App\Services;

use App\Models\CartItem;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class BiteshipShippingService
{
    public function rates(string $destinationPostalCode, iterable $items, ?int $codAmount = null): array
    {
        $apiKey = config('services.biteship.api_key');
        if (!$apiKey) {
            throw new RuntimeException('BITESHIP_API_KEY belum dikonfigurasi.');
        }

        $payload = [
            'origin_postal_code' => (int) config('services.biteship.origin_postal_code'),
            'destination_postal_code' => (int) $destinationPostalCode,
            'couriers' => config('services.biteship.couriers', 'jne,jnt,sicepat,anteraja'),
            'items' => collect($items)->map(function (CartItem $item): array {
                $product = $item->product;
                $variant = $item->variant;
                $price = (float) ($variant?->price ?? $product->price);

                return [
                    'name' => $product->name,
                    'description' => $variant?->displayName() ?: $product->name,
                    'value' => (int) round($price),
                    'quantity' => (int) $item->quantity,
                    'weight' => (int) config('services.biteship.default_item_weight_grams', 1000),
                ];
            })->values()->all(),
        ];

        if ($codAmount !== null && $codAmount > 0) {
            $payload['destination_cash_on_delivery'] = $codAmount;
            $payload['destination_cash_on_delivery_type'] = '7_days';
        }

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->timeout((int) config('services.biteship.timeout', 10))
            ->post(rtrim(config('services.biteship.base_url'), '/') . '/v1/rates/couriers', $payload);

        if ($response->failed()) {
            throw new RuntimeException('Biteship gagal mengambil tarif pengiriman.');
        }

        return collect($response->json('pricing', []))
            ->map(fn (array $rate): array => [
                'id' => ($rate['courier_code'] ?? $rate['company'] ?? 'courier') . ':' . ($rate['courier_service_code'] ?? $rate['type'] ?? 'service'),
                'courier_code' => $rate['courier_code'] ?? $rate['company'] ?? null,
                'courier_name' => $rate['courier_name'] ?? $rate['company'] ?? 'Courier',
                'service_code' => $rate['courier_service_code'] ?? $rate['type'] ?? null,
                'service_name' => $rate['courier_service_name'] ?? $rate['description'] ?? 'Service',
                'duration' => $rate['duration'] ?? null,
                'price' => (int) round($rate['price'] ?? 0),
                'available_for_cod' => (bool) ($rate['available_for_cash_on_delivery'] ?? false),
            ])->filter(fn (array $rate) => $rate['courier_code'] && $rate['service_code'])->values()->all();
    }
}
