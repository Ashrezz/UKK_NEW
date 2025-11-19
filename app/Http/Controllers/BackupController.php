<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Services\DatabaseBackupService;
use Carbon\Carbon;

class BackupController extends Controller
{
    public function index(DatabaseBackupService $service)
    {
        $setting = DB::table('backup_settings')->orderByDesc('id')->first();
        $backups = $service->list();
        return view('admin.backups.index', compact('setting', 'backups'));
    }

    public function saveSettings(Request $request)
    {
        $data = $request->validate([
            'frequency_unit' => 'required\nin:day,week,month,year',
            'frequency_interval' => 'required|integer|min:1|max:365',
            'run_time' => 'required|date_format:H:i',
            'enabled' => 'nullable',
        ]);
        $data['enabled'] = $request->has('enabled');
        // compute next run based on now + interval
        $next = Carbon::now()->setTimeFromTimeString($data['run_time']);
        $interval = (int)$data['frequency_interval'];
        switch ($data['frequency_unit']) {
            case 'day': $next->addDays($interval); break;
            case 'week': $next->addWeeks($interval); break;
            case 'month': $next->addMonths($interval); break;
            case 'year': $next->addYears($interval); break;
        }
        $settingRow = [
            'frequency_unit' => $data['frequency_unit'],
            'frequency_interval' => $interval,
            'run_time' => $data['run_time'],
            'enabled' => $data['enabled'],
            'next_run_at' => $next,
            'updated_at' => Carbon::now(),
        ];
        $existing = DB::table('backup_settings')->orderByDesc('id')->first();
        if ($existing) {
            DB::table('backup_settings')->where('id', $existing->id)->update($settingRow);
        } else {
            $settingRow['created_at'] = Carbon::now();
            DB::table('backup_settings')->insert($settingRow);
        }
        return redirect()->route('admin.backups.index')->with('success', 'Pengaturan backup disimpan.');
    }

    public function manual(DatabaseBackupService $service)
    {
        try {
            $result = $service->generate();
            
            // For Railway - immediately offer download
            if (env('APP_ENV') === 'production' || request()->has('download')) {
                $path = storage_path('app/backups/' . $result['filename']);
                if (file_exists($path)) {
                    return response()->download($path, $result['filename'], [
                        'Content-Type' => 'application/sql',
                    ])->deleteFileAfterSend(true);
                }
            }
            
            return redirect()->route('admin.backups.index')->with('success', 'Backup berhasil dibuat: ' . $result['filename']);
        } catch (\Exception $e) {
            \Log::error('Manual backup failed: ' . $e->getMessage());
            return redirect()->route('admin.backups.index')->with('error', 'Backup gagal: ' . $e->getMessage());
        }
    }

    public function download($filename)
    {
        // Validate filename to prevent directory traversal
        if (strpos($filename, '..') !== false || strpos($filename, '/') !== false) {
            abort(400, 'Invalid filename');
        }

        $fullPath = storage_path('app/backups/' . $filename);

        // Check if file exists using file_exists instead of Storage facade
        if (!file_exists($fullPath)) {
            // If file not found, try to regenerate from database
            \Log::warning('Backup file not found on disk, attempting to regenerate', [
                'filename' => $filename,
                'full_path' => $fullPath
            ]);
            
            // Check if backup record exists in database
            $backup = DB::table('backups')->where('filename', $filename)->first();
            if (!$backup) {
                abort(404, 'File backup tidak ditemukan dan tidak ada record di database.');
            }
            
            // Generate fresh backup and download immediately
            try {
                $service = app(DatabaseBackupService::class);
                $result = $service->generate();
                $newPath = storage_path('app/backups/' . $result['filename']);
                
                if (file_exists($newPath)) {
                    return response()->download($newPath, $result['filename'], [
                        'Content-Type' => 'application/sql',
                    ])->deleteFileAfterSend(true);
                }
            } catch (\Exception $e) {
                \Log::error('Failed to regenerate backup: ' . $e->getMessage());
            }
            
            abort(404, 'File backup tidak ditemukan. Silakan buat backup baru.');
        }

        return response()->download($fullPath, $filename, [
            'Content-Type' => 'application/sql',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"'
        ]);
    }

    public function restoreForm()
    {
        return view('admin.backups.restore');
    }

    public function restoreUpload(Request $request, DatabaseBackupService $service)
    {
        $request->validate([
            'backup_file' => 'required|file|mimes:sql,txt|max:10240',
        ]);
        $file = $request->file('backup_file');
        $filename = 'uploaded-restore-' . Carbon::now()->format('Ymd-His') . '.sql';
        $file->storeAs('backups', $filename);
        try {
            $count = $service->restore($filename);
            return redirect()->route('admin.backups.index')->with('success', 'Restore selesai. Statement dieksekusi: ' . $count);
        } catch (\Throwable $e) {
            return redirect()->route('admin.backups.index')->with('error', 'Restore gagal: ' . $e->getMessage());
        }
    }
}
