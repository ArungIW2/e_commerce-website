<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ShippingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.biteship.api_key', 'biteship_test.example');
        config()->set('services.biteship.base_url', 'https://api.biteship.com');
        config()->set('services.biteship.origin_postal_code', '17530');
        config()->set('services.biteship.couriers', 'jne,jnt,sicepat');
    }

    public function test_shipping_rates_endpoint_returns_normalized_biteship_rates(): void
    {
        Http::fake([
            'https://api.biteship.com/v1/rates/couriers' => Http::response([
                'success' => true,
                'pricing' => [[
                    'company' => 'jne',
                    'courier_name' => 'JNE',
                    'courier_code' => 'jne',
                    'courier_service_name' => 'REG',
                    'courier_service_code' => 'reg',
                    'duration' => '2 - 3 days',
                    'price' => 18000,
                    'available_for_cash_on_delivery' => true,
                ]],
            ], 200),
        ]);

        $user = User::factory()->create();
        $product = Product::factory()->create(['price' => 100000, 'stock' => 10, 'is_active' => true]);
        $cart = Cart::create(['user_id' => $user->id]);
        $cart->items()->create(['product_id' => $product->id, 'quantity' => 1]);

        $response = $this->actingAs($user)->getJson('/shipping/rates?postal_code=17531&payment_method=cod');

        $response->assertOk()->assertJsonPath('rates.0.id', 'jne:reg')->assertJsonPath('rates.0.price', 18000);
        Http::assertSent(fn ($request) =>
            $request->url() === 'https://api.biteship.com/v1/rates/couriers'
            && $request->hasHeader('Authorization', 'Bearer biteship_test.example')
            && $request['destination_postal_code'] === 17531
            && $request['items.0.weight'] === 1000
            && $request['destination_cash_on_delivery'] === 100000
        );
    }

    public function test_shipping_rates_endpoint_hides_provider_error(): void
    {
        Http::fake([
            'https://api.biteship.com/v1/rates/couriers' => Http::response(['error' => 'provider unavailable'], 500),
        ]);

        $user = User::factory()->create();
        $product = Product::factory()->create(['price' => 100000, 'stock' => 10, 'is_active' => true]);
        $cart = Cart::create(['user_id' => $user->id]);
        $cart->items()->create(['product_id' => $product->id, 'quantity' => 1]);

        $this->actingAs($user)
            ->getJson('/shipping/rates?postal_code=17531')
            ->assertStatus(503)
            ->assertJson(['message' => 'Tarif pengiriman sedang tidak tersedia. Silakan coba lagi.']);
    }
}
