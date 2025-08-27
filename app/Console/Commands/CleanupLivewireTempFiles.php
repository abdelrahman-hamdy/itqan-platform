<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class CleanupLivewireTempFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'livewire:cleanup-temp {--force : Force cleanup without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up Livewire temporary files and ensure proper directory structure';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧹 Cleaning up Livewire temporary files...');

        // Ensure storage directories exist
        $this->ensureDirectoriesExist();

        // Clean up old temporary files
        $this->cleanupTempFiles();

        // Set proper permissions
        $this->setPermissions();

        $this->info('✅ Livewire cleanup completed successfully!');
    }

    private function ensureDirectoriesExist(): void
    {
        $directories = [
            storage_path('app/livewire-tmp'),
            storage_path('app/public'),
            storage_path('app/public/course-thumbnails'),
            storage_path('app/public/course-materials'),
            storage_path('app/public/lessons/videos'),
        ];

        foreach ($directories as $directory) {
            if (! File::exists($directory)) {
                File::makeDirectory($directory, 0755, true);
                $this->line("📁 Created directory: {$directory}");
            }
        }
    }

    private function cleanupTempFiles(): void
    {
        $tempDir = storage_path('app/livewire-tmp');

        if (! File::exists($tempDir)) {
            return;
        }

        $files = File::files($tempDir);
        $count = 0;

        foreach ($files as $file) {
            // Remove files older than 24 hours
            if (time() - $file->getMTime() > 86400) {
                File::delete($file->getPathname());
                $count++;
            }
        }

        if ($count > 0) {
            $this->line("🗑️  Removed {$count} old temporary files");
        } else {
            $this->line('✨ No old temporary files found');
        }
    }

    private function setPermissions(): void
    {
        $directories = [
            storage_path('app/livewire-tmp'),
            storage_path('app/public'),
            storage_path('app/public/course-thumbnails'),
            storage_path('app/public/course-materials'),
            storage_path('app/public/lessons/videos'),
        ];

        foreach ($directories as $directory) {
            if (File::exists($directory)) {
                chmod($directory, 0755);
            }
        }

        $this->line('🔐 Set proper permissions on directories');
    }
}
