<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Services\DatabaseBackupService;
use Carbon\Carbon;

class BackupController extends Controller
{
    public function index()
    {
        try {
            $supabase = new \App\Services\SupabaseStorageService();
            $files = $supabase->listFiles();
            return view('admin.backups.index', compact('files'));
        } catch (\Exception $e) {
            \Log::error('Backup index error: ' . $e->getMessage());
            return view('admin.backups.index');
        }
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
            \Log::info('=== BACKUP MANUAL START ===');

            // Check database connection first
            try {
                $dbName = DB::connection()->getDatabaseName();
                \Log::info('Database connected', ['db' => $dbName]);
            } catch (\Exception $e) {
                \Log::error('Database connection failed', ['error' => $e->getMessage()]);
                throw new \Exception('Database connection failed: ' . $e->getMessage());
            }

            // Set limits
            set_time_limit(300);
            ini_set('memory_limit', '512M');
            \Log::info('Limits set');

            // Generate SQL backup and upload to Supabase
            \Log::info('Calling generate with Supabase upload');
            $result = $service->generate(true);
            \Log::info('generate completed', ['size' => $result['size'], 'filename' => $result['filename']]);

            if (empty($result['sql_content'])) {
                throw new \Exception('Backup content is empty');
            }

            // Stream download langsung tanpa save
            return response()->streamDownload(function() use ($result) {
                echo $result['sql_content'];
            }, $result['filename'], [
                'Content-Type' => 'application/sql',
                'Content-Disposition' => 'attachment; filename="' . $result['filename'] . '"',
            ]);
        } catch (\Throwable $e) {
            \Log::error('=== BACKUP MANUAL FAILED ===', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString()
            ]);

            // Always return JSON error for debugging
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile()),
                'type' => get_class($e)
            ], 500);
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
