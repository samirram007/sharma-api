<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

/**
 * Authenticate a request from the httpOnly `token` cookie when the client sent
 * no bearer token.
 *
 * `jwt.cookies` already does this for every /api route, but channel
 * authorization lives outside /api (/broadcasting/auth) and only runs the
 * `web` group, so the JWT guard there saw the bearer header alone. A tab whose
 * token is not in web storage — VITE_AUTH_STORAGE defaults to per-tab
 * sessionStorage — keeps working against /api/* through the cookie yet got a
 * 403 here.
 *
 * Unlike `jwt.cookies` this middleware never rejects the request: a missing or
 * expired cookie leaves the request unauthenticated so Laravel still answers
 * 403. A 401 would make the SPA's axios interceptor refresh the token and
 * possibly sign the user out over a failed channel subscription.
 */
class JWTFromCookieIfPresent
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (mixed)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken() ?? $request->cookie('token');

        if (! $token) {
            return $next($request);
        }

        try {
            JWTAuth::setToken($token);
            $user = JWTAuth::authenticate();

            if ($user) {
                Auth::login($user);
            }
        } catch (JWTException $e) {
            // Expired or malformed token: continue unauthenticated.
        }

        return $next($request);
    }
}
