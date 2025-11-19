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
            <h2 class="text-lg font-medium mb-4">Pengaturan Jadwal</h2>
            <form method="POST" action="{{ route('admin.backups.settings.save') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="text-sm font-medium">Interval</label>
                    <div class="flex gap-2 mt-1">
                        <input type="number" name="frequency_interval" value="{{ old('frequency_interval', $setting->frequency_interval ?? 1) }}" min="1" class="w-24 border rounded px-2 py-1"/>
                        <select name="frequency_unit" class="border rounded px-2 py-1">
                            @foreach(['day'=>'Hari','week'=>'Minggu','month'=>'Bulan','year'=>'Tahun'] as $u=>$label)
                                <option value="{{ $u }}" @selected(old('frequency_unit', $setting->frequency_unit ?? 'week')==$u)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <label class="text-sm font-medium">Waktu Eksekusi (HH:MM)</label>
                    <input type="text" name="run_time" value="{{ old('run_time', $setting->run_time ?? '02:00') }}" class="mt-1 border rounded px-2 py-1 w-32"/>
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="enabled" value="1" @checked(old('enabled', $setting->enabled ?? true)) />
                    <span class="text-sm">Aktifkan jadwal otomatis</span>
                </div>
                <button class="btn-primary">Simpan Pengaturan</button>
                @if($setting && $setting->next_run_at)
                    <p class="text-xs mt-2 text-black/60">Backup berikutnya: {{ \Carbon\Carbon::parse($setting->next_run_at)->format('d M Y H:i') }}</p>
                @endif
            </form>
        </div>
        <div class="card p-6">
            <h2 class="text-lg font-medium mb-4">Backup Manual</h2>
            <p class="text-sm text-black/60 mb-4">
                @if(config('app.env') === 'production')
                    <span class="inline-flex items-center px-2 py-1 rounded text-xs bg-yellow-100 text-yellow-800 mb-2">
                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        Railway Mode: Backup akan langsung didownload
                    </span><br>
                @endif
                Backup database akan disimpan dan dapat didownload.
            </p>
            <form method="POST" action="{{ route('admin.backups.manual') }}" class="mb-4">
                @csrf
                <input type="hidden" name="download" value="1">
                <button type="submit" class="btn-secondary">Buat & Download Backup Sekarang</button>
            </form>
            <a href="{{ route('admin.backups.restore.form') }}" class="text-sm text-blue-600 hover:underline">Restore dari File</a>
        </div>
    </div>

    <div class="card p-6">
        <h2 class="text-lg font-medium mb-4">Daftar Backup</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase text-black/60">
                        <th class="py-2">File</th>
                        <th class="py-2">Ukuran</th>
                        <th class="py-2">Dibuat</th>
                        <th class="py-2 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                @forelse($backups as $b)
                    <tr>
                        <td class="py-2 font-medium">{{ $b['filename'] }}</td>
                        <td class="py-2">{{ number_format($b['size_bytes']/1024,2) }} KB</td>
                        <td class="py-2">{{ \Carbon\Carbon::parse($b['created_at'])->format('d M Y H:i') }}</td>
                        <td class="py-2 text-right">
                            <a class="text-blue-600 hover:underline" href="{{ route('admin.backups.download', $b['filename']) }}">Download</a>
                            @if(config('app.env') === 'production')
                                <span class="text-xs text-black/40 ml-2">(regenerate if missing)</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="py-4 text-center text-black/50">Belum ada backup</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
