<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
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

    public function test_api_routes_are_served_without_web_csrf_middleware(): void
    {
        $this->postJson('/api/v1/auth/login', [
            'email' => 'missing@example.com',
            'password' => 'wrong-password',
        ])->assertStatus(422);
    }

    public function test_public_product_api_hides_internal_product_state_fields_and_raw_stock(): void
    {
        $product = Product::factory()->create([
            'is_active' => true,
            'stock' => 7,
        ]);

        $response = $this->getJson('/api/v1/products/'.$product->slug)->assertOk();
        $response->assertJsonPath('data.id', $product->id);
        $response->assertJsonPath('data.in_stock', true);
        $response->assertJsonMissingPath('data.is_active');
        $response->assertJsonMissingPath('data.stock');
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

    public function test_oldest_active_api_tokens_are_revoked_when_limit_is_reached(): void
    {
        config(['services.api.max_active_tokens' => 2]);

        $user = User::factory()->create();

        foreach (['token-1', 'token-2'] as $plainToken) {
            ApiToken::create([
                'user_id' => $user->id,
                'name' => 'test',
                'token_hash' => hash('sha256', $plainToken),
                'expires_at' => Carbon::now()->addDay(),
            ]);
        }

        $this->actingAs($user)
            ->postJson('/api/v1/auth/login', [
                'email' => $user->email,
                'password' => 'password',
            ])
            ->assertOk();

        $this->assertSame(2, $user->apiTokens()->whereNull('revoked_at')->count());
        $this->assertNotNull($user->apiTokens()->where('token_hash', hash('sha256', 'token-1'))->first()->revoked_at);
        $this->assertNull($user->apiTokens()->where('token_hash', hash('sha256', 'token-2'))->first()->revoked_at);
    }
}
