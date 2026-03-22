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
    protected $signature = 'storage:setup
                            {--create-dirs : Create storage directories}
                            {--link : Create symbolic link for public storage}
                            {--all : Run all setup tasks}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup storage directories and symbolic links for e-books and covers';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $runAll = $this->option('all');
        $createDirs = $this->option('create-dirs') || $runAll;
        $createLink = $this->option('link') || $runAll;

        if (!$createDirs && !$createLink) {
            $this->info('Please specify an option: --create-dirs, --link, or --all');
            $this->info('Example: php artisan storage:setup --all');
            return;
        }

        $this->info('Setting up storage for E-Resources System...');
        $this->newLine();

        // Create directories
        if ($createDirs) {
            $this->createDirectories();
        }

        // Create symbolic link
        if ($createLink) {
            $this->createSymbolicLink();
        }

        $this->newLine();
        $this->info('Storage setup completed!');
    }

    /**
     * Create necessary storage directories.
     */
    protected function createDirectories(): void
    {
        $this->info('Creating storage directories...');

        $directories = [
            public_path('ebooks'),
            public_path('covers'),
            storage_path('app/public/ebooks'),
            storage_path('app/public/covers'),
        ];

        foreach ($directories as $dir) {
            if (!File::exists($dir)) {
                File::makeDirectory($dir, 0755, true);
                $this->line("  Created: {$dir}");
            } else {
                $this->line("  Exists:  {$dir}");
            }
        }

        $this->info('Directories created successfully.');
        $this->newLine();
    }

    /**
     * Create symbolic link for public storage.
     */
    protected function createSymbolicLink(): void
    {
        $this->info('Setting up symbolic link...');

        $linkPath = public_path('storage');
        $targetPath = storage_path('app/public');

        // Remove existing link if it exists
        if (File::exists($linkPath) && is_link($linkPath)) {
            unlink($linkPath);
            $this->line("  Removed existing link: {$linkPath}");
        }

        // Check if target directory exists
        if (!File::exists($targetPath)) {
            $this->error("Target directory does not exist: {$targetPath}");
            return;
        }

        // Create symlink (Windows requires admin privileges)
        if (PHP_OS_FAMILY === 'Windows') {
            // On Windows, use junction or admin privileges needed for symlink
            // For development, we can copy instead
            $this->warn('Windows detected. Symlinks may require administrator privileges.');
            $this->warn('If link creation fails, the storage will still work via direct paths.');
        }

        try {
            if (symlink($targetPath, $linkPath)) {
                $this->line("  Created link: {$linkPath} -> {$targetPath}");
                $this->info('Symbolic link created successfully.');
            } else {
                $this->warn('Failed to create symbolic link. This is common on Windows.');
                $this->info('Storage will still work - files are accessible via direct paths.');
            }
        } catch (\Exception $e) {
            $this->warn('Could not create symbolic link: ' . $e->getMessage());
            $this->info('Storage will still work - files are accessible via direct paths.');
        }
    }
}

