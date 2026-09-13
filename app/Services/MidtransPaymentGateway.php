<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MidtransPaymentGateway implements PaymentGateway
{
    public function createPayment(Order $order): array
    {
        $serverKey = config('services.midtrans.server_key');
        if (!$serverKey) {
            throw new RuntimeException('MIDTRANS_SERVER_KEY belum dikonfigurasi.');
        }

        $baseUrl = config('services.midtrans.is_production')
            ? 'https://app.midtrans.com'
            : 'https://app.sandbox.midtrans.com';

        $response = Http::withBasicAuth($serverKey, '')
            ->acceptJson()
            ->timeout(15)
            ->post($baseUrl . '/snap/v1/transactions', [
                'transaction_details' => [
                    'order_id' => $order->order_number,
                    'gross_amount' => (int) round($order->total),
                ],
                'customer_details' => [
                    'first_name' => $order->recipient_name,
                    'phone' => $order->phone,
                ],
                'item_details' => $order->items->map(fn ($item) => [
                    'id' => (string) ($item->product_variant_id ?: $item->product_id ?: $item->id),
                    'price' => (int) round($item->price),
                    'quantity' => $item->quantity,
                    'name' => mb_substr(
                        $item->product_name . ($item->variant_name ? ' - ' . $item->variant_name : ''),
                        0,
                        50
                    ),
                ])->values()->all(),
            ]);

        $response->throw();
        $data = $response->json();

        if (empty($data['token'])) {
            throw new RuntimeException('Midtrans tidak mengembalikan payment token.');
        }

        return [
            'token' => $data['token'],
            'redirect_url' => $data['redirect_url'] ?? null,
        ];
    }

    public function handleNotification(array $payload): void
    {
        $serverKey = config('services.midtrans.server_key');
        $signature = (string) ($payload['signature_key'] ?? '');
        $orderNumber = (string) ($payload['order_id'] ?? '');
        $statusCode = (string) ($payload['status_code'] ?? '');
        $grossAmount = (string) ($payload['gross_amount'] ?? '');

        if (!$serverKey || !$signature || !$orderNumber || !$statusCode || !$grossAmount) {
            abort(403, 'Invalid Midtrans notification.');
        }

        $expected = hash('sha512', $orderNumber . $statusCode . $grossAmount . $serverKey);
        if (!hash_equals($expected, $signature)) {
            abort(403, 'Invalid Midtrans signature.');
        }

        $order = Order::where('order_number', $orderNumber)->firstOrFail();
        $expectedGrossAmount = number_format((float) $order->total, 2, '.', '');
        if ((float) $grossAmount !== (float) $expectedGrossAmount) {
            abort(422, 'Midtrans gross amount does not match order total.');
        }

        $transaction = (string) ($payload['transaction_status'] ?? '');
        $fraud = $payload['fraud_status'] ?? null;

        DB::transaction(function () use ($order, $transaction, $fraud): void {
            $order->refresh();

            if ($order->status === 'cancelled' || $order->payment_status === 'refunded') {
                return;
            }

            if (in_array($transaction, ['capture', 'settlement'], true)
                && ($fraud === null || $fraud === 'accept')) {
                $order->update([
                    'payment_status' => 'paid',
                    'status' => $order->status === 'pending' ? 'processing' : $order->status,
                ]);
                return;
            }

            if ($transaction === 'pending' && $order->payment_status === 'unpaid') {
                $order->update(['payment_status' => 'unpaid']);
                return;
            }

            if (in_array($transaction, ['deny', 'cancel', 'expire'], true)
                && $order->payment_status === 'unpaid') {
                $order->update(['payment_status' => 'failed']);
                return;
            }

            if (in_array($transaction, ['refund', 'partial_refund'], true)
                && $order->payment_status === 'paid') {
                $order->update(['payment_status' => 'refunded']);
            }
        });
    }
}
