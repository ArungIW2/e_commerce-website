<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use App\Services\MidtransPaymentGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MidtransPaymentTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        return User::create([
            'name' => 'Test Customer',
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password'),
            'role' => 'customer',
        ]);
    }

    private function makeOrder(?User $user = null, float $total = 150000): Order
    {
        $user ??= $this->makeUser();

        return Order::create([
            'user_id' => $user->id,
            'order_number' => 'ORD-' . fake()->unique()->numerify('########'),
            'status' => 'pending',
            'subtotal' => $total,
            'shipping_cost' => 0,
            'discount' => 0,
            'total' => $total,
            'payment_method' => 'midtrans',
            'payment_status' => 'unpaid',
            'recipient_name' => 'Test Customer',
            'phone' => '081234567890',
            'address_line' => 'Jl. Testing No. 1',
            'city' => 'Depok',
            'state' => 'Jawa Barat',
            'postal_code' => '16424',
        ]);
    }

    public function test_can_create_snap_payment(): void
    {
        Http::fake([
            'https://app.sandbox.midtrans.com/snap/v1/transactions' => Http::response([
                'token' => 'snap-token-123',
                'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/snap-token-123',
            ], 200),
        ]);

        $order = $this->makeOrder();
        $order->items()->create([
            'product_id' => null,
            'product_name' => 'Test Product',
            'price' => 150000,
            'quantity' => 1,
            'line_total' => 150000,
        ]);

        $payment = app(MidtransPaymentGateway::class)->createPayment($order->load('items'));

        $this->assertSame('snap-token-123', $payment['token']);
        Http::assertSent(fn ($request) =>
            $request->url() === 'https://app.sandbox.midtrans.com/snap/v1/transactions'
            && $request['transaction_details']['order_id'] === $order->order_number
            && $request['transaction_details']['gross_amount'] === 150000
        );
    }

    public function test_payment_page_creates_and_stores_token(): void
    {
        Http::fake([
            'https://app.sandbox.midtrans.com/snap/v1/transactions' => Http::response(['token' => 'stored-token'], 200),
        ]);

        $user = $this->makeUser();
        $order = $this->makeOrder($user);

        $this->actingAs($user)->get('/payments/' . $order->id)->assertOk();
        $this->assertSame('stored-token', $order->fresh()->payment_token);
    }

    public function test_payment_page_reuses_existing_token(): void
    {
        $user = $this->makeUser();
        $order = $this->makeOrder($user);
        $order->update(['payment_token' => 'existing-token']);

        Http::fake();
        $this->actingAs($user)->get('/payments/' . $order->id)->assertOk();

        Http::assertNothingSent();
    }

    public function test_payment_page_rejects_unauthorized_order(): void
    {
        $owner = $this->makeUser();
        $otherUser = $this->makeUser();
        $order = $this->makeOrder($owner);

        $this->actingAs($otherUser)->get('/payments/' . $order->id)->assertForbidden();
    }

    public function test_valid_settlement_webhook_marks_order_paid(): void
    {
        $order = $this->makeOrder();
        $payload = $this->signedPayload($order, 'settlement', 'accept');

        $this->postJson('/payments/midtrans/notification', $payload)
            ->assertOk()
            ->assertJson(['status' => 'ok']);

        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertSame('processing', $order->fresh()->status);
    }

    public function test_invalid_webhook_signature_is_rejected(): void
    {
        $order = $this->makeOrder();
        $payload = $this->signedPayload($order, 'settlement', 'accept');
        $payload['signature_key'] = 'invalid';

        $this->postJson('/payments/midtrans/notification', $payload)->assertForbidden();
        $this->assertSame('unpaid', $order->fresh()->payment_status);
    }

    public function test_webhook_statuses_are_mapped(): void
    {
        $cases = [
            ['pending', null, 'unpaid'],
            ['deny', null, 'failed'],
            ['cancel', null, 'failed'],
            ['expire', null, 'failed'],
            ['refund', null, 'refunded'],
            ['partial_refund', null, 'refunded'],
        ];

        foreach ($cases as [$transaction, $fraud, $expected]) {
            $order = $this->makeOrder();
            $payload = $this->signedPayload($order, $transaction, $fraud);
            $this->postJson('/payments/midtrans/notification', $payload)->assertOk();
            $this->assertSame($expected, $order->fresh()->payment_status, $transaction);
        }
    }

    public function test_capture_is_paid_only_when_fraud_is_accepted(): void
    {
        $accepted = $this->makeOrder();
        $this->postJson('/payments/midtrans/notification', $this->signedPayload($accepted, 'capture', 'accept'))->assertOk();
        $this->assertSame('paid', $accepted->fresh()->payment_status);

        $challenge = $this->makeOrder();
        $this->postJson('/payments/midtrans/notification', $this->signedPayload($challenge, 'capture', 'challenge'))->assertOk();
        $this->assertSame('unpaid', $challenge->fresh()->payment_status);
    }

    private function signedPayload(Order $order, string $transactionStatus, ?string $fraudStatus = null): array
    {
        $grossAmount = number_format((float) $order->total, 2, '.', '');
        $payload = [
            'order_id' => $order->order_number,
            'status_code' => '200',
            'gross_amount' => $grossAmount,
            'transaction_status' => $transactionStatus,
        ];

        if ($fraudStatus !== null) {
            $payload['fraud_status'] = $fraudStatus;
        }

        $payload['signature_key'] = hash('sha512', $payload['order_id'] . $payload['status_code'] . $payload['gross_amount'] . config('services.midtrans.server_key'));

        return $payload;
    }
}
