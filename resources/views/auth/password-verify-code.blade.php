<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Kode - Sistem Peminjaman Ruang</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .code-input {
            width: 3rem;
            height: 3.5rem;
            font-size: 1.5rem;
            text-align: center;
            font-weight: 600;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <!-- Card -->
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
            <!-- Header -->
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 p-6 text-white text-center">
                <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-3">
                    <i class="fas fa-shield-alt text-3xl"></i>
                </div>
                <h1 class="text-2xl font-bold">Verifikasi Kode</h1>
                <p class="text-blue-100 text-sm mt-1">Masukkan 6 digit kode verifikasi</p>
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

                <!-- Info -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-6 text-sm text-blue-800">
                    <i class="fas fa-info-circle mr-2"></i>
                    Kode verifikasi telah dikirim ke email <strong>{{ session('reset_email') }}</strong>
                </div>

                <!-- Verification Form -->
                <form action="{{ route('password.verify.post') }}" method="POST" class="space-y-6">
                    @csrf

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-3 text-center">
                            Kode Verifikasi (6 digit)
                        </label>
                        <div class="flex justify-center gap-2" id="code-inputs">
                            <input type="text" maxlength="1" class="code-input border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" data-index="0">
                            <input type="text" maxlength="1" class="code-input border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" data-index="1">
                            <input type="text" maxlength="1" class="code-input border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" data-index="2">
                            <input type="text" maxlength="1" class="code-input border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" data-index="3">
                            <input type="text" maxlength="1" class="code-input border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" data-index="4">
                            <input type="text" maxlength="1" class="code-input border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" data-index="5">
                        </div>
                        <input type="hidden" name="code" id="final-code">
                        @error('code')
                        <p class="text-red-600 text-xs mt-2 text-center">
                            <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                        </p>
                        @enderror
                    </div>

                    <button
                        type="submit"
                        id="verify-btn"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 rounded-lg transition-all duration-200 flex items-center justify-center gap-2">
                        <i class="fas fa-check"></i>
                        Verifikasi Kode
                    </button>
                </form>

                <!-- Resend Code -->
                <div class="text-center mt-4 pt-4 border-t">
                    <p class="text-sm text-gray-600 mb-2">Tidak menerima kode?</p>
                    <a href="{{ route('password.request') }}" class="text-blue-600 hover:text-blue-700 text-sm font-medium inline-flex items-center gap-1">
                        <i class="fas fa-redo"></i>
                        Kirim Ulang Kode
                    </a>
                </div>

                <!-- Back -->
                <div class="text-center mt-3">
                    <a href="{{ route('password.request') }}" class="text-gray-600 hover:text-gray-700 text-sm inline-flex items-center gap-1">
                        <i class="fas fa-arrow-left"></i>
                        Kembali
                    </a>
                </div>
            </div>
        </div>

        <!-- Timer -->
        <div class="text-center mt-4">
            <p class="text-sm text-gray-600">
                <i class="fas fa-clock mr-1"></i>
                Kode berlaku selama <span id="timer" class="font-semibold text-blue-600">15:00</span>
            </p>
        </div>
    </div>

    <script>
        // Auto-focus and auto-tab functionality
        const inputs = document.querySelectorAll('.code-input');
        const finalCodeInput = document.getElementById('final-code');
        const verifyBtn = document.getElementById('verify-btn');

        inputs[0].focus();

        inputs.forEach((input, index) => {
            input.addEventListener('input', (e) => {
                const value = e.target.value;

                if (value.length === 1 && index < inputs.length - 1) {
                    inputs[index + 1].focus();
                }

                // Update hidden input
                updateFinalCode();
            });

            input.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && !e.target.value && index > 0) {
                    inputs[index - 1].focus();
                }
            });

            input.addEventListener('paste', (e) => {
                e.preventDefault();
                const pasteData = e.clipboardData.getData('text').slice(0, 6);

                pasteData.split('').forEach((char, i) => {
                    if (inputs[i]) {
                        inputs[i].value = char;
                    }
                });

                updateFinalCode();

                if (pasteData.length === 6) {
                    inputs[5].focus();
                }
            });
        });

        function updateFinalCode() {
            const code = Array.from(inputs).map(input => input.value).join('');
            finalCodeInput.value = code;

            if (code.length === 6) {
                verifyBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            } else {
                verifyBtn.classList.add('opacity-50', 'cursor-not-allowed');
            }
        }

        // Timer countdown
        let timeLeft = 15 * 60; // 15 minutes in seconds
        const timerElement = document.getElementById('timer');

        function updateTimer() {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            timerElement.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;

            if (timeLeft > 0) {
                timeLeft--;
            } else {
                timerElement.textContent = 'Kode Expired';
                timerElement.classList.add('text-red-600');
            }
        }

        setInterval(updateTimer, 1000);
    </script>
</body>
</html>
