<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ApiTokenCleanupTest extends TestCase
{
    use RefreshDatabase;

    public function test_cleanup_removes_old_revoked_and_expired_tokens_but_keeps_recent_tokens(): void
    {
        $user = User::factory()->create();

        $oldRevoked = ApiToken::create(['user_id' => $user->id, 'name' => 'old-revoked', 'token_hash' => hash('sha256', 'old-revoked'), 'revoked_at' => Carbon::now()->subDays(8)]);
        $oldExpired = ApiToken::create(['user_id' => $user->id, 'name' => 'old-expired', 'token_hash' => hash('sha256', 'old-expired'), 'expires_at' => Carbon::now()->subDays(8)]);
        $recentRevoked = ApiToken::create(['user_id' => $user->id, 'name' => 'recent-revoked', 'token_hash' => hash('sha256', 'recent-revoked'), 'revoked_at' => Carbon::now()->subDay()]);
        $active = ApiToken::create(['user_id' => $user->id, 'name' => 'active', 'token_hash' => hash('sha256', 'active'), 'expires_at' => Carbon::now()->addDay()]);

        $this->artisan('api-tokens:cleanup')->assertSuccessful();

        $this->assertDatabaseMissing('api_tokens', ['id' => $oldRevoked->id]);
        $this->assertDatabaseMissing('api_tokens', ['id' => $oldExpired->id]);
        $this->assertDatabaseHas('api_tokens', ['id' => $recentRevoked->id]);
        $this->assertDatabaseHas('api_tokens', ['id' => $active->id]);
    }
}
