<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * Command to generate a default book cover image.
 * This creates a simple placeholder image that is used when
 * PDF cover generation fails.
 */
class GenerateDefaultCover extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'covers:generate-default
                            {--force : Overwrite existing default cover}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a default book cover image';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $coverDir = Storage::disk('public')->path('covers');
        $defaultCoverPath = $coverDir . DIRECTORY_SEPARATOR . 'default-book.png';

        // Check if cover directory exists
        if (!Storage::disk('public')->exists('covers')) {
            Storage::disk('public')->makeDirectory('covers');
            $this->info("Created covers directory: {$coverDir}");
        }

        // Check if default cover already exists
        if (File::exists($defaultCoverPath) && !$this->option('force')) {
            $this->info('Default cover already exists. Use --force to overwrite.');
            return;
        }

        // Generate the default cover image
        $this->info('Generating default book cover...');

        try {
            $this->generateCoverImage($defaultCoverPath);
            $this->info("Default cover created: {$defaultCoverPath}");
        } catch (\Exception $e) {
            $this->error("Failed to create default cover: " . $e->getMessage());
        }
    }

    /**
     * Generate a simple default cover image using GD library.
     *
     * @param string $path Output path for the image
     */
    protected function generateCoverImage(string $path): void
    {
        // Image dimensions (3:4 aspect ratio)
        $width = 400;
        $height = 600;

        // Create image
        $image = imagecreatetruecolor($width, $height);

        // Colors
        $bgColor = imagecolorallocate($image, 240, 240, 245); // Light gray-blue
        $bookColor = imagecolorallocate($image, 70, 130, 180); // Steel blue
        $textColor = imagecolorallocate($image, 255, 255, 255); // White
        $accentColor = imagecolorallocate($image, 100, 149, 237); // Cornflower blue

        // Fill background
        imagefill($image, 0, 0, $bgColor);

        // Draw book spine (left side)
        imagefilledrectangle($image, 30, 40, 50, 560, $accentColor);

        // Draw book body
        imagefilledrectangle($image, 50, 40, 370, 560, $bookColor);

        // Draw accent stripe
        imagefilledrectangle($image, 50, 80, 370, 100, $accentColor);

        // Add "BOOK" text (simple representation using rectangles)
        // Title area
        imagefilledrectangle($image, 80, 200, 340, 210, $textColor);
        imagefilledrectangle($image, 80, 220, 320, 230, $textColor);

        // Author area
        imagefilledrectangle($image, 80, 350, 250, 355, $textColor);

        // Add decorative elements
        // Corner ornaments
        imagefilledrectangle($image, 60, 50, 70, 60, $textColor);
        imagefilledrectangle($image, 350, 50, 360, 60, $textColor);
        imagefilledrectangle($image, 60, 540, 70, 550, $textColor);
        imagefilledrectangle($image, 350, 540, 360, 550, $textColor);

        // Save as PNG
        imagepng($image, $path, 6);

        // Free memory
        imagedestroy($image);

        $this->info("Cover image generated successfully!");
    }
}

