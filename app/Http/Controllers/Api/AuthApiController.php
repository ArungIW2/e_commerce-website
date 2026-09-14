<?php

namespace App\Http\Controllers\Api;

use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthApiController extends ApiController
{
    private function issueToken(User $user): string
    {
        $plain = Str::random(80);
        ApiToken::create(['user_id' => $user->id, 'name' => 'api', 'token_hash' => hash('sha256', $plain)]);
        return $plain;
    }

    public function register(Request $request)
    {
        $data = $request->validate(['name' => ['required','string','max:255'], 'email' => ['required','email','max:255','unique:users,email'], 'password' => ['required','string','min:8','confirmed']]);
        $user = User::create($data);
        return $this->success(['user' => $user, 'token' => $this->issueToken($user)], 'Registered.', 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate(['email' => ['required','email'], 'password' => ['required','string']]);
        $user = User::where('email', $data['email'])->first();
        if (!$user || !Hash::check($data['password'], $user->password)) throw ValidationException::withMessages(['email' => ['Invalid credentials.']]);
        return $this->success(['user' => $user, 'token' => $this->issueToken($user)]);
    }

    public function logout(Request $request)
    {
        $request->attributes->get('api_token')?->delete();
        return $this->success(null, 'Logged out.');
    }

    public function me(Request $request) { return $this->success($request->user()); }
}
