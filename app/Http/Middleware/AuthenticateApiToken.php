<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $tokenString = $request->bearerToken();

        if (!$tokenString) {
            return response()->json(['message' => 'Unauthenticated. API Token is missing.'], 401);
        }

        // Cari token dalam database (menggunakan sha256 hash untuk keamanan)
        $apiToken = ApiToken::where('token', hash('sha256', $tokenString))->first();

        if (!$apiToken) {
            return response()->json(['message' => 'Unauthenticated. Invalid API Token.'], 401);
        }

        // Cari data user pemilik token
        $user = User::find($apiToken->user_id);
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated. User not found.'], 401);
        }

        // Set user ke Laravel Auth Context & request
        Auth::setUser($user);
        $request->merge(['auth_user' => $user]);

        return $next($request);
    }
}
