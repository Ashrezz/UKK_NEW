@extends('layout')

@section('content')
<div class="py-8">
    <!-- Hero -->
    <div class="card p-6 mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold">Selamat Datang di Sistem Peminjaman Ruangan</h1>
                <p class="muted mt-1">Lihat status peminjaman ruangan Anda dan kelola peminjaman dengan mudah</p>
            </div>
            <div class="flex items-center gap-3">
                @if(auth()->user()->role !== 'admin'    )
                    <a href="/peminjaman/create" class="btn-primary">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                        <span>Ajukan Peminjaman</span>
                    </a>
                @endif
                <a href="/peminjaman/jadwal" class="btn-ghost">Lihat Jadwal</a>
            </div>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="stats-card card">
            <div class="muted text-xs">Total Peminjaman</div>
            <div class="text-2xl font-semibold">{{ count($peminjaman) }}</div>
        </div>
        <div class="stats-card card">
            <div class="muted text-xs">Peminjaman Aktif</div>
            <div class="text-2xl font-semibold text-green-600">{{ $peminjaman->where('status', 'disetujui')->count() }}</div>
        </div>
        <div class="stats-card card">
            <div class="muted text-xs">Menunggu Persetujuan</div>
            <div class="text-2xl font-semibold text-yellow-600">{{ $peminjaman->where('status', 'pending')->count() }}</div>
        </div>
    </div>

    <!-- Table -->
    <div class="card overflow-x-auto">
        <div class="px-6 py-4 border-b">
            <h3 class="font-medium">Daftar Peminjaman Terkini</h3>
            <div class="muted text-sm">Semua peminjaman ruangan yang telah diajukan</div>
        </div>

        <div class="p-4 overflow-x-auto">
            <table class="w-full table-auto border-collapse text-sm">
                <thead>
                    <tr class="text-left text-xs text-muted uppercase tracking-wide">
                        <th class="px-4 py-3">Ruang</th>
                        <th class="hidden sm:table-cell px-4 py-3">Tanggal</th>
                        <th class="hidden md:table-cell px-4 py-3">Jam</th>
                        <th class="hidden lg:table-cell px-4 py-3">Peminjam</th>
                        <th class="px-4 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach($peminjaman as $p)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 font-medium">{{ $p->ruang->nama_ruang }}</td>
                        <td class="hidden sm:table-cell px-4 py-3 muted">{{ $p->tanggal }}</td>
                        <td class="hidden md:table-cell px-4 py-3 muted">{{ $p->jam_mulai }} - {{ $p->jam_selesai }}</td>
                        <td class="hidden lg:table-cell px-4 py-3 muted">{{ $p->user->name }}</td>
                        <td class="px-4 py-3">
                            @if($p->status == 'pending')
                                <span class="badge" style="background:#fff7ed;color:#92400e;border:1px solid rgba(148,64,14,0.06)">{{ ucfirst($p->status) }}</span>
                            @elseif($p->status == 'disetujui')
                                <span class="badge" style="background:#ecfdf5;color:#065f46;border:1px solid rgba(6,95,70,0.06)">{{ ucfirst($p->status) }}</span>
                            @else
                                <span class="badge" style="background:#fff1f2;color:#981b1b;border:1px solid rgba(152,27,27,0.06)">{{ ucfirst($p->status) }}</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
