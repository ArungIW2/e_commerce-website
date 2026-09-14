<?php

namespace App\Http\Controllers\Api;

use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthApiController extends ApiController
{
    private function issueToken(User $user): string
    {
        $maxActiveTokens = max(1, (int) config('services.api.max_active_tokens', 5));

        $activeTokens = $user->apiTokens()
            ->whereNull('revoked_at')
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->orderBy('created_at')
            ->get();

        $tokensToRevoke = max(0, $activeTokens->count() - $maxActiveTokens + 1);
        $activeTokens->take($tokensToRevoke)
            ->each(fn (ApiToken $token) => $token->forceFill(['revoked_at' => now()])->saveQuietly());

        $plain = Str::random(80);

        ApiToken::create([
            'user_id' => $user->id,
            'name' => 'api',
            'token_hash' => hash('sha256', $plain),
            'expires_at' => now()->addDays(max(1, (int) config('services.api.token_ttl_days', 30))),
        ]);

        return $plain;
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create($data);

        return $this->success([
            'user' => $user->makeHidden(['role']),
            'token' => $this->issueToken($user),
        ], 'Registered.', 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return $this->error('Invalid credentials.', 401);
        }

        return $this->success([
            'user' => $user->makeHidden(['role']),
            'token' => $this->issueToken($user),
        ]);
    }

    public function logout(Request $request)
    {
        $request->attributes->get('api_token')?->forceFill(['revoked_at' => now()])->saveQuietly();

        return $this->success(null, 'Logged out.');
    }

    public function me(Request $request)
    {
        return $this->success($request->user()->makeHidden(['role']));
    }
}
