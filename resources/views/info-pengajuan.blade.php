@extends('layout')

@section('content')
<div class="max-w-4xl mx-auto py-8">
    <div class="card p-6 mb-6">
        <h1 class="text-2xl font-semibold">Info Pengajuan & Kebijakan Pembayaran</h1>
        <p class="muted mt-1">Panduan lengkap alur pembayaran dan pengembalian dana</p>
    </div>

    <div class="card p-4 mb-4">
        <h2 class="text-lg font-semibold mb-2">Ringkasan</h2>
        <p class="muted">Di halaman ini kami menjelaskan bagaimana alur pembayaran dan pengembalian dana bekerja saat pengajuan peminjaman ruang disetujui atau ditolak oleh petugas atau admin.</p>
    </div>

    <div class="card p-4 mb-4">
        <h2 class="text-lg font-semibold mb-3">Jika Pengajuan Disetujui</h2>
        <ul class="list-disc list-inside space-y-2 muted">
            <li>Pengajuan yang disetujui akan otomatis menandakan pembayaran terverifikasi.</li>
            <li>Pengguna dapat mengakses detail booking dan bukti pembayaran via halaman peminjaman.</li>
        </ul>
    </div>

    <div class="card p-4 mb-4">
        <h2 class="text-lg font-semibold mb-3">Jika Pengajuan Ditolak</h2>
        <p class="muted mb-3">Saat petugas atau admin menolak pengajuan, mereka akan mencantumkan keterangan penolakan. Informasi pembayaran ditangani sebagai berikut:</p>
        <ul class="list-disc list-inside space-y-3 muted">
            <li>Jika pengguna membayar dengan Down Payment (DP):
                <ul class="list-disc list-inside ml-4 mt-2">
                    <li>DP tidak dapat dikembalikan jika pembatalan dilakukan oleh pengguna sendiri.</li>
                    <li>Jika admin yang membatalkan peminjaman, admin harus memproses pengembalian dana.</li>
                </ul>
            </li>
            <li>Jika admin/petugas menolak karena alasan internal, pengguna akan diberi keterangan dan jika perlu admin dapat mengembalikan pembayaran.</li>
        </ul>
    </div>

    <div class="card p-4">
        <h2 class="text-lg font-semibold mb-3">Kontak & Bantuan</h2>
        <p class="muted">Jika Anda punya pertanyaan tentang pengembalian dana atau status pembayaran, silakan hubungi admin melalui kontak yang tersedia atau buka tiket pembatalan pada halaman akun Anda.</p>
    </div>
</div>

@endsection

