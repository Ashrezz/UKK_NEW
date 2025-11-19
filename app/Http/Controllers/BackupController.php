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
        try {
            $setting = null;
            $backups = [];
            
            // Try to get settings if table exists
            try {
                if (DB::getSchemaBuilder()->hasTable('backup_settings')) {
                    $setting = DB::table('backup_settings')->orderByDesc('id')->first();
                }
            } catch (\Exception $e) {
                \Log::warning('Could not load backup settings: ' . $e->getMessage());
            }
            
            // Get backups list
            $backups = $service->list();
            
            return view('admin.backups.index', compact('setting', 'backups'));
        } catch (\Exception $e) {
            \Log::error('Backup index error: ' . $e->getMessage());
            return view('admin.backups.index', [
                'setting' => null,
                'backups' => [],
                'error' => 'Error loading backup data: ' . $e->getMessage()
            ]);
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
        // Set longer execution time for large databases
        set_time_limit(300); // 5 minutes
        ini_set('memory_limit', '512M');
        
        try {
            \Log::info('Starting manual backup process');
            
            $result = $service->generate();
            
            \Log::info('Backup generated', ['filename' => $result['filename'] ?? 'unknown']);
            
            // For Railway/Production - immediately offer download using SQL content
            if (env('APP_ENV') === 'production' || request()->has('download')) {
                \Log::info('Attempting to download backup for production/Railway');
                
                // Try multiple methods to get file content
                $content = null;
                
                // Method 1: From SQL content in result
                if (isset($result['sql_content']) && !empty($result['sql_content'])) {
                    $content = $result['sql_content'];
                    \Log::info('Using SQL content from result');
                }
                
                // Method 2: From temp file
                if (!$content && isset($result['temp_file']) && file_exists($result['temp_file'])) {
                    $content = file_get_contents($result['temp_file']);
                    \Log::info('Using temp file', ['path' => $result['temp_file']]);
                    @unlink($result['temp_file']); // Clean up temp file
                }
                
                // Method 3: From storage path
                if (!$content) {
                    $path = storage_path('app/backups/' . $result['filename']);
                    if (file_exists($path)) {
                        $content = file_get_contents($path);
                        \Log::info('Using storage path', ['path' => $path]);
                    }
                }
                
                // If we have content, return as download
                if ($content) {
                    \Log::info('Returning backup download', ['size' => strlen($content)]);
                    return response()->streamDownload(function() use ($content) {
                        echo $content;
                    }, $result['filename'], [
                        'Content-Type' => 'application/sql',
                        'Content-Disposition' => 'attachment; filename="' . $result['filename'] . '"',
                    ]);
                }
                
                \Log::error('No content available for download');
                throw new \Exception('Could not retrieve backup content for download');
            }
            
            return redirect()->route('admin.backups.index')->with('success', 'Backup berhasil dibuat: ' . $result['filename']);
        } catch (\Throwable $e) {
            \Log::error('Manual backup failed', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Return user-friendly error
            $errorMsg = 'Backup gagal: ' . $e->getMessage();
            if (env('APP_DEBUG')) {
                $errorMsg .= ' (Line: ' . $e->getLine() . ')';
            }
            
            return redirect()->route('admin.backups.index')->with('error', $errorMsg);
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
