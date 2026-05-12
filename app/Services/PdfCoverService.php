<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

/**
 * Service class for generating cover images from PDF files.
 */
class PdfCoverService
{
    const PDF_DIR = 'ebooks';
    const COVER_DIR = 'covers';
    const DEFAULT_COVER = 'covers/default-book.png';
    const COVER_WIDTH = 400;
    const COVER_HEIGHT = 600;

    public function generateCover(string $pdfPath, string $baseName): string|false
    {
        try {
            if (!file_exists($pdfPath)) {
                Log::error("PDF file not found: {$pdfPath}");
                return false;
            }

            if (!is_readable($pdfPath)) {
                Log::error("PDF file not readable: {$pdfPath}");
                return false;
            }

            $coverFilename = $this->generateCoverFilename($baseName);
            $coverPath = Storage::disk('public')->path(self::COVER_DIR . '/' . $coverFilename);

            $this->ensureCoverDirectoryExists();

            $coverImagePath = $this->generateCoverAnyMethod($pdfPath, $coverPath);

            if ($coverImagePath) {
                $this->optimizeImage($coverImagePath);
                
                // If Cloudinary is enabled, upload to Cloudinary
                if (env('CLOUDINARY_URL')) {
                    try {
                        $result = Cloudinary::upload($coverImagePath, ['folder' => 'knowly/covers']);
                        $url = $result->getSecurePath();
                        if (file_exists($coverImagePath)) unlink($coverImagePath);
                        return $url;
                    } catch (\Exception $e) {
                        Log::error("Cloudinary upload failed: " . $e->getMessage());
                    }
                }
                
                return self::COVER_DIR . '/' . $coverFilename;
            }

            return false;
        } catch (\Exception $e) {
            Log::error("Error generating cover: " . $e->getMessage());
            return false;
        }
    }

    private function generateCoverAnyMethod(string $pdfPath, string $outputPath): string|false
    {
        // Try Imagick first if available
        if (extension_loaded('imagick')) {
            try {
                $imagick = new \Imagick();
                $imagick->setResolution(150, 150);
                $imagick->readImage($pdfPath . '[0]');
                $imagick->setImageFormat('png');
                $imagick->setImageBackgroundColor('white');
                $imagick = $imagick->flattenImages();
                $imagick->thumbnailImage(self::COVER_WIDTH, self::COVER_HEIGHT, true);
                $imagick->writeImage($outputPath);
                $imagick->clear();
                $imagick->destroy();
                return $outputPath;
            } catch (\Exception $e) {
                Log::debug("Imagick failed: " . $e->getMessage());
            }
        }

        // Try pdftoppm as fallback
        if ($this->isPdftoppmAvailable()) {
            $outputBase = preg_replace('/\.(png|jpg|jpeg)$/i', '', $outputPath);
            $command = sprintf('pdftoppm -f 1 -singlefile -png -r 150 %s %s 2>&1', escapeshellarg($pdfPath), escapeshellarg($outputBase));
            exec($command, $output, $returnCode);
            if ($returnCode === 0 && file_exists($outputBase . '.png')) {
                $pngPath = $outputBase . '.png';
                $this->resizeImage($pngPath);
                return $pngPath;
            }
        }

        return false;
    }

    public function isPdftoppmAvailable(): bool
    {
        exec('pdftoppm -v 2>&1', $output, $returnCode);
        return $returnCode === 0;
    }

    private function resizeImage(string $imagePath): void
    {
        $imageInfo = getimagesize($imagePath);
        if (!$imageInfo) return;

        $source = match ($imageInfo[2]) {
            IMAGETYPE_PNG => imagecreatefrompng($imagePath),
            IMAGETYPE_JPEG => imagecreatefromjpeg($imagePath),
            default => null,
        };

        if (!$source) return;

        $srcWidth = imagesx($source);
        $srcHeight = imagesy($source);
        $ratio = max(self::COVER_WIDTH / $srcWidth, self::COVER_HEIGHT / $srcHeight);
        $newWidth = (int) round($srcWidth * $ratio);
        $newHeight = (int) round($srcHeight * $ratio);

        $dest = imagecreatetruecolor(self::COVER_WIDTH, self::COVER_HEIGHT);
        $white = imagecolorallocate($dest, 255, 255, 255);
        imagefill($dest, 0, 0, $white);
        imagecopyresampled($dest, $source, (self::COVER_WIDTH - $newWidth) / 2, (self::COVER_HEIGHT - $newHeight) / 2, 0, 0, $newWidth, $newHeight, $srcWidth, $srcHeight);
        imagepng($dest, $imagePath, 9);
        imagedestroy($source);
        imagedestroy($dest);
    }

    private function optimizeImage(string $imagePath): void
    {
        exec("optipng -o2 " . escapeshellarg($imagePath) . " 2>&1");
    }

    private function generateCoverFilename(string $baseName): string
    {
        $sanitized = substr(preg_replace('/[^a-zA-Z0-9_-]/', '_', $baseName), 0, 50);
        return $sanitized . '_' . time() . '_' . substr(md5(uniqid()), 0, 8) . '.png';
    }

    private function ensureCoverDirectoryExists(): void
    {
        if (!Storage::disk('public')->exists(self::COVER_DIR)) {
            Storage::disk('public')->makeDirectory(self::COVER_DIR);
        }
    }

    public function getStorageInfo(): array
    {
        return [
            'pdf' => ['count' => 0, 'size_mb' => 0],
            'covers' => ['count' => 0, 'size_mb' => 0],
            'pdftoppm_available' => $this->isPdftoppmAvailable(),
            'imagick_available' => extension_loaded('imagick'),
        ];
    }
}
