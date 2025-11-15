@extends('layout')

@section('content')
<div class="min-h-screen py-8 sm:py-12 px-3 sm:px-6 lg:px-8 flex items-center justify-center" style="background: var(--page-bg);">
    <div class="max-w-md w-full">
        <!-- Header -->
        <div class="text-center mb-6 sm:mb-8">
            <h2 class="text-2xl sm:text-3xl font-bold text-black">
                Buat Akun Baru
            </h2>
            <p class="mt-2 text-xs sm:text-sm text-black/70">
                Daftar untuk mengakses sistem peminjaman ruangan
            </p>
        </div>

        <!-- Register Card -->
        <div class="card p-6">
            <form method="POST" action="/register" class="space-y-4">
                @csrf

                <!-- Username Field -->
                <div>
                    <label for="username" class="block text-xs sm:text-sm font-medium text-black">
                        Username
                    </label>
                    <div class="mt-1">
                        <input id="username" name="username" type="text" required
                            class="appearance-none block w-full px-3 py-2 text-sm border border-gray-300 rounded-md shadow-sm placeholder-black/50
                            focus:outline-none focus:ring-red-500 focus:border-red-500 text-black transition-colors duration-200"
                            placeholder="Pilih username (min 3 karakter)">
                    </div>
                </div>

                <!-- Email Field -->
                <div>
                    <label for="email" class="block text-xs sm:text-sm font-medium text-black">
                        Email
                    </label>
                    <div class="mt-1">
                        <input id="email" name="email" type="email" required
                            class="appearance-none block w-full px-3 py-2 text-sm border border-gray-300 rounded-md shadow-sm placeholder-black/50
                            focus:outline-none focus:ring-red-500 focus:border-red-500 text-black transition-colors duration-200"
                            placeholder="nama@email.com">
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

                <!-- Password Confirmation Field -->
                <div>
                    <label for="password_confirmation" class="block text-xs sm:text-sm font-medium text-black">
                        Konfirmasi Password
                    </label>
                    <div class="mt-1">
                        <input id="password_confirmation" name="password_confirmation" type="password" required
                            class="appearance-none block w-full px-3 py-2 text-sm border border-gray-300 rounded-md shadow-sm placeholder-black/50
                            focus:outline-none focus:ring-red-500 focus:border-red-500 text-black transition-colors duration-200"
                            placeholder="••••••••">
                    </div>
                </div>

                <!-- Submit Button -->
                <div>
                    <button type="submit" class="btn-primary w-full justify-center">Daftar</button>
                </div>

                <!-- Login Link -->
                <div class="text-xs sm:text-sm text-center">
                    <p class="text-black/70">
                        Sudah punya akun?
                        <a href="/login" class="font-medium text-red-600 hover:text-red-700">
                            Login disini
                        </a>
                    </p>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
