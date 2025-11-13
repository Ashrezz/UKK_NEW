@extends('layout')

@section('content')
<div class="min-h-screen py-8 sm:py-12 px-3 sm:px-6 lg:px-8" style="background: var(--page-bg);">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="text-center mb-8 sm:mb-10">
            <h1 class="text-3xl sm:text-4xl font-bold text-black mb-2">Verifikasi Pembayaran</h1>
            <p class="text-sm sm:text-base text-black/70">Kelola dan verifikasi pembayaran peminjaman ruangan</p>
        </div>
        
        <div class="mt-4 sm:mt-6">
            @if($peminjaman->isEmpty())
                <div class="card text-center py-6">
                    <p class="muted">Tidak ada peminjaman yang menunggu verifikasi pembayaran</p>
                </div>
            @else
                <div class="card overflow-x-auto">
                    <div class="p-4 overflow-x-auto">
                        <table class="w-full table-auto border-collapse text-sm">
                            <thead>
                                <tr class="text-left text-xs text-muted uppercase tracking-wide">
                                    <th class="px-4 py-3">Peminjam</th>
                                    <th class="hidden sm:table-cell px-4 py-3">Ruang</th>
                                    <th class="hidden md:table-cell px-4 py-3">Tanggal</th>
                                    <th class="hidden md:table-cell px-4 py-3">Waktu</th>
                                    <th class="hidden lg:table-cell px-4 py-3">Keperluan</th>
                                    <th class="hidden lg:table-cell px-4 py-3">Biaya</th>
                                    <th class="px-4 py-3">Bukti</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                @foreach($peminjaman as $p)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-4 py-3">
                                        <div class="font-medium">{{ $p->user->name }}</div>
                                        <div class="muted text-xs">{{ $p->user->email }}</div>
                                    </td>
                                    <td class="hidden sm:table-cell px-4 py-3 muted">{{ $p->ruang->nama_ruang }}</td>
                                    <td class="hidden md:table-cell px-4 py-3 muted">{{ date('d/m/Y', strtotime($p->tanggal)) }}</td>
                                    <td class="hidden md:table-cell px-4 py-3 muted">{{ substr($p->jam_mulai, 0, 5) }} - {{ substr($p->jam_selesai, 0, 5) }}</td>
                                    <td class="hidden lg:table-cell px-4 py-3 muted">{{ $p->keperluan }}</td>
                                    <td class="hidden lg:table-cell px-4 py-3 muted">Rp {{ number_format($p->biaya, 0, ',', '.') }}</td>
                                    <td class="px-4 py-3">
                                        @if($p->bukti_pembayaran)
                                            <a href="{{ asset('storage/bukti_pembayaran/' . $p->bukti_pembayaran) }}" target="_blank" class="text-red-600 hover:text-red-700 font-medium">Lihat</a>
                                        @else
                                            <span class="muted">Belum ada</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="badge" style="background:{{ $p->status === 'pending' ? '#fffbeb' : ($p->status === 'disetujui' ? '#ecfdf5' : '#fff1f2') }}; color:{{ $p->status === 'pending' ? '#92400e' : ($p->status === 'disetujui' ? '#065f46' : '#981b1b') }};">{{ ucfirst($p->status) }}</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($p->status_pembayaran == 'menunggu_verifikasi' && $p->status == 'pending')
                                        <form action="{{ route('pembayaran.verifikasi', $p->id) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="btn-primary">Verifikasi</button>
                                        </form>
                                        @else
                                            <span class="muted">Sudah Diverifikasi</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection