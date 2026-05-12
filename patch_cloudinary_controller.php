<?php
$path = 'app/Http/Controllers/LibrarianController.php';
$content = file_get_contents($path);

// Add Cloudinary import
if (strpos($content, 'use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;') === false) {
    $content = str_replace(
        'use Illuminate\Support\Facades\Storage;',
        "use Illuminate\Support\Facades\Storage;\nuse CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;",
        $content
    );
}

// Update storeCoverPhoto to use Cloudinary
$content = preg_replace(
    '/private function storeCoverPhoto\(\$file\)\s*\{.*?\}/s',
    'private function storeCoverPhoto($file)
    {
        try {
            // If Cloudinary is configured, use it
            if (env(\'CLOUDINARY_URL\')) {
                $result = Cloudinary::upload($file->getRealPath(), [
                    \'folder\' => \'knowly/covers\'
                ]);
                return $result->getSecurePath();
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error(\'Cloudinary Upload Failed: \' . $e->getMessage());
        }

        // Fallback to local storage
        $path = $file->store(\'uploads/book-covers\', \'public\');
        return \'storage/\' . $path;
    }',
    $content
);

file_put_contents($path, $content);
echo "Successfully updated LibrarianController.php for Cloudinary\n";
