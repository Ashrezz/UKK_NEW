@extends('layout')

@section('content')
<div class="min-h-screen py-8 sm:py-12 px-3 sm:px-6 lg:px-8 flex items-center justify-center" style="background: var(--page-bg);">
    <div class="max-w-md w-full">
        <!-- Header -->
        <div class="text-center mb-6 sm:mb-8">
            <h2 class="text-2xl sm:text-3xl font-bold text-black">
                Selamat Datang Kembali
            </h2>
            <p class="mt-2 text-xs sm:text-sm text-black/70">
                Silakan login untuk melanjutkan
            </p>
        </div>

        <!-- Login Card -->
        <div class="card p-6">
            <form method="POST" action="/login" class="space-y-4">
                @csrf

                <!-- Email or Username Field -->
                <div>
                    <label for="email" class="block text-xs sm:text-sm font-medium text-black">
                        Email atau Username
                    </label>
                    <div class="mt-1">
                        <input id="email" name="email" type="text" required
                            class="appearance-none block w-full px-3 py-2 text-sm border border-gray-300 rounded-md shadow-sm placeholder-black/50
                            focus:outline-none focus:ring-red-500 focus:border-red-500 text-black transition-colors duration-200"
                            placeholder="nama atau username">
                    </div>
                </div>

                <!-- Password Field -->
                <div>
                    <label for="password" class="block text-xs sm:text-sm font-medium text-black">
                        Password
                    </label>
                    <div class="mt-1">
                        <input id="password" name="password" type="password" required
                            class="appearance-none block w-full px-3 py-2 text-sm border border-gray-300 rounded-md shadow-sm placeholder-black/50
                            focus:outline-none focus:ring-red-500 focus:border-red-500 text-black transition-colors duration-200"
                            placeholder="••••••••">
                    </div>
                </div>

                <!-- Submit Button -->
                <div>
                    <button type="submit" class="btn-primary w-full justify-center">Login</button>
                </div>

                <!-- Register Link -->
                <div class="text-xs sm:text-sm text-center">
                    <p class="text-black/70">
                        Belum punya akun?
                        <a href="/register" class="font-medium text-red-600 hover:text-red-700">
                            Daftar disini
                        </a>
                    </p>
                    <p class="text-black/70 mt-3">
                        Lupa atau ingin mengganti password?
                        <a
                            href="https://wa.me/{{ preg_replace('/[^0-9]/', '', config('app.admin_whatsapp', '085892925898')) }}?text={{ urlencode('Halo Admin, saya lupa/ingin mengganti password akun saya.') }}"
                            target="_blank"
                            rel="noopener"
                            class="font-medium text-green-600 hover:text-green-700 inline-flex items-center gap-1">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                            </svg>
                            Hubungi Admin via WhatsApp
                        </a>
                    </p>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
