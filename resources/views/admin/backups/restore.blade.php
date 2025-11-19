@extends('layout')

@section('content')
<div class="py-8 max-w-xl mx-auto">
    <h1 class="text-2xl font-semibold mb-4">Restore Backup</h1>
    @if(session('error'))
        <div class="card p-4 mb-4 bg-red-50">{{ session('error') }}</div>
    @endif
    <div class="card p-6">
        <p class="text-sm mb-4 text-black/70">Upload file backup (.sql) yang sebelumnya Anda download untuk mengembalikan database. Proses ini akan menimpa data yang ada.</p>
        <form method="POST" action="{{ route('admin.backups.restore.upload') }}" enctype="multipart/form-data" onsubmit="return confirm('Restore akan menimpa data. Lanjutkan?')">
            @csrf
            <input type="file" name="backup_file" accept=".sql,.txt" required class="border rounded px-3 py-2 w-full mb-4" />
            <button class="btn-danger">Mulai Restore</button>
            <a href="{{ route('admin.backups.index') }}" class="btn-secondary ml-2">Batal</a>
        </form>
    </div>
</div>
@endsection
