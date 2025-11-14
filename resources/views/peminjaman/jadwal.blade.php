@extends('layout')

@section('content')
<!-- Modal animation styles (reuse from manage view) -->
<style>
    .modal-fade-enter { opacity: 0; transform: scale(0.95); }
    .modal-fade-enter-active { opacity: 1; transform: scale(1); transition: opacity 300ms, transform 300ms; }
    .modal-fade-exit { opacity: 1; transform: scale(1); }
    .modal-fade-exit-active { opacity: 0; transform: scale(0.95); transition: opacity 300ms, transform 300ms; }
    .modal-backdrop-enter { opacity: 0; }
    .modal-backdrop-enter-active { opacity: 1; transition: opacity 300ms; }
    .modal-backdrop-exit { opacity: 1; }
    .modal-backdrop-exit-active { opacity: 0; transition: opacity 300ms; }
</style>

<div class="py-8">
    <div class="max-w-7xl mx-auto">
        <!-- Hero -->
        <div class="card p-6 mb-6">
            <h2 class="text-2xl font-semibold">Jadwal Peminjaman Ruangan</h2>
            <p class="muted mt-1">Daftar seluruh jadwal peminjaman ruangan yang telah diajukan</p>
            @if(auth()->check() && in_array(auth()->user()->role, ['admin', 'petugas']))
                <div class="mt-4">
                    <a href="{{ route('peminjaman.laporan', ['month' => now()->format('Y-m')]) }}" class="btn-primary inline-flex items-center gap-2">Generate Laporan Bulanan ({{ now()->format('F Y') }})</a>
                </div>
            @endif
        </div>

        <!-- Table Card -->
        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full table-auto border-collapse text-sm">
                    <thead>
                        <tr class="text-left text-xs text-muted uppercase tracking-wide">
                            <th class="px-4 py-3">Ruang</th>
                            <th class="px-4 py-3">Tanggal</th>
                            <th class="px-4 py-3">Jam</th>
                            <th class="px-4 py-3">Peminjam</th>
                            <th class="px-4 py-3">Status</th>
                            @if(auth()->check() && in_array(auth()->user()->role, ['admin', 'petugas']))
                                <th class="px-4 py-3">Aksi</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach($jadwal as $j)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3 font-medium">{{ $j->ruang->nama_ruang }}</td>
                            <td class="px-4 py-3 muted">{{ $j->tanggal }}</td>
                            <td class="px-4 py-3 muted">{{ $j->jam_mulai }} - {{ $j->jam_selesai }}</td>
                            <td class="px-4 py-3 muted">{{ $j->user->name }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <span class="badge" style="background:{{ $j->status == 'pending' ? '#fffbeb' : ($j->status == 'disetujui' ? '#ecfdf5' : '#fff1f2') }}; color:{{ $j->status == 'pending' ? '#92400e' : ($j->status == 'disetujui' ? '#065f46' : '#981b1b') }}; border:1px solid rgba(0,0,0,0.04)">
                                        {{ $j->status == 'pending' ? 'Menunggu' : ($j->status == 'disetujui' ? 'Disetujui' : 'Ditolak') }}
                                    </span>

                                    @if($j->status == 'ditolak' && $j->alasan_penolakan)
                                        <button type="button" class="inline-flex items-center px-2 py-1 text-sm text-red-700 bg-red-50 rounded-md hover:bg-red-100 open-reason-modal" data-alasan="{{ e($j->alasan_penolakan) }}" data-oleh="{{ e($j->dibatalkan_oleh) }}">Lihat Alasan</button>
                                    @endif
                                </div>
                            </td>
                            @if(auth()->check() && in_array(auth()->user()->role, ['admin', 'petugas']))
                                <td class="px-4 py-3">
                                    <form method="POST" action="/peminjaman/{{ $j->id }}" class="inline-block" onsubmit="return confirm('Yakin hapus booking ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-ghost text-red-600 inline-flex items-center gap-1">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            Hapus
                                        </button>
                                    </form>
                                </td>
                            @endif
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-4">
                {{-- pagination --}}
                <div class="mt-4">
                    {{ $jadwal->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
            <!-- Reason Modal -->
            <div id="reasonModal" class="fixed inset-0 z-50 hidden overflow-y-auto overflow-x-hidden">
                <div id="reasonBackdrop" class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"></div>
                <div class="flex min-h-screen items-center justify-center p-4">
                    <div id="reasonContent" class="relative max-w-2xl w-full bg-white rounded-lg shadow-lg transform transition-all">
                        <div class="flex items-center justify-between p-6 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-black">Alasan Penolakan</h3>
                            <button onclick="closeReasonModal()" class="text-black/60 hover:text-black/80">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        <div class="p-6">
                            <p id="reasonText" class="text-black/80 whitespace-pre-wrap"></p>
                            <p id="reasonBy" class="mt-4 text-sm text-black/60"></p>
                        </div>
                        <div class="px-6 py-3 border-t border-gray-200 flex justify-end">
                            <button onclick="closeReasonModal()" class="px-4 py-2 text-sm font-medium btn-danger rounded-md transition-shadow duration-200">Tutup</button>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                const reasonModal = document.getElementById('reasonModal');
                const reasonBackdrop = document.getElementById('reasonBackdrop');
                const reasonContent = document.getElementById('reasonContent');
                const reasonText = document.getElementById('reasonText');
                const reasonBy = document.getElementById('reasonBy');

                function openReasonModal(alasan, oleh) {
                    reasonText.textContent = alasan || '-';
                    reasonBy.textContent = oleh ? 'Ditolak oleh: ' + oleh : '';
                    reasonModal.classList.remove('hidden');
                    if (reasonBackdrop) reasonBackdrop.classList.add('modal-backdrop-enter', 'modal-backdrop-enter-active');
                    if (reasonContent) reasonContent.classList.add('modal-fade-enter', 'modal-fade-enter-active');
                    document.documentElement.classList.add('overflow-hidden');
                    setTimeout(() => { if (reasonBackdrop) reasonBackdrop.classList.remove('modal-backdrop-enter', 'modal-backdrop-enter-active'); if (reasonContent) reasonContent.classList.remove('modal-fade-enter', 'modal-fade-enter-active'); }, 300);
                }
                function closeReasonModal() {
                    if (reasonBackdrop) reasonBackdrop.classList.add('modal-backdrop-exit', 'modal-backdrop-exit-active');
                    if (reasonContent) reasonContent.classList.add('modal-fade-exit', 'modal-fade-exit-active');
                    setTimeout(() => { reasonModal.classList.add('hidden'); if (reasonBackdrop) reasonBackdrop.classList.remove('modal-backdrop-exit', 'modal-backdrop-exit-active'); if (reasonContent) reasonContent.classList.remove('modal-fade-exit', 'modal-fade-exit-active'); document.documentElement.classList.remove('overflow-hidden'); }, 300);
                }

                document.addEventListener('click', function(e) {
                    const btn = e.target.closest('.open-reason-modal');
                    if (!btn) return;
                    e.preventDefault();
                    const alasan = btn.dataset.alasan || '';
                    const oleh = btn.dataset.oleh || '';
                    openReasonModal(alasan, oleh);
                });

                if (reasonBackdrop) reasonBackdrop.addEventListener('click', closeReasonModal);
                document.addEventListener('keydown', function(e) { if (e.key === 'Escape') { if (!reasonModal.classList.contains('hidden')) closeReasonModal(); } });
            </script>
@endsection
