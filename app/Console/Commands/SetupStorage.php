<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SetupStorage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'storage:setup {--force : Overwrite existing link}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup storage link and directories for file uploads. Works on Railway and other platforms.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $storageLink = public_path('storage');
        $storageAppPublic = storage_path('app/public');

        // Create storage/app/public directory if it doesn't exist
        if (!is_dir($storageAppPublic)) {
            File::makeDirectory($storageAppPublic, 0755, true);
            $this->info('Created directory: ' . $storageAppPublic);
        }

        // Check if link already exists using is_link (built-in PHP function)
        if (is_link($storageLink)) {
            if ($this->option('force')) {
                @unlink($storageLink);
                $this->info('Removed existing symlink.');
            } else {
                $this->info('Symlink already exists at: ' . $storageLink);
                return 0;
            }
        } elseif (is_dir($storageLink) || file_exists($storageLink)) {
            if (!$this->option('force')) {
                $this->warn('Directory/file already exists at: ' . $storageLink);
                $this->line('Use --force to overwrite.');
                return 1;
            }
            if (is_dir($storageLink)) {
                File::deleteDirectory($storageLink);
            } else {
                @unlink($storageLink);
            }
            $this->info('Removed existing directory/file.');
        }

        // Create subdirectories for bukti_pembayaran
        $buktiDir = $storageAppPublic . '/bukti_pembayaran';
        if (!is_dir($buktiDir)) {
            File::makeDirectory($buktiDir, 0755, true);
            $this->info('Created directory: ' . $buktiDir);
        }

        // Create symlink
        try {
            symlink($storageAppPublic, $storageLink);
            $this->info('Symlink created successfully!');
            $this->info('From: ' . $storageAppPublic);
            $this->info('To: ' . $storageLink);
            return 0;
        } catch (\Throwable $e) {
            $this->error('Failed to create symlink: ' . $e->getMessage());
            $this->warn('Note: On some systems (Windows, some shared hosts), symlinks may fail.');
            $this->line('The application will still serve files via the /pembayaran/bukti route.');
            return 0; // Return 0 (success) because the app works without symlink via controller route
        }
    }
}
