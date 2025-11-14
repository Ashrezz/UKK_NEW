<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class StoreLastVisited
{
    /**
     * Store the last GET url visited by an authenticated user.
     */
    public function handle(Request $request, Closure $next)
    {
        // Only store for regular GET page views (ignore API/AJAX and asset requests)
        if ($request->isMethod('get') && ! $request->expectsJson() && auth()->check()) {
            // Exclude login/register/logout routes to avoid redirect loops
            $excluded = [];

            // Attempt to add named routes if they exist without failing
            try {
                $excluded = [route('login', [], false), route('logout', [], false), route('register', [], false)];
            } catch (\Exception $e) {
                // ignore if routes not defined
            }

            $current = $request->path();

            // Only store if not an excluded path
            if ($current && ! in_array('/'.$current, $excluded)) {
                session(['last_allowed_url' => $request->fullUrl()]);
            }
        }

        return $next($request);
    }
}
