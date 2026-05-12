<?php
$path = 'app/Http/Controllers/RecentBooksController.php';
$content = file_get_contents($path);

// Add Cloudinary import
if (strpos($content, 'use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;') === false) {
    $content = str_replace(
        'use Illuminate\Support\Facades\Storage;',
        "use Illuminate\Support\Facades\Storage;\nuse CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;",
        $content
    );
}

// Update upload method to use Cloudinary
$content = preg_replace(
    '/\$coverPath = \$coverImage->storeAs\(\'covers\', \$coverFilename, \'public\'\);/s',
    'if (env(\'CLOUDINARY_URL\')) {
                    $result = Cloudinary::upload($coverImage->getRealPath(), [
                        \'folder\' => \'knowly/covers\'
                    ]);
                    $coverPath = $result->getSecurePath();
                } else {
                    $coverPath = $coverImage->storeAs(\'covers\', $coverFilename, \'public\');
                }',
    $content
);

file_put_contents($path, $content);
echo "Successfully updated RecentBooksController.php for Cloudinary\n";
