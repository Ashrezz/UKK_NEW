<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!auth()->check()) {
            return redirect('login');
        }

        if (empty($roles)) {
            return $next($request);
        }

        foreach ($roles as $role) {
            if (auth()->user()->role === $role) {
                return $next($request);
            }
        }

        // If the client expects JSON (API), return 403 JSON response instead of redirecting
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access.'
            ], 403);
        }

        // Redirect back to the last allowed page if available to prevent forced URL jumps
        $fallback = session('last_allowed_url', route('home'));
        return redirect()->to($fallback)->with('error', 'Unauthorized access.');
    }
}
