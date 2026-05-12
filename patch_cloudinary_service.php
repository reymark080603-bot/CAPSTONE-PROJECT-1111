<?php
$path = 'app/Services/PdfCoverService.php';
$content = file_get_contents($path);

// Add Cloudinary import
if (strpos($content, 'use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;') === false) {
    $content = str_replace(
        'use Illuminate\Support\Facades\Storage;',
        "use Illuminate\Support\Facades\Storage;\nuse CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;",
        $content
    );
}

// Update generateCover to upload to Cloudinary
$content = preg_replace(
    '/Storage::disk\(\'public\'\)->put\(\$outputPath, \$data\);.*?return \$outputPath;/s',
    'if (env(\'CLOUDINARY_URL\')) {
            $result = Cloudinary::upload("data:image/png;base64," . base64_encode($data), [
                \'folder\' => \'knowly/covers\'
            ]);
            return $result->getSecurePath();
        }
        
        Storage::disk(\'public\')->put($outputPath, $data);
        return $outputPath;',
    $content
);

file_put_contents($path, $content);
echo "Successfully updated PdfCoverService.php for Cloudinary\n";
