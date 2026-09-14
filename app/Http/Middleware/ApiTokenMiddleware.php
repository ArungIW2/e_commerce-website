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
        if (!$plain) return response()->json(['success'=>false,'message'=>'Unauthenticated.'],401);
        $token = ApiToken::with('user')->where('token_hash',hash('sha256',$plain))->first();
        if (!$token || !$token->isActive()) { if($token&&$token->expires_at?->isPast()) $token->forceFill(['revoked_at'=>now()])->saveQuietly(); return response()->json(['success'=>false,'message'=>'Unauthenticated.'],401); }
        if (!$token->last_used_at || $token->last_used_at->lt(now()->subMinutes(5))) $token->forceFill(['last_used_at'=>now()])->saveQuietly();
        $request->setUserResolver(fn()=> $token->user); $request->attributes->set('api_token',$token); return $next($request);
    }
}
