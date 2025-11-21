<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Baru - Sistem Peminjaman Ruang</title>
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
                    <i class="fas fa-lock text-3xl"></i>
                </div>
                <h1 class="text-2xl font-bold">Password Baru</h1>
                <p class="text-blue-100 text-sm mt-1">Buat password baru untuk akun Anda</p>
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

                <!-- Success Message -->
                <div class="bg-green-50 border border-green-200 rounded-lg p-3 mb-6 text-sm text-green-800">
                    <i class="fas fa-check-circle mr-2"></i>
                    Verifikasi berhasil! Silakan masukkan password baru Anda.
                </div>

                <!-- Password Form -->
                <form action="{{ route('password.update') }}" method="POST" class="space-y-4" id="password-form">
                    @csrf
                    
                    <!-- New Password -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fas fa-lock mr-1"></i>Password Baru
                        </label>
                        <div class="relative">
                            <input 
                                type="password" 
                                name="password" 
                                id="password"
                                placeholder="Minimal 8 karakter"
                                class="w-full px-4 py-2.5 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                                required
                            >
                            <button 
                                type="button"
                                onclick="togglePassword('password')"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <i class="fas fa-eye" id="password-icon"></i>
                            </button>
                        </div>
                        @error('password')
                        <p class="text-red-600 text-xs mt-1">
                            <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                        </p>
                        @enderror
                        <!-- Password strength indicator -->
                        <div class="mt-2">
                            <div class="h-1 bg-gray-200 rounded-full overflow-hidden">
                                <div id="strength-bar" class="h-full transition-all duration-300"></div>
                            </div>
                            <p id="strength-text" class="text-xs mt-1"></p>
                        </div>
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fas fa-lock mr-1"></i>Konfirmasi Password
                        </label>
                        <div class="relative">
                            <input 
                                type="password" 
                                name="password_confirmation" 
                                id="password_confirmation"
                                placeholder="Masukkan ulang password"
                                class="w-full px-4 py-2.5 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                                required
                            >
                            <button 
                                type="button"
                                onclick="togglePassword('password_confirmation')"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <i class="fas fa-eye" id="password_confirmation-icon"></i>
                            </button>
                        </div>
                        <p id="match-text" class="text-xs mt-1"></p>
                    </div>

                    <!-- Password Requirements -->
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 text-xs text-gray-600">
                        <p class="font-semibold mb-2"><i class="fas fa-info-circle mr-1"></i>Persyaratan Password:</p>
                        <ul class="space-y-1">
                            <li id="req-length" class="flex items-center gap-2">
                                <i class="fas fa-circle text-gray-400 text-xs"></i>
                                Minimal 8 karakter
                            </li>
                            <li id="req-upper" class="flex items-center gap-2">
                                <i class="fas fa-circle text-gray-400 text-xs"></i>
                                Minimal 1 huruf besar
                            </li>
                            <li id="req-lower" class="flex items-center gap-2">
                                <i class="fas fa-circle text-gray-400 text-xs"></i>
                                Minimal 1 huruf kecil
                            </li>
                            <li id="req-number" class="flex items-center gap-2">
                                <i class="fas fa-circle text-gray-400 text-xs"></i>
                                Minimal 1 angka
                            </li>
                        </ul>
                    </div>

                    <button 
                        type="submit"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 rounded-lg transition-all duration-200 flex items-center justify-center gap-2">
                        <i class="fas fa-check"></i>
                        Reset Password
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = document.getElementById(fieldId + '-icon');
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Password strength checker
        const passwordInput = document.getElementById('password');
        const confirmInput = document.getElementById('password_confirmation');
        const strengthBar = document.getElementById('strength-bar');
        const strengthText = document.getElementById('strength-text');
        const matchText = document.getElementById('match-text');

        passwordInput.addEventListener('input', checkPasswordStrength);
        confirmInput.addEventListener('input', checkPasswordMatch);

        function checkPasswordStrength() {
            const password = passwordInput.value;
            let strength = 0;
            
            // Check requirements
            const hasLength = password.length >= 8;
            const hasUpper = /[A-Z]/.test(password);
            const hasLower = /[a-z]/.test(password);
            const hasNumber = /[0-9]/.test(password);
            
            // Update requirement indicators
            updateRequirement('req-length', hasLength);
            updateRequirement('req-upper', hasUpper);
            updateRequirement('req-lower', hasLower);
            updateRequirement('req-number', hasNumber);
            
            // Calculate strength
            if (hasLength) strength++;
            if (hasUpper) strength++;
            if (hasLower) strength++;
            if (hasNumber) strength++;
            
            // Update strength bar
            const colors = ['bg-red-500', 'bg-orange-500', 'bg-yellow-500', 'bg-green-500'];
            const texts = ['Lemah', 'Cukup', 'Baik', 'Kuat'];
            const textColors = ['text-red-600', 'text-orange-600', 'text-yellow-600', 'text-green-600'];
            
            strengthBar.className = 'h-full transition-all duration-300 ' + (colors[strength - 1] || '');
            strengthBar.style.width = (strength * 25) + '%';
            strengthText.className = 'text-xs mt-1 ' + (textColors[strength - 1] || '');
            strengthText.textContent = texts[strength - 1] || '';
            
            checkPasswordMatch();
        }

        function checkPasswordMatch() {
            const password = passwordInput.value;
            const confirm = confirmInput.value;
            
            if (confirm.length === 0) {
                matchText.textContent = '';
                return;
            }
            
            if (password === confirm) {
                matchText.className = 'text-xs mt-1 text-green-600';
                matchText.innerHTML = '<i class="fas fa-check-circle mr-1"></i>Password cocok';
            } else {
                matchText.className = 'text-xs mt-1 text-red-600';
                matchText.innerHTML = '<i class="fas fa-times-circle mr-1"></i>Password tidak cocok';
            }
        }

        function updateRequirement(id, met) {
            const element = document.getElementById(id);
            const icon = element.querySelector('i');
            
            if (met) {
                icon.classList.remove('fa-circle', 'text-gray-400');
                icon.classList.add('fa-check-circle', 'text-green-500');
                element.classList.add('text-green-600');
            } else {
                icon.classList.remove('fa-check-circle', 'text-green-500');
                icon.classList.add('fa-circle', 'text-gray-400');
                element.classList.remove('text-green-600');
            }
        }
    </script>
</body>
</html>
