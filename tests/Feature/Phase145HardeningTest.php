<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class Phase145HardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_expired_api_token_is_rejected(): void
    {
        $user = User::factory()->create();
        $token = ApiToken::create([
            'user_id' => $user->id,
            'name' => 'test',
            'token_hash' => hash('sha256', 'expired-token'),
            'expires_at' => Carbon::now()->subMinute(),
        ]);

        $this->withHeader('Authorization', 'Bearer expired-token')
            ->getJson('/api/v1/auth/me')
            ->assertUnauthorized();

        $this->assertNotNull($token->fresh()->revoked_at);
    }

    public function test_api_auth_route_is_not_blocked_by_web_csrf(): void
    {
        $this->postJson('/api/v1/auth/login', [
            'email' => 'missing@example.com',
            'password' => 'wrong-password',
        ])->assertStatus(422);
    }

    public function test_public_product_api_uses_a_safe_resource(): void
    {
        $category = Category::create(['name' => 'Test', 'slug' => 'test', 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Test Product',
            'slug' => 'test-product-'.Str::random(6),
            'sku' => 'TEST-'.Str::upper(Str::random(6)),
            'price' => 10000,
            'stock' => 5,
            'is_active' => true,
        ]);

        $this->getJson('/api/v1/products/'.$product->slug)
            ->assertOk()
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonMissingPath('data.is_active');
    }

    public function test_api_token_relationship_exists(): void
    {
        $user = User::factory()->create();
        ApiToken::create([
            'user_id' => $user->id,
            'name' => 'test',
            'token_hash' => hash('sha256', 'token'),
            'expires_at' => Carbon::now()->addDay(),
        ]);

        $this->assertCount(1, $user->apiTokens()->get());
    }
}
