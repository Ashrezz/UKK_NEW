@extends('layout')

@section('content')
<!-- Modal Styles -->
<style>
    .modal-fade-enter {
        opacity: 0;
        transform: scale(0.95);
    }
    .modal-fade-enter-active {
        opacity: 1;
        transform: scale(1);
        transition: opacity 300ms, transform 300ms;
    }
    .modal-fade-exit {
        opacity: 1;
        transform: scale(1);
    }
    .modal-fade-exit-active {
        opacity: 0;
        transform: scale(0.95);
        transition: opacity 300ms, transform 300ms;
    }
    .modal-backdrop-enter {
        opacity: 0;
    }
    .modal-backdrop-enter-active {
        opacity: 1;
        transition: opacity 300ms;
    }
    .modal-backdrop-exit {
        opacity: 1;
    }
    .modal-backdrop-exit-active {
        opacity: 0;
        transition: opacity 300ms;
    }
</style>

<div class="py-8">
    <div class="max-w-7xl mx-auto">
        <div class="card p-6 mb-6">
            <h2 class="text-2xl font-semibold">Kelola Peminjaman</h2>
            <p class="muted mt-1">Daftar peminjaman yang menunggu persetujuan dan verifikasi pembayaran</p>
            <div class="mt-4">
                <form method="POST" action="{{ route('peminjaman.cleanup') }}" onsubmit="return confirm('Jalankan cleanup? Semua booking yang tanggalnya lewat akan dihapus permanen.')">
                    @csrf
                    <button type="submit" class="btn-ghost">Jalankan Cleanup Jadwal Lama</button>
                </form>
            </div>
        </div>

        <!-- Tab navigation -->
        <div class="flex space-x-4 mb-4 border-b border-gray-200">
            <button onclick="switchTab('regular')" id="tabRegular" class="px-4 py-2 font-medium text-gray-600 border-b-2 border-transparent hover:border-blue-500 transition">
                Reguler <span class="badge ml-1">{{ $peminjaman->count() }}</span>
            </button>
            <button onclick="switchTab('priority')" id="tabPriority" class="px-4 py-2 font-medium text-gray-600 border-b-2 border-transparent hover:border-blue-500 transition">
                Prioritas <span class="badge ml-1" style="background:#fef3c7;color:#92400e">{{ $prioritas->count() }}</span>
            </button>
        </div>

        <!-- Regular bookings table -->
        <div id="contentRegular" class="card mb-6" style="display:block">
                <div class="p-4 overflow-x-auto">
                    <table class="w-full table-auto border-collapse text-sm">
                        <thead>
                            <tr class="text-left text-xs text-muted uppercase tracking-wide">
                                <th class="px-4 py-3">Ruang</th>
                                <th class="px-4 py-3">Tanggal</th>
                                <th class="px-4 py-3">Jam</th>
                                <th class="px-4 py-3">Peminjam</th>
                                <th class="px-4 py-3">Keperluan</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Bukti Bayar</th>
                                <th class="px-4 py-3">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach($peminjaman as $p)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-3 font-medium">{{ $p->ruang->nama_ruang }}</td>
                                <td class="px-4 py-3 muted">{{ $p->tanggal }}</td>
                                <td class="px-4 py-3 muted">{{ $p->jam_mulai }} - {{ $p->jam_selesai }}</td>
                                <td class="px-4 py-3 muted">{{ $p->user->name }}</td>
                                <td class="px-4 py-3 muted"><p class="max-w-xs overflow-hidden text-ellipsis">{{ $p->keperluan }}</p></td>
                                <td class="px-4 py-3">
                                    @if($p->status === 'pending')
                                        <span class="badge" style="background:#fffbeb;color:#92400e;border:1px solid rgba(148,64,14,0.06)">Menunggu</span>
                                    @elseif($p->status === 'approved' || $p->status === 'disetujui')
                                        <span class="badge" style="background:#ecfdf5;color:#065f46;border:1px solid rgba(6,95,70,0.06)">Disetujui</span>
                                    @elseif($p->status === 'rejected' || $p->status === 'ditolak')
                                        <span class="badge" style="background:#fff1f2;color:#981b1b;border:1px solid rgba(152,27,27,0.06)">Ditolak</span>
                                    @endif

                                    @if($p->status_pembayaran === 'belum_bayar')
                                        <div class="mt-2"><span class="badge" style="background:#f3f4f6;color:#374151;border:1px solid rgba(0,0,0,0.04)">Belum Bayar</span></div>
                                    @elseif($p->status_pembayaran === 'menunggu_verifikasi')
                                        <div class="mt-2"><span class="badge" style="background:#eff6ff;color:#1e3a8a;border:1px solid rgba(29,78,216,0.06)">Menunggu Verifikasi</span></div>
                                    @elseif($p->status_pembayaran === 'terverifikasi' || $p->status_pembayaran === 'lunas')
                                        <div class="mt-2"><span class="badge" style="background:#ecfdf5;color:#065f46;border:1px solid rgba(6,95,70,0.06)">Terverifikasi</span></div>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if($p->bukti_pembayaran_src)
                                        <button type="button" data-src="{{ $p->bukti_pembayaran_src }}" onclick="openModal(this.dataset.src)" class="btn-ghost inline-flex items-center">Lihat Bukti</button>
                                    @else
                                        <span class="muted">Belum ada bukti</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 flex items-center gap-2">
                                    @if($p->status === 'pending' || $p->status_pembayaran === 'menunggu_verifikasi')
                                        <form method="POST" action="{{ $p->status === 'pending' ? url('/peminjaman/' . $p->id . '/approve') : route('pembayaran.verifikasi', $p->id) }}">
                                            @csrf
                                            <button type="submit" class="btn-primary inline-flex items-center">{{ $p->status === 'pending' ? 'Setujui' : 'Verifikasi' }}</button>
                                        </form>

                                        @if($p->status === 'pending')
                                            <button type="button" data-id="{{ $p->id }}" data-name="{{ $p->user->name }}" class="btn-ghost text-yellow-700 open-reject-modal">Tolak</button>
                                        @endif
                                    @endif

                                    <form method="POST" action="/peminjaman/{{ $p->id }}" onsubmit="return confirm('Yakin hapus booking ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-ghost text-red-600">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
        </div>

        <!-- Priority bookings table -->
        <div id="contentPriority" class="card mb-6" style="display:none">
                <div class="p-4 overflow-x-auto">
                    <table class="w-full table-auto border-collapse text-sm">
                        <thead>
                            <tr class="text-left text-xs text-muted uppercase tracking-wide">
                                <th class="px-4 py-3">Ruang</th>
                                <th class="px-4 py-3">Tanggal</th>
                                <th class="px-4 py-3">Jam</th>
                                <th class="px-4 py-3">Peminjam</th>
                                <th class="px-4 py-3">Keperluan</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Badge</th>
                                <th class="px-4 py-3">Bukti Bayar</th>
                                <th class="px-4 py-3">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach($prioritas as $p)
                            <tr class="hover:bg-yellow-50 transition-colors">
                                <td class="px-4 py-3 font-medium">{{ $p->ruang->nama_ruang }}</td>
                                <td class="px-4 py-3 muted">{{ $p->tanggal }}</td>
                                <td class="px-4 py-3 muted">{{ $p->jam_mulai }} - {{ $p->jam_selesai }}</td>
                                <td class="px-4 py-3 muted">{{ $p->user->name }}</td>
                                <td class="px-4 py-3 muted"><p class="max-w-xs overflow-hidden text-ellipsis">{{ $p->keperluan }}</p></td>
                                <td class="px-4 py-3">
                                    @if($p->status === 'pending')
                                        <span class="badge" style="background:#fffbeb;color:#92400e;border:1px solid rgba(148,64,14,0.06)">Menunggu</span>
                                    @elseif($p->status === 'approved' || $p->status === 'disetujui')
                                        <span class="badge" style="background:#ecfdf5;color:#065f46;border:1px solid rgba(6,95,70,0.06)">Disetujui</span>
                                    @elseif($p->status === 'rejected' || $p->status === 'ditolak')
                                        <span class="badge" style="background:#fff1f2;color:#981b1b;border:1px solid rgba(152,27,27,0.06)">Ditolak</span>
                                    @endif

                                    @if($p->status_pembayaran === 'belum_bayar')
                                        <div class="mt-2"><span class="badge" style="background:#f3f4f6;color:#374151;border:1px solid rgba(0,0,0,0.04)">Belum Bayar</span></div>
                                    @elseif($p->status_pembayaran === 'menunggu_verifikasi')
                                        <div class="mt-2"><span class="badge" style="background:#eff6ff;color:#1e3a8a;border:1px solid rgba(29,78,216,0.06)">Menunggu Verifikasi</span></div>
                                    @elseif($p->status_pembayaran === 'terverifikasi' || $p->status_pembayaran === 'lunas')
                                        <div class="mt-2"><span class="badge" style="background:#ecfdf5;color:#065f46;border:1px solid rgba(6,95,70,0.06)">Terverifikasi</span></div>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @php $lvl = (int)($p->user->prioritas_level ?? 0); @endphp
                                    @if($lvl === 1)
                                        <span class="badge" style="background:#dbeafe;color:#1e40af;border:1px solid #3b82f6">Bronze</span>
                                    @elseif($lvl === 2)
                                        <span class="badge" style="background:#f3e8ff;color:#6b21a8;border:1px solid #a855f7">Silver</span>
                                    @elseif($lvl === 3)
                                        <span class="badge" style="background:#fef3c7;color:#92400e;border:1px solid #f59e0b">Gold</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if($p->bukti_pembayaran_src)
                                        <button type="button" data-src="{{ $p->bukti_pembayaran_src }}" onclick="openModal(this.dataset.src)" class="btn-ghost inline-flex items-center">Lihat Bukti</button>
                                    @else
                                        <span class="muted">Belum ada bukti</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 flex items-center gap-2">
                                    @if($p->status === 'pending' || $p->status_pembayaran === 'menunggu_verifikasi')
                                        <form method="POST" action="{{ $p->status === 'pending' ? url('/peminjaman/' . $p->id . '/approve') : route('pembayaran.verifikasi', $p->id) }}">
                                            @csrf
                                            <button type="submit" class="btn-primary inline-flex items-center">{{ $p->status === 'pending' ? 'Setujui' : 'Verifikasi' }}</button>
                                        </form>

                                        @if($p->status === 'pending')
                                            <button type="button" data-id="{{ $p->id }}" data-name="{{ $p->user->name }}" class="btn-ghost text-yellow-700 open-reject-modal">Tolak</button>
                                        @endif
                                    @endif

                                    <form method="POST" action="/peminjaman/{{ $p->id }}" onsubmit="return confirm('Yakin hapus booking ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-ghost text-red-600">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
        </div>
    </div>
</div>
<!-- Modal -->
<div id="imageModal" class="fixed inset-0 z-50 hidden overflow-y-auto overflow-x-hidden">
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" id="modalBackdrop"></div>

    <!-- Modal Content -->
    <div class="flex min-h-screen items-center justify-center p-4">
    <div id="modalContent" class="relative max-w-2xl w-full bg-white rounded-lg shadow-lg transform transition-all">
            <!-- Header -->
            <div class="flex items-center justify-between p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-black">
                    Bukti Pembayaran
                </h3>
                <button onclick="closeModal()" class="text-black/60 hover:text-black/80">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Body -->
            <div class="p-6">
                <div class="flex justify-center">
                    <img id="modalImage" src="" alt="Bukti Pembayaran"
                        class="max-w-full max-h-[70vh] rounded-lg shadow-lg object-contain">
                </div>
            </div>

            <!-- Footer -->
            <div class="px-6 py-3 border-t border-gray-200 flex justify-end space-x-2">
                    <button onclick="window.open(document.getElementById('modalImage').src, '_blank')"
                        class="px-4 py-2 text-sm font-medium text-black/70 hover:text-black transition-colors">
                        Buka di Tab Baru
                    </button>
                    <button onclick="closeModal()"
                        class="px-4 py-2 text-sm font-medium btn-danger rounded-md transition-shadow duration-200">
                        Tutup
                    </button>
            </div>
        </div>
    </div>
</div>

<script>
    function openModal(imageSrc) {
        const modal = document.getElementById('imageModal');
        const modalContent = document.getElementById('modalContent');
        const backdrop = document.getElementById('modalBackdrop');
        const modalImage = document.getElementById('modalImage');

        // Set image source
        modalImage.src = imageSrc;

        // Show modal
        modal.classList.remove('hidden');

    // Add animation classes (guarded)
    if (backdrop) backdrop.classList.add('modal-backdrop-enter');
    if (modalContent) modalContent.classList.add('modal-fade-enter');

    // Force reflow (guarded)
    if (backdrop) void backdrop.offsetHeight;
    if (modalContent) void modalContent.offsetHeight;

        // Start animation
    if (backdrop) backdrop.classList.add('modal-backdrop-enter-active');
    if (modalContent) modalContent.classList.add('modal-fade-enter-active');

        // Remove animation classes
        setTimeout(() => {
            if (backdrop) backdrop.classList.remove('modal-backdrop-enter', 'modal-backdrop-enter-active');
            if (modalContent) modalContent.classList.remove('modal-fade-enter', 'modal-fade-enter-active');
        }, 300);
    }

    function closeModal() {
        const modal = document.getElementById('imageModal');
        const modalContent = document.getElementById('modalContent');
        const backdrop = document.getElementById('modalBackdrop');

        // Add exit animation classes (guarded)
        if (backdrop) backdrop.classList.add('modal-backdrop-exit');
        if (modalContent) modalContent.classList.add('modal-fade-exit');

        // Force reflow
        if (backdrop) void backdrop.offsetHeight;
        if (modalContent) void modalContent.offsetHeight;

        // Start exit animation
        if (backdrop) backdrop.classList.add('modal-backdrop-exit-active');
        if (modalContent) modalContent.classList.add('modal-fade-exit-active');

        // Hide modal after animation
        setTimeout(() => {
            modal.classList.add('hidden');
            if (backdrop) backdrop.classList.remove('modal-backdrop-exit', 'modal-backdrop-exit-active');
            if (modalContent) modalContent.classList.remove('modal-fade-exit', 'modal-fade-exit-active');
        }, 300);
    }

    // Close modal when clicking backdrop (guarded)
    const _backdrop = document.getElementById('modalBackdrop');
    if (_backdrop) _backdrop.addEventListener('click', closeModal);

    // Prevent closing when clicking modal content (guarded)
    const _modalContent = document.getElementById('modalContent');
    if (_modalContent) _modalContent.addEventListener('click', function(e) {
        e.stopPropagation();
    });

    // Close on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const modal = document.getElementById('imageModal');
            if (modal && !modal.classList.contains('hidden')) {
                closeModal();
            }
        }
    });
</script>
<!-- Reject Modal -->
<!-- Reject Modal (floating like Bukti Pembayaran) -->
<div id="rejectModal" class="fixed inset-0 z-50 hidden overflow-y-auto overflow-x-hidden">
    <!-- Backdrop -->
    <div id="rejectBackdrop" class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"></div>

    <!-- Modal Content -->
    <div class="flex min-h-screen items-center justify-center p-4">
        <div id="rejectContent" class="relative max-w-2xl w-full bg-white rounded-lg shadow-lg transform transition-all">
            <!-- Header -->
            <div class="flex items-center justify-between p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-black">Tolak Pengajuan</h3>
                <button onclick="closeRejectModal()" class="text-black/60 hover:text-black/80">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Body -->
            <div class="p-6">
                <form id="rejectForm" method="POST" action="">
                    @csrf
                    <div class="mb-4">
                        <label for="alasan_penolakan" class="block text-sm font-medium text-black">Keterangan Penolakan</label>
                        <textarea id="alasan_penolakan" name="alasan_penolakan" rows="6" class="mt-1 block w-full rounded-md border border-gray-300 text-black placeholder-black/50" placeholder="Jelaskan alasan penolakan..."></textarea>
                    </div>
                </form>
            </div>

            <!-- Footer -->
            <div class="px-6 py-3 border-t border-gray-200 flex justify-end space-x-2">
                    <button type="button" onclick="closeRejectModal()" class="px-4 py-2 text-sm font-medium text-black/70 hover:text-black transition-colors">Batal</button>
                    <button type="button" onclick="submitRejectForm()" class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md transition-colors">Kirim & Tolak</button>
            </div>
        </div>
    </div>
</div>

<script>
    const rejectModalEl = document.getElementById('rejectModal');
    const rejectBackdropEl = document.getElementById('rejectBackdrop');
    const rejectContentEl = document.getElementById('rejectContent');

    function openRejectModal(id) {
        const form = document.getElementById('rejectForm');
        form.action = '/peminjaman/' + id + '/reject';

        // show modal
        rejectModalEl.classList.remove('hidden');

        // animation in
        if (rejectBackdropEl) rejectBackdropEl.classList.add('modal-backdrop-enter');
        if (rejectContentEl) rejectContentEl.classList.add('modal-fade-enter');
        if (rejectBackdropEl) void rejectBackdropEl.offsetHeight;
        if (rejectContentEl) void rejectContentEl.offsetHeight;
        if (rejectBackdropEl) rejectBackdropEl.classList.add('modal-backdrop-enter-active');
        if (rejectContentEl) rejectContentEl.classList.add('modal-fade-enter-active');

        // prevent background scroll
        document.documentElement.classList.add('overflow-hidden');

        // focus textarea
        setTimeout(() => {
            const ta = document.getElementById('alasan_penolakan');
            if (ta) ta.focus();
        }, 150);
    }

    function closeRejectModal() {
        if (rejectBackdropEl) rejectBackdropEl.classList.add('modal-backdrop-exit');
        if (rejectContentEl) rejectContentEl.classList.add('modal-fade-exit');
        if (rejectBackdropEl) void rejectBackdropEl.offsetHeight;
        if (rejectContentEl) void rejectContentEl.offsetHeight;
        if (rejectBackdropEl) rejectBackdropEl.classList.add('modal-backdrop-exit-active');
        if (rejectContentEl) rejectContentEl.classList.add('modal-fade-exit-active');

        setTimeout(() => {
            rejectModalEl.classList.add('hidden');
            if (rejectBackdropEl) rejectBackdropEl.classList.remove('modal-backdrop-enter', 'modal-backdrop-enter-active', 'modal-backdrop-exit', 'modal-backdrop-exit-active');
            if (rejectContentEl) rejectContentEl.classList.remove('modal-fade-enter', 'modal-fade-enter-active', 'modal-fade-exit', 'modal-fade-exit-active');
            document.documentElement.classList.remove('overflow-hidden');
            const ta = document.getElementById('alasan_penolakan'); if (ta) ta.value = '';
        }, 300);
    }

    function submitRejectForm() {
        const form = document.getElementById('rejectForm');
        if (!form) return;
        form.submit();
    }

    // delegated click to open modal
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.open-reject-modal');
        if (!btn) return;
        e.preventDefault();
        const id = btn.dataset.id;
        if (id) openRejectModal(id);
    });

    // backdrop click closes
    if (rejectBackdropEl) rejectBackdropEl.addEventListener('click', closeRejectModal);

    // close on escape
    document.addEventListener('keydown', function(e) { if (e.key === 'Escape') { if (!rejectModalEl.classList.contains('hidden')) closeRejectModal(); } });

    // Tab switching logic
    function switchTab(tab) {
        const regular = document.getElementById('contentRegular');
        const priority = document.getElementById('contentPriority');
        const tabRegular = document.getElementById('tabRegular');
        const tabPriority = document.getElementById('tabPriority');

        if (tab === 'priority') {
            regular.style.display = 'none';
            priority.style.display = 'block';
            tabRegular.classList.remove('border-blue-500', 'text-blue-600');
            tabRegular.classList.add('text-gray-600', 'border-transparent');
            tabPriority.classList.remove('border-transparent', 'text-gray-600');
            tabPriority.classList.add('border-blue-500', 'text-blue-600');
        } else {
            regular.style.display = 'block';
            priority.style.display = 'none';
            tabPriority.classList.remove('border-blue-500', 'text-blue-600');
            tabPriority.classList.add('text-gray-600', 'border-transparent');
            tabRegular.classList.remove('border-transparent', 'text-gray-600');
            tabRegular.classList.add('border-blue-500', 'text-blue-600');
        }
    }

    // Activate default tab
    switchTab('regular');
</script>
@endsection
