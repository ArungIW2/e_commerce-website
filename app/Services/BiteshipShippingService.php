<?php

namespace App\Services;

use App\Models\CartItem;
use App\Models\Order;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class BiteshipShippingService
{
    public function rates(string $destinationPostalCode, iterable $items, ?int $codAmount = null): array
    {
        $response = $this->client()->post($this->url('/v1/rates/couriers'), [
            'origin_postal_code' => $this->originPostalCode(),
            'destination_postal_code' => (int) $destinationPostalCode,
            'couriers' => config('services.biteship.couriers', 'jne,jnt,sicepat,anteraja'),
            'items' => $this->mapCartItems($items),
            ...($codAmount !== null && $codAmount > 0 ? [
                'destination_cash_on_delivery' => $codAmount,
                'destination_cash_on_delivery_type' => '7_days',
            ] : []),
        ]);

        $this->ensureSuccessful($response, 'Biteship gagal mengambil tarif pengiriman.');

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

    public function createOrder(Order $order): array
    {
        if ($order->biteship_order_id) {
            return $this->retrieveOrder($order->biteship_order_id);
        }

        if (!str_starts_with($order->shipping_method, 'biteship:')) {
            throw new RuntimeException('Pesanan ini tidak menggunakan pengiriman Biteship.');
        }

        [$courierCompany, $courierType] = array_pad(explode(':', $order->shipping_method, 3), 2, null);
        if (!$courierCompany || !$courierType) {
            throw new RuntimeException('Metode Biteship pada pesanan tidak valid.');
        }

        $order->loadMissing('items');
        $payload = [
            'shipper_contact_name' => config('services.biteship.origin_contact_name'),
            'shipper_contact_phone' => config('services.biteship.origin_contact_phone'),
            'shipper_contact_email' => config('services.biteship.origin_contact_email'),
            'shipper_organization' => config('services.biteship.shipper_organization'),
            'origin_contact_name' => config('services.biteship.origin_contact_name'),
            'origin_contact_phone' => config('services.biteship.origin_contact_phone'),
            'origin_contact_email' => config('services.biteship.origin_contact_email'),
            'origin_address' => config('services.biteship.origin_address'),
            'origin_postal_code' => $this->originPostalCode(),
            'destination_contact_name' => $order->recipient_name,
            'destination_contact_phone' => $order->phone,
            'destination_contact_email' => $order->user?->email,
            'destination_address' => $order->address_line . ', ' . $order->city . ', ' . $order->state,
            'destination_postal_code' => (int) $order->postal_code,
            'courier_company' => $courierCompany,
            'courier_type' => $courierType,
            'delivery_type' => 'now',
            'reference_id' => $order->order_number,
            'metadata' => ['order_id' => $order->id, 'order_number' => $order->order_number],
            'items' => $this->mapOrderItems($order->items),
        ];

        if ($order->payment_method === 'cod') {
            $payload['destination_cash_on_delivery'] = (int) round($order->total);
            $payload['destination_cash_on_delivery_type'] = '7_days';
        }

        $response = $this->client()->post($this->url('/v1/orders'), $payload);
        $this->ensureSuccessful($response, 'Biteship gagal membuat pengiriman.');

        return $response->json();
    }

    public function retrieveOrder(string $biteshipOrderId): array
    {
        $response = $this->client()->get($this->url('/v1/orders/' . urlencode($biteshipOrderId)));
        $this->ensureSuccessful($response, 'Biteship gagal mengambil detail pengiriman.');

        return $response->json();
    }

    public function retrieveTracking(string $trackingId): array
    {
        $response = $this->client()->get($this->url('/v1/trackings/' . urlencode($trackingId)));
        $this->ensureSuccessful($response, 'Biteship gagal mengambil tracking.');

        return $response->json();
    }

    public function cancelOrder(string $biteshipOrderId): array
    {
        $response = $this->client()->post($this->url('/v1/orders/' . urlencode($biteshipOrderId) . '/cancel'));
        $this->ensureSuccessful($response, 'Biteship gagal membatalkan pengiriman.');

        return $response->json();
    }

    public function syncOrderResponse(Order $order, array $response): Order
    {
        $courier = $response['courier'] ?? [];
        $status = $response['status'] ?? null;
        $order->update(array_filter([
            'biteship_order_id' => $response['id'] ?? $order->biteship_order_id,
            'biteship_tracking_id' => $courier['tracking_id'] ?? $order->biteship_tracking_id,
            'tracking_number' => $courier['waybill_id'] ?? $order->tracking_number,
            'shipping_status' => $status,
            'shipping_tracking_url' => $courier['link'] ?? $order->shipping_tracking_url,
            'shipped_at' => in_array($status, ['picked', 'in_transit', 'dropping_off', 'delivered'], true)
                ? ($order->shipped_at ?? now()) : $order->shipped_at,
        ], static fn ($value) => $value !== null));

        return $order->fresh();
    }

    private function client()
    {
        $apiKey = config('services.biteship.api_key');
        if (!$apiKey) {
            throw new RuntimeException('BITESHIP_API_KEY belum dikonfigurasi.');
        }

        return Http::withToken($apiKey)
            ->acceptJson()
            ->timeout((int) config('services.biteship.timeout', 10));
    }

    private function originPostalCode(): int
    {
        $postalCode = config('services.biteship.origin_postal_code');
        if (!$postalCode) {
            throw new RuntimeException('BITESHIP_ORIGIN_POSTAL_CODE belum dikonfigurasi.');
        }

        return (int) $postalCode;
    }

    private function url(string $path): string
    {
        return rtrim(config('services.biteship.base_url', 'https://api.biteship.com'), '/') . $path;
    }

    private function ensureSuccessful($response, string $message): void
    {
        if ($response->failed() || $response->json('success') === false) {
            throw new RuntimeException($message);
        }
    }

    private function mapCartItems(iterable $items): array
    {
        return collect($items)->map(function (CartItem $item): array {
            $product = $item->product;
            $variant = $item->variant;
            $price = (float) ($variant?->price ?? $product->price);

            return [
                'name' => $product->name,
                'description' => $variant?->displayName() ?: $product->name,
                'value' => (int) round($price),
                'quantity' => (int) $item->quantity,
                'weight' => (int) config('services.biteship.default_item_weight_grams', 1000),
                'sku' => $variant?->sku ?? $product->sku,
            ];
        })->values()->all();
    }

    private function mapOrderItems(iterable $items): array
    {
        return collect($items)->map(fn ($item): array => [
            'name' => $item->product_name,
            'description' => $item->variant_name ?: $item->product_name,
            'sku' => $item->sku,
            'value' => (int) round((float) $item->price),
            'quantity' => (int) $item->quantity,
            'weight' => (int) config('services.biteship.default_item_weight_grams', 1000),
        ])->values()->all();
    }
}
