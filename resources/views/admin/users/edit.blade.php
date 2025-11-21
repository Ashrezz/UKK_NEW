@extends('layout')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-2xl">
    <div class="mb-6">
        <h1 class="text-2xl sm:text-3xl font-bold text-black">Edit User</h1>
        <p class="text-sm text-black/70 mt-1">Update informasi user</p>
    </div>

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

        <form action="{{ route('admin.users.update', $user->id) }}" method="POST">
            @csrf
            @method('PUT')

            <!-- Username -->
            <div class="mb-4">
                <label for="username" class="block text-sm font-medium text-black mb-2">
                    Username <span class="text-red-500">*</span>
                </label>
                <input type="text" name="username" id="username" required
                    value="{{ old('username', $user->username ?? $user->name) }}"
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

            <!-- Role -->
            <div class="mb-4">
                <label for="role" class="block text-sm font-medium text-black mb-2">
                    Role <span class="text-red-500">*</span>
                </label>
                <select name="role" id="role" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-red-500 focus:border-red-500">
                    <option value="admin" {{ old('role', $user->role) === 'admin' ? 'selected' : '' }}>Admin</option>
                    <option value="petugas" {{ old('role', $user->role) === 'petugas' ? 'selected' : '' }}>Petugas</option>
                    <option value="user" {{ old('role', $user->role) === 'user' ? 'selected' : '' }}>User</option>
                </select>
            </div>

            <!-- Badge (Priority Customer Badge) -->
            <div class="mb-4">
                <label for="badge" class="block text-sm font-medium text-black mb-2">
                    Badge Pelanggan Prioritas
                </label>
                <select name="badge" id="badge"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-red-500 focus:border-red-500">
                    <option value="0" {{ old('badge', $user->badge ?? 0) == 0 ? 'selected' : '' }}>Tidak Ada Badge</option>
                    <option value="1" {{ old('badge', $user->badge ?? 0) == 1 ? 'selected' : '' }}>Badge 1</option>
                    <option value="2" {{ old('badge', $user->badge ?? 0) == 2 ? 'selected' : '' }}>Badge 2</option>
                    <option value="3" {{ old('badge', $user->badge ?? 0) == 3 ? 'selected' : '' }}>Badge 3</option>
                </select>
                <p class="text-xs text-gray-500 mt-1">Badge dimulai dari 1 saat user menjadi pelanggan prioritas (prioritas_level > 0)</p>
            </div>

            <!-- Password (Optional) -->
            <div class="mb-4">
                <label for="password" class="block text-sm font-medium text-black mb-2">
                    Password Baru
                </label>
                <input type="password" name="password" id="password"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-red-500 focus:border-red-500 @error('password') border-red-500 @enderror"
                    placeholder="Kosongkan jika tidak ingin mengubah password">
                <p class="text-xs text-gray-500 mt-1">Minimal 8 karakter. Kosongkan jika tidak ingin mengubah password.</p>
                @error('password')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Buttons -->
            <div class="flex gap-3 mt-6">
                <button type="submit" class="btn-primary flex-1">
                    Update User
                </button>
                <a href="{{ route('admin.users.index') }}" class="btn-secondary flex-1 text-center">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
