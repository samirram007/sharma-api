<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Allows an endpoint's response to be embedded in an <iframe> on the
 * configured SPA origins despite a server-level X-Frame-Options: SAMEORIGIN
 * header (often injected by nginx/Apache or the hosting panel outside this
 * application's control).
 *
 * Browsers that support CSP Level 2 (every modern browser) enforce
 * `Content-Security-Policy: frame-ancestors` and ignore X-Frame-Options on
 * the same response — so listing the SPA origin here is the only change
 * needed; the web server's header does NOT have to be removed.
 *
 * Because this is a response header it must be attached per-route (routes are
 * the only place we can meaningfully say "this response may be framed"), so
 * use it as route middleware, e.g.:
 *
 *   Route::get('nodes/{node}/preview', …)->middleware(AddFrameAncestorsHeader::class);
 *
 * Allowed origins come from config('app.frame_ancestors') (FRAME_ANCESTORS
 * env var, space-separated). When empty the header is not sent at all, which
 * leaves the server-level X-Frame-Options policy fully in effect.
 */
class AddFrameAncestorsHeader
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $ancestors = trim((string) config('app.frame_ancestors'));

        if ($ancestors !== '') {
            $response->headers->set(
                'Content-Security-Policy',
                'frame-ancestors '.$ancestors
            );
        }

        return $response;
    }
}
