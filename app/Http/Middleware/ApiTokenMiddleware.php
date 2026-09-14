<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiTokenMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $plain = $request->bearerToken();
        if (!$plain) return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);

        $token = ApiToken::with('user')->where('token_hash', hash('sha256', $plain))->first();
        if (!$token) return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);

        $token->forceFill(['last_used_at' => now()])->saveQuietly();
        $request->setUserResolver(fn () => $token->user);
        $request->attributes->set('api_token', $token);
        return $next($request);
    }
}
