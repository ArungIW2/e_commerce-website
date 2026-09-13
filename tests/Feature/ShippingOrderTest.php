<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ShippingOrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.biteship.api_key', 'biteship_test.example');
        config()->set('services.biteship.base_url', 'https://api.biteship.com');
        config()->set('services.biteship.origin_postal_code', '17530');
        config()->set('services.biteship.origin_contact_name', 'Store Admin');
        config()->set('services.biteship.origin_contact_phone', '081234567890');
        config()->set('services.biteship.origin_contact_email', 'store@example.com');
        config()->set('services.biteship.origin_address', 'Jl. Store No. 1');
        config()->set('services.biteship.shipper_organization', 'Laravel Commerce');
        config()->set('services.biteship.webhook_signature_key', 'X-Biteship-Signature');
        config()->set('services.biteship.webhook_signature_secret', 'secret-value');
    }

    private function makeOrder(array $overrides = []): Order
    {
        $user = User::create([
            'name' => 'Order User',
            'email' => 'order-' . uniqid() . '@example.com',
            'password' => 'password',
            'role' => 'customer',
        ]);

        $order = Order::create(array_merge([
            'user_id' => $user->id,
            'order_number' => 'ORD-' . uniqid(),
            'status' => 'processing',
            'subtotal' => 100000,
            'shipping_cost' => 18000,
            'shipping_method' => 'biteship:jne:reg',
            'shipping_courier' => 'jne',
            'shipping_service' => 'REG',
            'discount' => 0,
            'total' => 118000,
            'payment_method' => 'cod',
            'payment_status' => 'unpaid',
            'recipient_name' => 'John Doe',
            'phone' => '081234567890',
            'address_line' => 'Jl. Customer No. 2',
            'city' => 'Depok',
            'state' => 'Jawa Barat',
            'postal_code' => '17531',
        ], $overrides));

        $order->items()->create([
            'product_name' => 'Test Product',
            'sku' => 'TEST-001',
            'price' => 100000,
            'quantity' => 1,
            'line_total' => 100000,
        ]);

        return $order->fresh('user', 'items');
    }

    public function test_admin_can_create_biteship_order_and_store_tracking_data(): void
    {
        $order = $this->makeOrder();
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin-' . uniqid() . '@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        Http::fake([
            'https://api.biteship.com/v1/orders' => Http::response([
                'success' => true,
                'id' => 'bite-order-123',
                'status' => 'confirmed',
                'courier' => [
                    'tracking_id' => 'tracking-123',
                    'waybill_id' => 'JNE123456',
                    'link' => 'https://tracking.example.test/JNE123456',
                ],
            ], 200),
        ]);

        $response = $this->actingAs($admin)->post(route('admin.orders.shipping.create', $order));

        $response->assertRedirect();
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'biteship_order_id' => 'bite-order-123',
            'biteship_tracking_id' => 'tracking-123',
            'tracking_number' => 'JNE123456',
            'shipping_status' => 'confirmed',
        ]);
        Http::assertSent(fn ($request) =>
            $request->url() === 'https://api.biteship.com/v1/orders'
            && $request->hasHeader('Authorization', 'Bearer biteship_test.example')
            && $request['reference_id'] === $order->order_number
        );
    }

    public function test_biteship_webhook_updates_tracking_idempotently(): void
    {
        $order = $this->makeOrder(['biteship_order_id' => 'bite-order-456']);

        $payload = [
            'event' => 'order.status',
            'order_id' => 'bite-order-456',
            'status' => 'inTransit',
            'courier_waybill_id' => 'JNE999',
            'courier_link' => 'https://tracking.example.test/JNE999',
        ];

        $first = $this->withHeader('X-Biteship-Signature', 'secret-value')
            ->postJson(route('shipping.webhook'), $payload);
        $first->assertOk()->assertJson(['status' => 'ok']);

        $second = $this->withHeader('X-Biteship-Signature', 'secret-value')
            ->postJson(route('shipping.webhook'), $payload);
        $second->assertOk();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'tracking_number' => 'JNE999',
            'shipping_status' => 'inTransit',
            'shipping_tracking_url' => 'https://tracking.example.test/JNE999',
            'status' => 'shipped',
        ]);
    }

    public function test_biteship_webhook_rejects_invalid_signature(): void
    {
        $order = $this->makeOrder(['biteship_order_id' => 'bite-order-789']);

        $this->withHeader('X-Biteship-Signature', 'wrong-secret')
            ->postJson(route('shipping.webhook'), [
                'order_id' => $order->biteship_order_id,
                'status' => 'delivered',
            ])
            ->assertStatus(401);
    }
}
