@extends('layout')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-2xl">
    <div class="mb-6">
        <h1 class="text-2xl sm:text-3xl font-bold text-black">Edit Profil</h1>
        <p class="text-sm text-black/70 mt-1">Update informasi profil Anda</p>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-4">
            {{ session('success') }}
        </div>
    @endif

    <div class="card p-6">
        @if($errors->any())
            <div class="alert alert-danger mb-4">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('profile.update') }}" method="POST">
            @csrf
            @method('PUT')

            <!-- Name -->
            <div class="mb-4">
                <label for="name" class="block text-sm font-medium text-black mb-2">
                    Nama Lengkap <span class="text-red-500">*</span>
                </label>
                <input type="text" name="name" id="name" required
                    value="{{ old('name', $user->name) }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-red-500 focus:border-red-500 @error('name') border-red-500 @enderror">
                @error('name')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Username -->
            <div class="mb-4">
                <label for="username" class="block text-sm font-medium text-black mb-2">
                    Username <span class="text-red-500">*</span>
                </label>
                <input type="text" name="username" id="username" required
                    value="{{ old('username', $user->username) }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-red-500 focus:border-red-500 @error('username') border-red-500 @enderror">
                @error('username')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Email -->
            <div class="mb-4">
                <label for="email" class="block text-sm font-medium text-black mb-2">
                    Email <span class="text-red-500">*</span>
                </label>
                <input type="email" name="email" id="email" required
                    value="{{ old('email', $user->email) }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-red-500 focus:border-red-500 @error('email') border-red-500 @enderror">
                @error('email')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- No HP -->
            <div class="mb-4">
                <label for="no_hp" class="block text-sm font-medium text-black mb-2">
                    Nomor HP / WhatsApp <span class="text-red-500">*</span>
                </label>
                <input type="text" name="no_hp" id="no_hp" required
                    value="{{ old('no_hp', $user->no_hp) }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-red-500 focus:border-red-500 @error('no_hp') border-red-500 @enderror"
                    placeholder="08xxxxxxxxxx" minlength="8" maxlength="30">
                @error('no_hp')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Priority Badge Display (read-only) -->
            <div class="mb-4 p-4 rounded-lg border {{ $user->prioritas_level > 0 ? 'bg-gradient-to-r from-yellow-50 to-orange-50 border-yellow-200' : 'bg-gray-50 border-gray-200' }}">
                <div class="flex items-center gap-3">
                    <svg class="w-6 h-6 {{ $user->prioritas_level > 0 ? 'text-yellow-600' : 'text-gray-400' }}" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                    <div class="flex-1">
                        <h4 class="text-sm font-semibold text-gray-800">Status Pelanggan</h4>
                        @if($user->prioritas_level > 0)
                        <div class="flex items-center gap-2 mt-1">
                            @if($user->prioritas_level === 1)
                                <span class="badge" style="background:#dbeafe;color:#1e40af;border:1px solid #3b82f6">🥉 Bronze</span>
                            @elseif($user->prioritas_level === 2)
                                <span class="badge" style="background:#f3e8ff;color:#6b21a8;border:1px solid #a855f7">🥈 Silver</span>
                            @elseif($user->prioritas_level === 3)
                                <span class="badge" style="background:#fef3c7;color:#92400e;border:1px solid #f59e0b">🥇 Gold</span>
                            @endif
                            <span class="text-sm text-gray-600">Diskon: <strong>{{ $user->prioritas_discount_percent }}%</strong></span>
                        </div>
                        @else
                        <p class="text-xs text-gray-500 mt-1">Belum menjadi pelanggan prioritas</p>
                        @endif
                        
                        <!-- Badge Display with Tooltip -->
                        <div class="flex items-center gap-2 mt-2">
                            @php $userBadge = $user->badge ?? 0; @endphp
                            @if($userBadge > 0)
                                <div class="relative inline-block badge-tooltip-container">
                                    <button type="button" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold cursor-help" style="background:#fef3c7;color:#92400e;border:1px solid #f59e0b">
                                        ⭐ Badge {{ $userBadge }}
                                    </button>
                                    <div class="badge-tooltip hidden absolute z-10 w-64 p-3 bg-white border border-gray-200 rounded-lg shadow-lg bottom-full left-0 mb-2">
                                        <div class="text-xs">
                                            <p class="font-semibold text-gray-800 mb-2">✨ Benefit Badge {{ $userBadge }}:</p>
                                            <ul class="list-disc list-inside space-y-1 text-gray-600">
                                                <li>Diskon {{ $user->prioritas_discount_percent }}% untuk setiap peminjaman</li>
                                                <li>Prioritas lebih tinggi dalam antrian booking</li>
                                                @if($userBadge >= 2)
                                                <li>Akses ke ruangan premium</li>
                                                @endif
                                                @if($userBadge >= 3)
                                                <li>Bonus poin loyalty setiap transaksi</li>
                                                @endif
                                            </ul>
                                        </div>
                                        <div class="absolute top-full left-4 -mt-1">
                                            <div class="w-2 h-2 bg-white border-r border-b border-gray-200 transform rotate-45"></div>
                                        </div>
                                    </div>
                                </div>
                                <span class="text-xs text-gray-500">Pelanggan Prioritas</span>
                            @else
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold" style="background:#e5e7eb;color:#6b7280;border:1px solid #d1d5db">
                                    Tidak Ada Badge
                                </span>
                            @endif
                        </div>
                        @if($user->prioritas_since)
                        <p class="text-xs text-gray-500 mt-1">Sejak: {{ \Carbon\Carbon::parse($user->prioritas_since)->format('d M Y') }}</p>
                        @endif
                    </div>
                </div>
            </div>
            
            <style>
            .badge-tooltip-container:hover .badge-tooltip {
                display: block;
            }
            </style>

            <hr class="my-6">

            <h3 class="text-lg font-semibold text-black mb-4">Ubah Password</h3>
            <p class="text-sm text-gray-600 mb-4">Kosongkan jika tidak ingin mengubah password</p>

            <!-- Password -->
            <div class="mb-4">
                <label for="password" class="block text-sm font-medium text-black mb-2">
                    Password Baru
                </label>
                <input type="password" name="password" id="password"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-red-500 focus:border-red-500 @error('password') border-red-500 @enderror"
                    placeholder="Minimal 8 karakter">
                <p class="text-xs text-gray-500 mt-1">⚠️ Password harus minimal 8 karakter</p>
                @error('password')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Password Confirmation -->
            <div class="mb-6">
                <label for="password_confirmation" class="block text-sm font-medium text-black mb-2">
                    Konfirmasi Password Baru
                </label>
                <input type="password" name="password_confirmation" id="password_confirmation"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-red-500 focus:border-red-500"
                    placeholder="Masukkan password yang sama">
            </div>

            <!-- Buttons -->
            <div class="flex gap-3">
                <button type="submit" class="btn-primary flex-1">
                    Update Profil
                </button>
                <a href="{{ route('home') }}" class="btn-secondary flex-1 text-center">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
