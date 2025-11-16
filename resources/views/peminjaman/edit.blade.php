@extends('layout')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-2xl">
    <div class="mb-6">
        <h1 class="text-2xl sm:text-3xl font-bold text-black">Edit Peminjaman</h1>
        <p class="text-sm text-black/70 mt-1">Update detail peminjaman ruangan</p>
    </div>

    @if(session('error'))
        <div class="alert alert-danger mb-4">
            {{ session('error') }}
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

        <form action="{{ route('peminjaman.update', $peminjaman->id) }}" method="POST">
            @csrf
            @method('PUT')

            <!-- Ruang -->
            <div class="mb-4">
                <label for="ruang_id" class="block text-sm font-medium text-black mb-2">
                    Pilih Ruangan <span class="text-red-500">*</span>
                </label>
                <select name="ruang_id" id="ruang_id" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-red-500 focus:border-red-500">
                    <option value="">-- Pilih Ruangan --</option>
                    @foreach($ruangs as $ruang)
                        <option value="{{ $ruang->id }}" {{ old('ruang_id', $peminjaman->ruang_id) == $ruang->id ? 'selected' : '' }}>
                            {{ $ruang->nama_ruang }}
                        </option>
                    @endforeach
                </select>
                @error('ruang_id')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Tanggal -->
            <div class="mb-4">
                <label for="tanggal" class="block text-sm font-medium text-black mb-2">
                    Tanggal Peminjaman <span class="text-red-500">*</span>
                </label>
                <input type="date" name="tanggal" id="tanggal" required
                    value="{{ old('tanggal', $peminjaman->tanggal) }}"
                    min="{{ date('Y-m-d') }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-red-500 focus:border-red-500 @error('tanggal') border-red-500 @enderror">
                @error('tanggal')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Jam Mulai -->
            <div class="mb-4">
                <label for="jam_mulai" class="block text-sm font-medium text-black mb-2">
                    Jam Mulai <span class="text-red-500">*</span>
                </label>
                <input type="time" name="jam_mulai" id="jam_mulai" required
                    value="{{ old('jam_mulai', substr($peminjaman->jam_mulai, 0, 5)) }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-red-500 focus:border-red-500 @error('jam_mulai') border-red-500 @enderror">
                @error('jam_mulai')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Jam Selesai -->
            <div class="mb-4">
                <label for="jam_selesai" class="block text-sm font-medium text-black mb-2">
                    Jam Selesai <span class="text-red-500">*</span>
                </label>
                <input type="time" name="jam_selesai" id="jam_selesai" required
                    value="{{ old('jam_selesai', substr($peminjaman->jam_selesai, 0, 5)) }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-red-500 focus:border-red-500 @error('jam_selesai') border-red-500 @enderror">
                @error('jam_selesai')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Keperluan -->
            <div class="mb-4">
                <label for="keperluan" class="block text-sm font-medium text-black mb-2">
                    Keperluan <span class="text-red-500">*</span>
                </label>
                <textarea name="keperluan" id="keperluan" rows="4" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-red-500 focus:border-red-500 @error('keperluan') border-red-500 @enderror"
                    placeholder="Jelaskan tujuan peminjaman ruangan...">{{ old('keperluan', $peminjaman->keperluan) }}</textarea>
                @error('keperluan')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Info Biaya -->
            <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-md">
                <p class="text-sm text-blue-800">
                    <strong>ℹ️ Informasi:</strong> Biaya akan dihitung ulang berdasarkan durasi peminjaman (Rp 50.000/jam)
                </p>
            </div>

            <!-- Buttons -->
            <div class="flex gap-3">
                <button type="submit" class="btn-primary flex-1">
                    Update Peminjaman
                </button>
                <a href="{{ route('home') }}" class="btn-secondary flex-1 text-center">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
