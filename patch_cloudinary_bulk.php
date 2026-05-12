<?php
$path = 'app/Http/Controllers/BulkUploadController.php';
$content = file_get_contents($path);

// Add Cloudinary import
if (strpos($content, 'use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;') === false) {
    $content = str_replace(
        'use Illuminate\Support\Facades\Storage;',
        "use Illuminate\Support\Facades\Storage;\nuse CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;",
        $content
    );
}

// Update Frontend Thumbnail upload in process()
$content = preg_replace(
    '/if \(Storage::disk\(\'public\'\)->put\(\$coverPath, \$decoded\)\) \{.*?\}/s',
    'if (env(\'CLOUDINARY_URL\')) {
                                                $result = Cloudinary::upload("data:image/$ext;base64,$base64Data", [
                                                    \'folder\' => \'knowly/covers\'
                                                ]);
                                                $coverToSave = $result->getSecurePath();
                                                $coversGenerated++;
                                                $hasFrontendThumb = true;
                                            } else if (Storage::disk(\'public\')->put($coverPath, $decoded)) {
                                                $coverToSave = $coverPath;
                                                $coversGenerated++;
                                                $hasFrontendThumb = true;
                                            }',
    $content
);

file_put_contents($path, $content);
echo "Successfully updated BulkUploadController.php for Cloudinary\n";
