<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Sistem Peminjaman Ruang</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <!-- Card -->
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
            <!-- Header -->
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 p-6 text-white text-center">
                <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-3">
                    <i class="fas fa-key text-3xl"></i>
                </div>
                <h1 class="text-2xl font-bold">Reset Password</h1>
                <p class="text-blue-100 text-sm mt-1">Pilih metode reset password</p>
            </div>

            <!-- Body -->
            <div class="p-6">
                @if(session('success'))
                <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg p-3 mb-4 text-sm">
                    <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
                </div>
                @endif

                @if(session('error'))
                <div class="bg-red-50 border border-red-200 text-red-800 rounded-lg p-3 mb-4 text-sm">
                    <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
                </div>
                @endif

                <!-- Method Selection -->
                <div class="space-y-4 mb-6">
                    <p class="text-gray-600 text-sm">Pilih salah satu metode untuk reset password:</p>
                    
                    <!-- WhatsApp Option -->
                    <a href="https://wa.me/6281234567890?text=Halo%20Admin,%20saya%20ingin%20reset%20password%20akun%20saya" 
                       target="_blank"
                       class="block bg-green-50 hover:bg-green-100 border-2 border-green-200 rounded-xl p-4 transition-all duration-200">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center text-white mr-4">
                                <i class="fab fa-whatsapp text-2xl"></i>
                            </div>
                            <div class="flex-1">
                                <h3 class="font-semibold text-gray-800">Chat Admin WhatsApp</h3>
                                <p class="text-sm text-gray-600">Hubungi admin untuk reset password</p>
                            </div>
                            <i class="fas fa-chevron-right text-gray-400"></i>
                        </div>
                    </a>

                    <!-- Email Option -->
                    <div class="bg-blue-50 border-2 border-blue-200 rounded-xl p-4">
                        <div class="flex items-center mb-3">
                            <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center text-white mr-4">
                                <i class="fas fa-envelope text-2xl"></i>
                            </div>
                            <div class="flex-1">
                                <h3 class="font-semibold text-gray-800">Reset via Email</h3>
                                <p class="text-sm text-gray-600">Dapatkan kode verifikasi via email</p>
                            </div>
                        </div>

                        <!-- Email Form -->
                        <form action="{{ route('password.send-code') }}" method="POST" class="space-y-3">
                            @csrf
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    <i class="fas fa-envelope mr-1"></i>Alamat Email
                                </label>
                                <input 
                                    type="email" 
                                    name="email" 
                                    value="{{ old('email') }}"
                                    placeholder="nama@email.com"
                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                                    required
                                >
                                @error('email')
                                <p class="text-red-600 text-xs mt-1">
                                    <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                                </p>
                                @enderror
                            </div>

                            <button 
                                type="submit"
                                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 rounded-lg transition-all duration-200 flex items-center justify-center gap-2">
                                <i class="fas fa-paper-plane"></i>
                                Kirim Kode Verifikasi
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Back to Login -->
                <div class="text-center pt-4 border-t">
                    <a href="{{ route('login') }}" class="text-blue-600 hover:text-blue-700 text-sm font-medium inline-flex items-center gap-1">
                        <i class="fas fa-arrow-left"></i>
                        Kembali ke Login
                    </a>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <p class="text-center text-gray-600 text-xs mt-4">
            <i class="fas fa-shield-alt mr-1"></i>
            Data Anda aman dan terlindungi
        </p>
    </div>
</body>
</html>
