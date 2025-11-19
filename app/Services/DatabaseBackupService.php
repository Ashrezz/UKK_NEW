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

    public function generate(): array
    {
        $connection = DB::connection();
        $pdo = $connection->getPdo();
        $dbName = $connection->getDatabaseName();

        $tables = $connection->select('SHOW TABLES');
        $keyName = 'Tables_in_' . $dbName; // MySQL specific

        $sql = "-- Database backup generated at " . Carbon::now()->toDateTimeString() . "\n";
        $sql .= "-- Database: {$dbName}\n\nSET FOREIGN_KEY_CHECKS=0;\n";

        foreach ($tables as $t) {
            $table = $t->$keyName ?? null;
            if (!$table) { continue; }
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
        }
        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

        $filename = 'backup-' . Carbon::now()->format('Ymd-His') . '-' . Str::random(6) . '.sql';
        $path = $this->folder . '/' . $filename;
        Storage::disk($this->disk)->put($path, $sql);
        $size = Storage::disk($this->disk)->size($path);

        DB::table('backups')->insert([
            'filename' => $filename,
            'size_bytes' => $size,
            'driver' => config('database.default'),
            'created_at' => Carbon::now(),
        ]);

        return ['filename' => $filename, 'size' => $size, 'path' => $path];
    }

    public function list(int $limit = 50): array
    {
        return DB::table('backups')->orderByDesc('id')->limit($limit)->get()->map(function ($b) {
            return (array)$b;
        })->toArray();
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
