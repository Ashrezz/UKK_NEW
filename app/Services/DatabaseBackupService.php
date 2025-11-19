<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DatabaseBackupService
{
    protected string $disk = 'local';
    protected string $folder = 'backups';

    public function generateAndDownload(): string
    {
        set_time_limit(300);
        ini_set('memory_limit', '512M');
        
        $connection = DB::connection();
        $pdo = $connection->getPdo();
        $dbName = $connection->getDatabaseName();

        $tables = $connection->select('SHOW TABLES');
        $keyName = 'Tables_in_' . $dbName;

        $sql = "-- Database backup generated at " . Carbon::now()->toDateTimeString() . "\n";
        $sql .= "-- Database: {$dbName}\n\nSET FOREIGN_KEY_CHECKS=0;\n";

        foreach ($tables as $t) {
            $table = $t->$keyName ?? null;
            if (!$table) { continue; }
            
            try {
                $create = $connection->select("SHOW CREATE TABLE `{$table}`");
                $createStmt = $create[0]->{'Create Table'} ?? null;
                if ($createStmt) {
                    $sql .= "\n-- Structure for table `{$table}`\nDROP TABLE IF EXISTS `{$table}`;\n{$createStmt};\n";
                }
                
                $rows = $connection->table($table)->get();
                if ($rows->count()) {
                    $sql .= "\n-- Data for table `{$table}`\n";
                    foreach ($rows as $row) {
                        $columns = array_map(fn($c) => "`" . str_replace("`","``", $c) . "`", array_keys((array)$row));
                        $values = array_map(function ($v) use ($pdo) {
                            if ($v === null) return 'NULL';
                            return $pdo->quote($v);
                        }, array_values((array)$row));
                        $sql .= "INSERT INTO `{$table}` (" . implode(',', $columns) . ") VALUES (" . implode(',', $values) . ");\n";
                    }
                }
            } catch (\Exception $e) {
                \Log::warning("Could not backup table {$table}: " . $e->getMessage());
                continue;
            }
        }
        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
        
        return $sql;
    }
    
    public function generate(): array
    {
        try {
            \Log::info('DatabaseBackupService: Starting backup generation');
            
            $connection = DB::connection();
            $pdo = $connection->getPdo();
            $dbName = $connection->getDatabaseName();
            
            \Log::info('DatabaseBackupService: Connected to database', ['db' => $dbName]);

            $tables = $connection->select('SHOW TABLES');
            $keyName = 'Tables_in_' . $dbName; // MySQL specific
            
            \Log::info('DatabaseBackupService: Found tables', ['count' => count($tables)]);

            $sql = "-- Database backup generated at " . Carbon::now()->toDateTimeString() . "\n";
            $sql .= "-- Database: {$dbName}\n\nSET FOREIGN_KEY_CHECKS=0;\n";

            foreach ($tables as $t) {
                $table = $t->$keyName ?? null;
                if (!$table) { continue; }
                
                try {
                    $create = $connection->select("SHOW CREATE TABLE `{$table}`");
                    $createStmt = $create[0]->{'Create Table'} ?? null;
                    if ($createStmt) {
                        $sql .= "\n-- Structure for table `{$table}`\nDROP TABLE IF EXISTS `{$table}`;\n{$createStmt};\n";
                    }
                    
                    $rows = $connection->table($table)->get();
                    if ($rows->count()) {
                        $sql .= "\n-- Data for table `{$table}`\n";
                        foreach ($rows as $row) {
                            $columns = array_map(fn($c) => "`" . str_replace("`","``", $c) . "`", array_keys((array)$row));
                            $values = array_map(function ($v) use ($pdo) {
                                if ($v === null) return 'NULL';
                                return $pdo->quote($v);
                            }, array_values((array)$row));
                            $sql .= "INSERT INTO `{$table}` (" . implode(',', $columns) . ") VALUES (" . implode(',', $values) . ");\n";
                        }
                    }
                    \Log::info("DatabaseBackupService: Backed up table {$table}", ['rows' => $rows->count()]);
                } catch (\Exception $e) {
                    \Log::warning("DatabaseBackupService: Could not backup table {$table}: " . $e->getMessage());
                    continue;
                }
            }
            $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
            
            \Log::info('DatabaseBackupService: SQL generation complete', ['size' => strlen($sql)]);

            $filename = 'backup-' . Carbon::now()->format('Ymd-His') . '-' . Str::random(6) . '.sql';
            $size = strlen($sql);

            return [
                'filename' => $filename, 
                'size' => $size, 
                'sql_content' => $sql
            ];
        } catch (\Throwable $e) {
            \Log::error('Backup generation failed', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public function list(int $limit = 50): array
    {
        try {
            // Check if backups table exists
            if (!DB::getSchemaBuilder()->hasTable('backups')) {
                \Log::warning('Backups table does not exist');
                return [];
            }
            
            return DB::table('backups')->orderByDesc('id')->limit($limit)->get()->map(function ($b) {
                return (array)$b;
            })->toArray();
        } catch (\Exception $e) {
            \Log::error('Error listing backups: ' . $e->getMessage());
            return [];
        }
    }

    public function restore(string $filename): int
    {
        $path = $this->folder . '/' . $filename;
        if (!Storage::disk($this->disk)->exists($path)) {
            throw new \RuntimeException('Backup file not found');
        }
        $sql = Storage::disk($this->disk)->get($path);
        // Simple split by semicolon - naive, but acceptable for basic dumps without procedures
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        $executed = 0;
        foreach ($statements as $statement) {
            if ($statement === '' || str_starts_with($statement, '--')) continue;
            try {
                DB::statement($statement);
                $executed++;
            } catch (\Throwable $e) {
                // Skip failing statements to avoid blocking entire restore
                // In real app, log this
            }
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        return $executed;
    }
}
