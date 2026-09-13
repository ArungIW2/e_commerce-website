<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\Models\Order;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MidtransPaymentGateway implements PaymentGateway
{
    public function createPayment(Order $order): array
    {
        $serverKey = config('services.midtrans.server_key');
        if (!$serverKey) throw new RuntimeException('MIDTRANS_SERVER_KEY belum dikonfigurasi.');
        $baseUrl = config('services.midtrans.is_production') ? 'https://app.midtrans.com' : 'https://app.sandbox.midtrans.com';
        $response = Http::withBasicAuth($serverKey, '')->acceptJson()->post($baseUrl . '/snap/v1/transactions', [
            'transaction_details' => ['order_id' => $order->order_number, 'gross_amount' => (int) round($order->total)],
            'customer_details' => ['first_name' => $order->recipient_name, 'phone' => $order->phone],
            'item_details' => $order->items->map(fn ($item) => [
                'id' => (string) ($item->product_variant_id ?: $item->product_id), 'price' => (int) round($item->price),
                'quantity' => $item->quantity, 'name' => mb_substr($item->product_name . ($item->variant_name ? ' - ' . $item->variant_name : ''), 0, 50),
            ])->values()->all(),
        ]);
        $response->throw();
        $data = $response->json();
        return ['token' => $data['token'], 'redirect_url' => $data['redirect_url'] ?? null];
    }

    public function handleNotification(array $payload): void
    {
        $serverKey = config('services.midtrans.server_key');
        $expected = hash('sha512', ($payload['order_id'] ?? '') . ($payload['status_code'] ?? '') . ($payload['gross_amount'] ?? '') . $serverKey);
        if (!$serverKey || !hash_equals($expected, (string) ($payload['signature_key'] ?? ''))) abort(403, 'Invalid Midtrans signature.');
        $order = Order::where('order_number', $payload['order_id'] ?? '')->firstOrFail();
        $transaction = $payload['transaction_status'] ?? '';
        $fraud = $payload['fraud_status'] ?? null;
        if (in_array($transaction, ['capture', 'settlement'], true) && ($fraud === null || $fraud === 'accept')) {
            $order->update(['payment_status' => 'paid', 'status' => $order->status === 'pending' ? 'processing' : $order->status]);
        } elseif ($transaction === 'pending') {
            $order->update(['payment_status' => 'unpaid']);
        } elseif (in_array($transaction, ['deny', 'cancel', 'expire'], true)) {
            $order->update(['payment_status' => 'failed']);
        } elseif (in_array($transaction, ['refund', 'partial_refund'], true)) {
            $order->update(['payment_status' => 'refunded']);
        }
    }
}
