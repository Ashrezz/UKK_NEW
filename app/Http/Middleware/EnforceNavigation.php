<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class EnforceNavigation
{
    /**
     * Handle an incoming request.
     * If the request is a direct-typed URL (no referer) or referer doesn't match last allowed URL,
     * redirect back to the stored last allowed URL for the session.
     */
    public function handle(Request $request, Closure $next)
    {
        // Only apply to normal browser GET requests for authenticated users
        if (! $request->isMethod('get') || $request->expectsJson() || ! auth()->check()) {
            return $next($request);
        }

        $last = session('last_allowed_url');
        $current = $request->fullUrl();

        // If we don't have a stored last URL, allow (first page)
        if (empty($last)) {
            return $next($request);
        }

        // If current equals last, allow
        if ($current === $last) {
            return $next($request);
        }

        // Check Referer header if present and normalize
        $referer = $request->headers->get('referer');

        if ($referer) {
            try {
                $refererHost = parse_url($referer, PHP_URL_HOST);
                $appHost = parse_url(URL::to('/'), PHP_URL_HOST);
                // If referer is from same host and matches the last stored URL's host, allow
                if ($refererHost && $refererHost === $appHost) {
                    // also allow if referer equals last
                    if ($referer === $last) {
                        return $next($request);
                    }
                }
            } catch (\Exception $e) {
                // ignore parse errors
            }
        }

        // Otherwise, redirect back to last allowed URL to prevent forced navigation
        return redirect()->to($last)->with('error', 'Direct navigation is not allowed. You have been returned to the previous page.');
    }
}
