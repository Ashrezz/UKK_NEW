<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PreventDirectAccess
{
    /**
     * Handle an incoming request.
     *
     * Require non-API web requests to contain a Referer header from the same host.
     * If not present, redirect back (or to home) with an error message.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Only enforce for GET web requests
        if (! $request->isMethod('get')) {
            return $next($request);
        }

        // Allow API/JSON and assets through
        if ($request->wantsJson() || $request->is('api/*') || $this->isAsset($request)) {
            return $next($request);
        }

        // Public paths that may be accessed directly
        $publicPatterns = [
            '/',
            'login',
            'register',
            'logout',
            'info-pengajuan',
        ];

        foreach ($publicPatterns as $p) {
            if ($request->is($p) || $request->is($p.'/*')) {
                return $next($request);
            }
        }

        $currentUrl = $request->fullUrl();
        $appHost = $request->getHost();

        // Check referer: allow if from same host (normal browser link clicks include referer)
        $referer = $request->headers->get('referer');
        if ($referer) {
            $refererHost = parse_url($referer, PHP_URL_HOST);
            if ($refererHost === $appHost) {
                return $next($request);
            }
        }

        // Otherwise block direct access (typing URL)
        return $this->redirectBackWithMessage();
    }

    protected function isAsset(Request $request)
    {
        return $request->is('css/*') || $request->is('js/*') || $request->is('img/*') || $request->is('build/*') || $request->is('favicon.ico');
    }

    protected function redirectBackWithMessage()
    {
        $redirectTo = url()->previous() ?: route('home');
        return redirect($redirectTo)->with('error', 'Akses langsung melalui URL tidak diperbolehkan. Gunakan navigasi aplikasi.');
    }
}
