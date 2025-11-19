@extends('layout')

@section('content')
<div class="py-8">
    <h1 class="text-2xl font-semibold mb-4">Manajemen Backup Database</h1>
    @if(session('success'))
        <div class="card p-4 mb-4 bg-green-50">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="card p-4 mb-4 bg-red-50">{{ session('error') }}</div>
    @endif

    <div class="grid md:grid-cols-2 gap-6 mb-8">
        <div class="card p-6">
            <h2 class="text-lg font-medium mb-4">Download Backup Database</h2>
            <p class="text-sm text-black/60 mb-4">
                Klik tombol di bawah untuk membuat dan download backup database secara langsung.
                File SQL akan otomatis terdownload ke komputer Anda.
            </p>
            <form method="POST" action="{{ route('admin.backups.manual') }}" class="mb-4">
                @csrf
                <button type="submit" class="btn-primary inline-flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Download Backup Database
                </button>
            </form>
            <hr class="my-4">
            <a href="{{ route('admin.backups.restore.form') }}" class="inline-flex items-center gap-2 text-sm text-blue-600 hover:underline">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L9 8m4-4v12"/>
                </svg>
                Restore Database dari File
            </a>
        </div>
        <div class="card p-6">
            <h2 class="text-lg font-medium mb-4">Informasi</h2>
            <div class="space-y-3 text-sm text-black/70">
                <p>✅ Backup database akan langsung didownload ke komputer Anda</p>
                <p>✅ Tidak disimpan di server (Railway-friendly)</p>
                <p>⚠️ Simpan file backup di tempat aman (Google Drive, Dropbox, dll)</p>
                <p>📁 Gunakan menu "Restore dari File" untuk mengembalikan database dari backup</p>
            </div>
        </div>
    </div>
</div>
@endsection
