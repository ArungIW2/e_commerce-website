<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\Category;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_endpoints_are_public(): void
    {
        Category::create(['name' => 'Shoes', 'slug' => 'shoes', 'is_active' => true]);
        $this->getJson('/api/v1/categories')->assertOk()->assertJsonPath('success', true);
        $this->getJson('/api/v1/products')->assertOk()->assertJsonPath('success', true);
    }

    public function test_register_returns_bearer_token_and_me_requires_it(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'API User', 'email' => 'api@example.com', 'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertCreated()->assertJsonPath('success', true);
        $token = $response->json('data.token');
        $this->assertNotEmpty($token);
        $this->getJson('/api/v1/auth/me')->assertUnauthorized();
        $this->withHeader('Authorization', 'Bearer '.$token)->getJson('/api/v1/auth/me')->assertOk()->assertJsonPath('data.email', 'api@example.com');
    }

    public function test_order_access_is_scoped_to_authenticated_user(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $plain = Str::random(80);
        ApiToken::create(['user_id' => $owner->id, 'name' => 'test', 'token_hash' => hash('sha256', $plain)]);
        $order = Order::create(['user_id' => $other->id, 'order_number' => 'ORD-API-001', 'status' => 'pending', 'subtotal' => 10000, 'shipping_cost' => 0, 'shipping_method' => 'pickup', 'discount' => 0, 'total' => 10000, 'payment_method' => 'cod', 'payment_status' => 'unpaid', 'recipient_name' => 'Other', 'phone' => '0812', 'address_line' => 'Test', 'city' => 'Depok', 'state' => 'Jawa Barat', 'postal_code' => '16411']);
        $this->withHeader('Authorization', 'Bearer '.$plain)->getJson('/api/v1/orders/'.$order->id)->assertNotFound();
    }
}
