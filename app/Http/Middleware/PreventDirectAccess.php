<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PreventDirectAccess
{
    /**
     * Middleware sederhana yang mencegah akses langsung ke halaman via URL.
     *
     * Cek referer atau session untuk memverifikasi user datang dari halaman yang diizinkan.
     * Jika direct access (tidak ada referer dari domain yang sama), redirect ke halaman sebelumnya.
     * Session diperbarui untuk melacak halaman yang dikunjungi.
     */
    public function handle(Request $request, Closure $next)
    {
        // Hanya berlaku untuk request GET
        if (!$request->isMethod('get')) {
            return $next($request);
        }

        // Hanya untuk user yang authenticated
        if (!auth()->check()) {
            return $next($request);
        }

        $currentUrl = $request->fullUrl();
        $referer = $request->headers->get('referer');

        // List halaman yang boleh diakses langsung (home, login, logout, assets, API)
        $allowedDirectPaths = [
            '/',
            'login',
            'register',
            'logout',
            'info-pengajuan',
            'css/',
            'js/',
            'img/',
            'storage/',
            'api/',
        ];

        // Check apakah current path ada di allowed list
        $isAllowedDirect = false;
        foreach ($allowedDirectPaths as $path) {
            if ($request->is($path . '*')) {
                $isAllowedDirect = true;
                break;
            }
        }

        // Jika halaman diizinkan untuk diakses langsung, lanjut
        if ($isAllowedDirect) {
            session(['last_visited_url' => $currentUrl]);
            return $next($request);
        }

        // Check referer: apakah request berasal dari halaman internal (domain sama)
        if ($referer) {
            $appHost = parse_url(url('/'), PHP_URL_HOST);
            $refererHost = parse_url($referer, PHP_URL_HOST);

            // Jika referer dari domain yang sama, izinkan akses
            if ($refererHost === $appHost) {
                session(['last_visited_url' => $currentUrl]);
                return $next($request);
            }
        }

        // Check session: apakah ada halaman sebelumnya yang diizinkan
        $lastUrl = session('last_visited_url');
        if ($lastUrl) {
            // Jika current URL berbeda dari last, berarti akses dari sidebar atau link internal
            if ($currentUrl !== $lastUrl) {
                session(['last_visited_url' => $currentUrl]);
                return $next($request);
            }
        }

        // Jika tidak ada referer dan direct access terdeteksi, redirect ke home dengan pesan
        $previousUrl = session('last_visited_url', url('/'));
        return redirect()->to($previousUrl)->with('error', 'Akses langsung tidak diizinkan. Gunakan navigasi yang tersedia.');
    }
}
