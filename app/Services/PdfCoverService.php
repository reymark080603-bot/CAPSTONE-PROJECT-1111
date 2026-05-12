<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

/**
 * Service class for generating cover images from PDF files.
 * 
 * Uses multiple methods to convert PDF first page to image:
 * 1. Spatie PDF to Image package (if installed)
 * 2. Imagick (PHP extension) - fallback
 * 3. pdftoppm (Poppler utils) - fallback option
 * 
 * Requirements:
 * - spatie/pdf-to-image package (optional but preferred in app flow)
 * - Imagick PHP extension (required by Spatie / fallback) OR
 * - pdftoppm (Poppler utils) - for PDF to image conversion
 * - GD extension - for image resizing
 */
class PdfCoverService
{
    /**
     * Storage directory for PDF e-books
     */
    const PDF_DIR = 'ebooks';
    
    /**
     * Storage directory for cover images
     */
    const COVER_DIR = 'covers';
    
    /**
     * Default cover image path
     */
    const DEFAULT_COVER = 'covers/default-book.png';
    
    /**
     * Cover image dimensions
     */
    const COVER_WIDTH = 400;
    const COVER_HEIGHT = 600;
    
    /**
     * PNG compression level (0-9)
     */
    const PNG_COMPRESSION = 6;

    /**
     * Generate a cover image from a PDF file.
     * 
     * @param string $pdfPath Full path to the PDF file
     * @param string $baseName Base filename for the cover image
     * @return string|false Path to the generated cover image, or false on failure
     */
    public function generateCover(string $pdfPath, string $baseName): string|false
    {
        try {
            // Validate PDF file exists
            if (!file_exists($pdfPath)) {
                Log::error("PDF file not found: {$pdfPath}");
                return false;
            }

            // Validate PDF file is readable
            if (!is_readable($pdfPath)) {
                Log::error("PDF file not readable: {$pdfPath}");
                return false;
            }

            // Generate unique filename for cover
            $coverFilename = $this->generateCoverFilename($baseName);
            $coverPath = Storage::disk('public')->path(self::COVER_DIR . '/' . $coverFilename);

            // Ensure covers directory exists
            $this->ensureCoverDirectoryExists();

            // Try to generate cover - try multiple methods
            $coverImagePath = $this->generateCoverAnyMethod($pdfPath, $coverPath);

            if ($coverImagePath) {
                // Optimize the generated image
                $this->optimizeImage($coverImagePath);
                
                Log::info("Cover generated successfully: {$coverImagePath}");
                return self::COVER_DIR . '/' . $coverFilename;
            }

            // If all methods fail, log and return false to use default
            Log::warning("All cover generation methods failed for PDF: {$pdfPath}");
            return false;

        } catch (\Exception $e) {
            Log::error("Error generating cover for {$pdfPath}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Try multiple methods to generate cover image.
     * 
     * @param string $pdfPath Full path to PDF file
     * @param string $outputPath Full path for output image
     * @return string|false
     */
    private function generateCoverAnyMethod(string $pdfPath, string $outputPath): string|false
    {
        // Method 1: Try Spatie package (if installed)
        $result = $this->generateWithSpatie($pdfPath, $outputPath);
        if ($result) {
            return $result;
        }

        // Method 2: Try Imagick (PHP extension) - no external tools needed
        $result = $this->generateWithImagick($pdfPath, $outputPath);
        if ($result) {
            return $result;
        }

        // Method 3: Try pdftoppm command line tool
        $result = $this->generateWithPdftoppm($pdfPath, $outputPath);
        if ($result) {
            return $result;
        }

        return false;
    }

    /**
     * Generate cover image using spatie/pdf-to-image package.
     *
     * @param string $pdfPath Full path to PDF file
     * @param string $outputPath Full path for output image
     * @return string|false
     */
    private function generateWithSpatie(string $pdfPath, string $outputPath): string|false
    {
        if (!class_exists(\Spatie\PdfToImage\Pdf::class)) {
            Log::debug('spatie/pdf-to-image is not installed, trying other methods');
            return false;
        }

        try {
            $pdf = new \Spatie\PdfToImage\Pdf($pdfPath);

            // Handle API differences between package versions.
            if (method_exists($pdf, 'selectPage')) {
                $pdf->selectPage(1);
            } elseif (method_exists($pdf, 'setPage')) {
                $pdf->setPage(1);
            }

            if (method_exists($pdf, 'resolution')) {
                $pdf->resolution(150);
            }

            if (method_exists($pdf, 'save')) {
                $pdf->save($outputPath);
            } elseif (method_exists($pdf, 'saveImage')) {
                $pdf->saveImage($outputPath);
            } else {
                Log::debug('Unsupported spatie/pdf-to-image API version');
                return false;
            }

            if (!file_exists($outputPath)) {
                return false;
            }

            $this->resizeImage($outputPath);
            Log::info("Cover generated with spatie/pdf-to-image: {$outputPath}");

            return $outputPath;
        } catch (\Throwable $e) {
            Log::debug('Spatie PDF conversion failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate cover image using Imagick PHP extension.
     * This is the preferred method as it doesn't require external tools.
     * 
     * @param string $pdfPath Full path to PDF file
     * @param string $outputPath Full path for output image
     * @return string|false
     */
    private function generateWithImagick(string $pdfPath, string $outputPath): string|false
    {
        // Check if Imagick extension is available
        if (!extension_loaded('imagick')) {
            Log::debug('Imagick extension not available, trying other methods');
            return false;
        }

        try {
            // Read the PDF first page
            $imagick = new \Imagick();
            $imagick->setResolution(150, 150);
            $imagick->readImage($pdfPath . '[0]'); // [0] = first page only
            
            // Set format to PNG
            $imagick->setImageFormat('png');
            
            // Set image background to white (for transparency handling)
            $imagick->setImageBackgroundColor('white');
            
            // Flatten image (removes transparency)
            $imagick = $imagick->flattenImages();
            
            // Resize to standard cover dimensions (maintain aspect ratio)
            $imagick->thumbnailImage(self::COVER_WIDTH, self::COVER_HEIGHT, true);
            
            // Write to file
            $result = $imagick->writeImage($outputPath);
            
            // Clean up
            $imagick->clear();
            $imagick->destroy();
            
            if ($result && file_exists($outputPath)) {
                Log::info("Cover generated with Imagick: {$outputPath}");
                return $outputPath;
            }
            
            return false;
        } catch (\Exception $e) {
            Log::debug("Imagick failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate cover image using pdftoppm command-line tool.
     * 
     * @param string $pdfPath Full path to PDF file
     * @param string $outputPath Full path for output image
     * @return string|false
     */
    private function generateWithPdftoppm(string $pdfPath, string $outputPath): string|false
    {
        // Check if pdftoppm is available
        if (!$this->isPdftoppmAvailable()) {
            Log::debug('pdftoppm is not available on this system');
            return false;
        }

        // Remove extension from output path for pdftoppm
        $outputBase = preg_replace('/\.(png|jpg|jpeg)$/i', '', $outputPath);

        // Build pdftoppm command
        // -f 1: First page only
        // -singlefile: Output single file
        // -png: Output as PNG
        // -r 150: Resolution (DPI)
        $command = sprintf(
            'pdftoppm -f 1 -singlefile -png -r 150 %s %s 2>&1',
            escapeshellarg($pdfPath),
            escapeshellarg($outputBase)
        );

        // Execute command
        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            Log::debug("pdftoppm command failed with code {$returnCode}");
            return false;
        }

        // Check if output file was created
        $pngPath = $outputBase . '.png';
        if (!file_exists($pngPath)) {
            Log::debug("pdftoppm did not create output file");
            return false;
        }

        // Resize and optimize the image
        try {
            $this->resizeImage($pngPath);
            return $pngPath;
        } catch (\Exception $e) {
            Log::debug("Failed to resize/optimize cover image: " . $e->getMessage());
            // Return the original even if resize failed
            return file_exists($pngPath) ? $pngPath : false;
        }
    }

    /**
     * Check if pdftoppm command is available on the system.
     * 
     * @return bool
     */
    public function isPdftoppmAvailable(): bool
    {
        $output = [];
        $returnCode = 0;
        exec('pdftoppm -v 2>&1', $output, $returnCode);
        
        return $returnCode === 0;
    }

    /**
     * Check if Imagick extension is available.
     * 
     * @return bool
     */
    public function isImagickAvailable(): bool
    {
        return extension_loaded('imagick');
    }

    /**
     * Check if spatie/pdf-to-image package is available.
     *
     * @return bool
     */
    public function isSpatiePdfToImageAvailable(): bool
    {
        return class_exists(\Spatie\PdfToImage\Pdf::class);
    }

    /**
     * Resize and optimize the generated cover image.
     * Uses GD library for image resizing.
     * 
     * @param string $imagePath Path to the image file
     */
    private function resizeImage(string $imagePath): void
    {
        // Use GD library for resizing
        $this->resizeWithGd($imagePath);
    }

    /**
     * Resize image using native GD library.
     * 
     * @param string $imagePath Path to the image file
     */
    private function resizeWithGd(string $imagePath): void
    {
        // Get image info
        $imageInfo = getimagesize($imagePath);
        if (!$imageInfo) {
            return;
        }

        // Create image resource based on type
        switch ($imageInfo[2]) {
            case IMAGETYPE_PNG:
                $source = imagecreatefrompng($imagePath);
                break;
            case IMAGETYPE_JPEG:
                $source = imagecreatefromjpeg($imagePath);
                break;
            default:
                return;
        }

        if (!$source) {
            return;
        }

        // Calculate dimensions maintaining aspect ratio
        $srcWidth = imagesx($source);
        $srcHeight = imagesy($source);
        
        // Calculate new dimensions (cover fit)
        $ratio = max(self::COVER_WIDTH / $srcWidth, self::COVER_HEIGHT / $srcHeight);
        $newWidth = (int) round($srcWidth * $ratio);
        $newHeight = (int) round($srcHeight * $ratio);

        // Create new image
        $dest = imagecreatetruecolor(self::COVER_WIDTH, self::COVER_HEIGHT);
        
        // Fill with white background (for transparency handling)
        $white = imagecolorallocate($dest, 255, 255, 255);
        imagefill($dest, 0, 0, $white);

        // Resample
        imagecopyresampled(
            $dest, $source,
            (self::COVER_WIDTH - $newWidth) / 2,
            (self::COVER_HEIGHT - $newHeight) / 2,
            0, 0,
            $newWidth, $newHeight,
            $srcWidth, $srcHeight
        );

        // Save
        imagepng($dest, $imagePath, 9);
        
        // Free memory
        imagedestroy($source);
        imagedestroy($dest);
    }

    /**
     * Optimize the generated image (basic compression).
     * 
     * @param string $imagePath Path to the image file
     */
    private function optimizeImage(string $imagePath): void
    {
        // Try to optimize with optipng if available
        $this->runExternalOptimizer('optipng', $imagePath);
        
        // Try to optimize with jpegoptim if available (for jpg files)
        if (preg_match('/\.(jpg|jpeg)$/i', $imagePath)) {
            $this->runExternalOptimizer('jpegoptim', $imagePath);
        }
    }

    /**
     * Run an external image optimizer if available.
     * 
     * @param string $optimizer Optimizer command name
     * @param string $imagePath Path to the image file
     */
    private function runExternalOptimizer(string $optimizer, string $imagePath): void
    {
        $command = "{$optimizer} -o2 " . escapeshellarg($imagePath) . " 2>&1";
        exec($command, $output, $returnCode);
        
        // Log if optimizer ran (success or failure doesn't matter much)
        if ($returnCode === 0) {
            Log::info("{$optimizer} optimized: {$imagePath}");
        }
    }

    /**
     * Generate a unique filename for the cover image.
     * 
     * @param string $baseName Base name to use
     * @return string
     */
    private function generateCoverFilename(string $baseName): string
    {
        // Sanitize the base name
        $sanitized = preg_replace('/[^a-zA-Z0-9_-]/', '_', $baseName);
        $sanitized = substr($sanitized, 0, 50); // Limit length
        
        // Add unique identifier
        return $sanitized . '_' . time() . '_' . substr(md5(uniqid()), 0, 8) . '.png';
    }

    /**
     * Ensure the covers directory exists in public folder.
     */
    private function ensureCoverDirectoryExists(): void
    {
        if (!Storage::disk('public')->exists(self::COVER_DIR)) {
            Storage::disk('public')->makeDirectory(self::COVER_DIR);
            Log::info("Created covers directory on public disk: " . self::COVER_DIR);
        }
    }

    /**
     * Delete a cover image file.
     * 
     * @param string $coverPath Relative path to cover image
     * @return bool
     */
    public function deleteCover(string $coverPath): bool
    {
        $normalized = ltrim($coverPath, '/');

        if (Storage::disk('public')->exists($normalized)) {
            return Storage::disk('public')->delete($normalized);
        }

        if (str_starts_with($normalized, 'storage/')) {
            return Storage::disk('public')->delete(substr($normalized, 8));
        }

        return false;
    }

    /**
     * Get the default cover image path.
     * 
     * @return string
     */
    public function getDefaultCover(): string
    {
        return self::DEFAULT_COVER;
    }

    /**
     * Check if a cover image exists, otherwise return default.
     * 
     * @param string|null $coverPath Relative path to cover
     * @return string
     */
    public function getCoverUrl(?string $coverPath): string
    {
        if (empty($coverPath)) {
            return asset('storage/' . self::DEFAULT_COVER);
        }

        $normalized = ltrim($coverPath, '/');

        if (Storage::disk('public')->exists($normalized)) {
            return asset('storage/' . $normalized);
        }

        if (str_starts_with($normalized, 'storage/')) {
            $relative = substr($normalized, 8);
            if (Storage::disk('public')->exists($relative)) {
                return asset('storage/' . $relative);
            }
        }

        return asset('storage/' . self::DEFAULT_COVER);
    }

    /**
     * Get storage information about PDFs and covers.
     * 
     * @return array
     */
    public function getStorageInfo(): array
    {
        $pdfDir = Storage::disk('public')->path(self::PDF_DIR);
        $coverDir = Storage::disk('public')->path(self::COVER_DIR);

        $pdfCount = 0;
        $pdfSize = 0;
        $coverCount = 0;
        $coverSize = 0;

        // Count PDFs
        if (is_dir($pdfDir)) {
            $pdfFiles = glob($pdfDir . '/*.pdf');
            $pdfCount = count($pdfFiles);
            foreach ($pdfFiles as $file) {
                $pdfSize += filesize($file);
            }
        }

        // Count covers
        if (is_dir($coverDir)) {
            $coverFiles = glob($coverDir . '/*.{png,jpg,jpeg}', GLOB_BRACE);
            $coverCount = count($coverFiles);
            foreach ($coverFiles as $file) {
                $coverSize += filesize($file);
            }
        }

        return [
            'pdf' => [
                'count' => $pdfCount,
                'size_bytes' => $pdfSize,
                'size_mb' => round($pdfSize / 1024 / 1024, 2),
            ],
            'covers' => [
                'count' => $coverCount,
                'size_bytes' => $coverSize,
                'size_mb' => round($coverSize / 1024 / 1024, 2),
            ],
            'pdftoppm_available' => $this->isPdftoppmAvailable(),
            'imagick_available' => $this->isImagickAvailable(),
            'spatie_pdf_to_image_available' => $this->isSpatiePdfToImageAvailable(),
        ];
    }
}
