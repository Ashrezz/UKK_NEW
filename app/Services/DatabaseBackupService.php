<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;


use App\Services\SupabaseStorageService;

class DatabaseBackupService
{
    protected string $disk = 'local';
    protected string $folder = 'backups';

    public function generateAndDownload(): string
    {
        try {
            set_time_limit(300);
            ini_set('memory_limit', '512M');

            $connection = DB::connection();

            if (!$connection) {
                throw new \Exception('Database connection failed');
            }

            $pdo = $connection->getPdo();
            $dbName = $connection->getDatabaseName();

            if (empty($dbName)) {
                throw new \Exception('Database name is empty');
            }

            $tables = $connection->select('SHOW TABLES');

            if (empty($tables)) {
                throw new \Exception('No tables found in database');
            }

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
                    $sql .= "\n-- Error backing up table {$table}: " . $e->getMessage() . "\n";
                    continue;
                }
            }
            $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

            if (strlen($sql) < 100) {
                throw new \Exception('Backup SQL is too short, something went wrong');
            }

            return $sql;
        } catch (\Throwable $e) {
            \Log::error('generateAndDownload failed', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
            throw new \Exception('Backup generation failed: ' . $e->getMessage());
        }
    }

    public function generate(bool $uploadToSupabase = false): array
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
                            $columns = array_map(function($c) { return "`" . str_replace("`","``", $c) . "`"; }, array_keys((array)$row));
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

            $uploadResult = null;
            if ($uploadToSupabase) {
                $supabase = new SupabaseStorageService();
                $uploadResult = $supabase->upload($filename, $sql);
                \Log::info('Supabase upload', ['filename' => $filename, 'success' => $uploadResult['success'] ?? false]);
            }

            return [
                'filename' => $filename,
                'size' => $size,
                'sql_content' => $sql,
                'upload' => $uploadResult,
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

    public function restore(string $filename): int
    {
        $path = storage_path('app/backups/' . $filename);
        if (!file_exists($path)) {
            throw new \Exception('Backup file not found: ' . $filename);
        }

        $sql = file_get_contents($path);
        if ($sql === false || strlen($sql) < 10) {
            throw new \Exception('Backup file is empty or unreadable');
        }

        $connection = DB::connection();
        $pdo = $connection->getPdo();
        $statements = [];
        $buffer = '';
        $inString = false;
        $stringChar = '';

        // Simple SQL splitter that tries to respect quotes
        $length = strlen($sql);
        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];
            $nextChar = $i + 1 < $length ? $sql[$i + 1] : '';

            if ($inString) {
                if ($char === $stringChar) {
                    // Check escaped quote
                    $escaped = $buffer !== '' && substr($buffer, -1) === '\\';
                    if (!$escaped) {
                        $inString = false;
                        $stringChar = '';
                    }
                }
                $buffer .= $char;
                continue;
            } else {
                if ($char === '\'' || $char === '"') {
                    $inString = true;
                    $stringChar = $char;
                    $buffer .= $char;
                    continue;
                }
            }

            // Handle comment lines starting with --
            if ($char === '-' && $nextChar === '-' ) {
                // Skip until end of line
                while ($i < $length && $sql[$i] !== "\n") { $buffer .= $sql[$i]; $i++; }
                continue;
            }

            if ($char === ';') {
                $trimmed = trim($buffer);
                if ($trimmed !== '' && !str_starts_with($trimmed, '--')) {
                    $statements[] = $trimmed;
                }
                $buffer = '';
            } else {
                $buffer .= $char;
            }
        }

        $pdo->beginTransaction();
        $count = 0;
        try {
            foreach ($statements as $stmt) {
                try {
                    $pdo->exec($stmt);
                    $count++;
                } catch (\Throwable $e) {
                    \Log::warning('Restore statement failed', ['error' => $e->getMessage(), 'statement' => substr($stmt, 0, 200)]);
                }
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw new \Exception('Restore failed: ' . $e->getMessage());
        }
        return $count;

