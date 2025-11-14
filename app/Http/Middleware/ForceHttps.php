<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ForceHttps
{
    /**
     * Enforce HTTPS for all requests.
     * Redirect HTTP requests to HTTPS.
     * Add HSTS header for security.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // If not secure (HTTP), redirect to HTTPS
        if (!$request->isSecure() && env('APP_ENV') === 'production') {
            return redirect()->secure($request->getRequestUri(), 301);
        }

        $response = $next($request);

        // Add HSTS header (Strict-Transport-Security)
        // tells browsers to always use HTTPS for this domain
        $response->header('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');

        return $response;
    }
}
