<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Marks public GET responses cacheable for 30 minutes.
 *
 * Sets Cache-Control: public, max-age=1800 so browsers (and any CDN
 * in front) hold the rendered HTML for 30 minutes per URL/query-string.
 *
 * Vary: Cookie ensures shared caches treat distinct sessions as
 * distinct cache entries — important because pages embed a per-session
 * CSRF token used by Livewire POSTs.
 *
 * Applied only to successful GET responses; POST/PUT/DELETE and any
 * 3xx/4xx/5xx response are left untouched.
 */
class CachePublicPage
{
    private const MAX_AGE = 1800; // 30 minutes

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->isMethodCacheable()) {
            return $response;
        }

        if ($response->getStatusCode() !== 200) {
            return $response;
        }

        $response->headers->set(
            'Cache-Control',
            'public, max-age='.self::MAX_AGE.', s-maxage='.self::MAX_AGE,
        );

        // Cookie variance prevents shared caches from leaking per-session content.
        $existingVary = $response->headers->get('Vary', '');
        $varyParts = array_filter(array_map('trim', explode(',', $existingVary)));
        foreach (['Cookie', 'Accept-Encoding'] as $needed) {
            if (! in_array($needed, $varyParts, true)) {
                $varyParts[] = $needed;
            }
        }
        $response->headers->set('Vary', implode(', ', $varyParts));

        return $response;
    }
}
