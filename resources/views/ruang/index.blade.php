@extends('layout')

@section('content')
<div class="min-h-screen py-8 sm:py-12 px-3 sm:px-6 lg:px-8" style="background: var(--page-bg);">
    <div class="max-w-7xl mx-auto">
        <div class="card p-6 mb-6">
            <h2 class="text-2xl font-semibold">Kelola Ruangan</h2>
            <p class="muted mt-1">Tambah dan kelola ruangan yang tersedia untuk peminjaman</p>
        </div>

        <!-- Add Room Form Card - Admin & Petugas -->
        @if(in_array(auth()->user()->role, ['admin','petugas']))
        <div class="card overflow-hidden mb-6">
            <div class="p-4">
                <h3 class="text-base font-medium mb-3">Tambah Ruangan Baru</h3>

                @if ($errors->any())
                    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-md">
                        <ul class="text-sm text-red-600 space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('ruang.store') }}" class="space-y-3" id="addRoomForm">
                    @csrf
                    <div class="grid grid-cols-1 gap-3 sm:gap-4 sm:grid-cols-3">
                        <!-- Nama Ruang Field -->
                        <div>
                            <label for="nama_ruang" class="block text-xs sm:text-sm font-medium text-black mb-1">
                                Nama Ruang
                            </label>
                            <input type="text" name="nama_ruang" id="nama_ruang" required
                                value="{{ old('nama_ruang') }}"
                                class="appearance-none block w-full px-3 py-2 text-sm border border-gray-300 rounded-md shadow-sm
                                focus:outline-none focus:ring-red-500 focus:border-red-500 text-black
                                transition-colors duration-200"
                                placeholder="Contoh: Ruang Rapat A">
                            @error('nama_ruang')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Deskripsi Field -->
                        <div>
                            <label for="deskripsi" class="block text-xs sm:text-sm font-medium text-black mb-1">
                                Deskripsi
                            </label>
                            <input type="text" name="deskripsi" id="deskripsi"
                                value="{{ old('deskripsi') }}"
                                class="appearance-none block w-full px-3 py-2 text-sm border border-gray-300 rounded-md shadow-sm
                                focus:outline-none focus:ring-red-500 focus:border-red-500 text-black
                                transition-colors duration-200"
                                placeholder="Deskripsi singkat ruangan">
                            @error('deskripsi')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Kapasitas Field -->
                        <div>
                            <label for="kapasitas" class="block text-xs sm:text-sm font-medium text-black mb-1">
                                Kapasitas
                            </label>
                            <input type="number" name="kapasitas" id="kapasitas" required
                                value="{{ old('kapasitas') }}"
                                class="appearance-none block w-full px-3 py-2 text-sm border border-gray-300 rounded-md shadow-sm
                                focus:outline-none focus:ring-red-500 focus:border-red-500 text-black
                                transition-colors duration-200"
                                placeholder="Jumlah orang" min="1">
                            @error('kapasitas')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-3 sm:mt-4">
                        <button type="submit" id="submitBtn"
                            class="w-full sm:w-auto px-4 sm:px-6 py-2 border border-transparent rounded-md shadow-sm text-xs sm:text-sm font-medium text-white
                            btn-danger
                            transform hover:scale-[1.02] transition-all duration-200">
                            Tambah Ruangan
                        </button>
                    </div>
                </form>
            </div>
        </div>
        @endif

        <!-- Room List Card -->
        <div class="card overflow-x-auto">
            <div class="p-4 overflow-x-auto">
                <table class="w-full table-auto border-collapse text-sm">
                    <thead>
                        <tr class="text-left text-xs text-muted uppercase tracking-wide">
                            <th class="px-4 py-3">Nama Ruang</th>
                            <th class="hidden sm:table-cell px-4 py-3">Deskripsi</th>
                            <th class="hidden md:table-cell px-4 py-3">Kapasitas</th>
                            <th class="px-4 py-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach($ruang as $r)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3 font-medium">{{ $r->nama_ruang }}</td>
                            <td class="hidden sm:table-cell px-4 py-3 muted">{{ $r->deskripsi }}</td>
                            <td class="hidden md:table-cell px-4 py-3"><span class="badge" style="background:#eff6ff;color:#1e3a8a;">{{ $r->kapasitas }} Orang</span></td>
                            <td class="px-4 py-3">
                                @if(in_array(auth()->user()->role, ['admin','petugas']))
                                <form method="POST" action="/ruang/{{ $r->id }}" onsubmit="return confirm('Yakin hapus ruang ini? Semua booking juga akan terhapus!')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-ghost text-red-600">Hapus</button>
                                </form>
                                @else
                                <span class="muted">-</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        </div>
    </div>
</div>

<script>
// Prevent double form submission
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('addRoomForm');
    if (form) {
        let isSubmitting = false;

        form.addEventListener('submit', function(e) {
            if (isSubmitting) {
                e.preventDefault();
                return false;
            }

            const submitBtn = document.getElementById('submitBtn');
            if (submitBtn) {
                isSubmitting = true;
                submitBtn.disabled = true;
                submitBtn.textContent = 'Menyimpan...';
                submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
            }
        });
    }
});
</script>
@endsection
