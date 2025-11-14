<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequireSidebarNavigation
{
    /**
     * Allow navigation only if user clicked a sidebar link recently (cookie present)
     * or the route is whitelisted (login, home, assets, API, etc.).
     */
    public function handle(Request $request, Closure $next)
    {
        // Only apply to browser GET requests for authenticated users
        if (! $request->isMethod('get') || $request->expectsJson()) {
            return $next($request);
        }

        // Don't enforce on unauthenticated routes (login/register) or API/asset routes
        if (! auth()->check()) {
            return $next($request);
        }

        // Whitelist patterns (home, auth, assets, api)
        $whitelistPatterns = [
            '/',
            'login',
            'register',
            'logout',
            'info-pengajuan',
            'css/*',
            'js/*',
            'img/*',
            'storage/*',
            'api/*',
        ];

        foreach ($whitelistPatterns as $pattern) {
            if ($request->is($pattern)) {
                return $next($request);
            }
        }

        // Allow if current page equals the last stored allowed URL (prevents redirect loops)
        $last = session('last_allowed_url');
        if ($last && $request->fullUrl() === $last) {
            return $next($request);
        }

        // Allow if the short-lived cookie is present (set by sidebar click) or query param is present
        if ($request->cookie('allowed_nav') || $request->query('allowed_nav')) {
            // If we have allowed_nav query param, redirect to clean URL (without the param) to hide it
            if ($request->query('allowed_nav')) {
                $cleanUrl = $request->url(); // URL without query string
                return redirect()->to($cleanUrl);
            }
            return $next($request);
        }

        // Otherwise, redirect back to last allowed URL
        $fallback = session('last_allowed_url', url('/'));
        // If fallback equals current, allow to avoid infinite redirect
        if ($fallback === $request->fullUrl()) {
            return $next($request);
        }

        return redirect()->to($fallback)->with('error', 'Use the sidebar to navigate.');
    }
}
