@extends('layout')

@section('content')
<div class="py-8">
    <div class="max-w-5xl mx-auto">
        <div class="card p-6 mb-4">
            <h2 class="text-xl font-semibold">Laporan Peminjaman - {{ \Carbon\Carbon::createFromFormat('Y-m', $month)->format('F Y') }}</h2>
            <p class="muted mt-1">Periode: {{ $start }} s/d {{ $end }}</p>
            @if(!empty($pdf_missing))
                <div class="mt-3 p-3 bg-yellow-50 border border-yellow-200 rounded text-sm">Paket PDF belum terpasang. Untuk mengunduh PDF, jalankan: <code>composer require barryvdh/laravel-dompdf</code> dan pastikan autoloading ter-update. Anda dapat meninjau laporan di halaman ini.</div>
            @endif
+            @if(!empty($pdf_error))
+                <div class="mt-3 p-3 bg-red-50 border border-red-200 rounded text-sm text-red-700">Terjadi kesalahan saat membuat PDF: {{ $pdf_error }}</div>
+            @endif
        </div>

        <div class="card overflow-hidden">
            <div class="p-4">
                <table class="w-full text-sm table-auto">
                    <thead>
                        <tr class="text-left text-xs text-muted uppercase">
                            <th class="px-3 py-2">#</th>
                            <th class="px-3 py-2">Ruang</th>
                            <th class="px-3 py-2">Kapasitas</th>
                            <th class="px-3 py-2">Jumlah Peminjaman</th>
                            <th class="px-3 py-2">Pendapatan (Rp)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $i => $r)
                        <tr>
                            <td class="px-3 py-2">{{ $i + 1 }}</td>
                            <td class="px-3 py-2">{{ $r['ruang'] }}</td>
                            <td class="px-3 py-2">{{ isset($r['kapasitas']) ? $r['kapasitas'] . ' Orang' : '-' }}</td>
                            <td class="px-3 py-2">{{ $r['total_peminjaman'] }}</td>
                            <td class="px-3 py-2">{{ number_format($r['total_revenue'], 0, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td class="px-3 py-2" colspan="5">Tidak ada data untuk periode ini.</td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="font-medium">
                            <td class="px-3 py-2" colspan="3">Total</td>
                            <td class="px-3 py-2">{{ $totalBookings }}</td>
                            <td class="px-3 py-2">{{ number_format($totalRevenue, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

    </div>
</div>
@endsection
