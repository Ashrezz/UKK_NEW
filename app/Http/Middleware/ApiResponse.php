<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ApiResponse
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if ($request->is('api/*')) {
            return response()->json([
                'success' => $response->getStatusCode() < 400,
                'status_code' => $response->getStatusCode(),
                'message' => $response->getReasonPhrase(),
                'data' => json_decode($response->getContent(), true)
            ]);
        }

        return $response;
    }
}
