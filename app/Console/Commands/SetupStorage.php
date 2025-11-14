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
        if (!File::isDirectory($storageAppPublic)) {
            File::makeDirectory($storageAppPublic, 0755, true);
            $this->info('Created directory: ' . $storageAppPublic);
        }

        // Check if link already exists
        if (File::isLink($storageLink)) {
            if ($this->option('force')) {
                File::delete($storageLink);
                $this->info('Removed existing symlink.');
            } else {
                $this->info('Symlink already exists at: ' . $storageLink);
                return 0;
            }
        } elseif (File::exists($storageLink)) {
            if (!$this->option('force')) {
                $this->warn('Directory already exists at: ' . $storageLink);
                $this->line('Use --force to overwrite.');
                return 1;
            }
            File::deleteDirectory($storageLink);
            $this->info('Removed existing directory.');
        }

        // Create subdirectories for bukti_pembayaran
        $buktiDir = $storageAppPublic . '/bukti_pembayaran';
        if (!File::isDirectory($buktiDir)) {
            File::makeDirectory($buktiDir, 0755, true);
            $this->info('Created directory: ' . $buktiDir);
        }

        // Create symlink
        try {
            File::link($storageAppPublic, $storageLink);
            $this->info('Symlink created successfully!');
            $this->info('From: ' . $storageAppPublic);
            $this->info('To: ' . $storageLink);
            return 0;
        } catch (\Throwable $e) {
            $this->error('Failed to create symlink: ' . $e->getMessage());
            $this->warn('Note: On some systems (Windows, some shared hosts), symlinks may fail.');
            $this->line('The application will still serve files via the /pembayaran/bukti route.');
            return 1;
        }
    }
}
