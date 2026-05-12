<?php
$path = 'app/Http/Controllers/LibrarianController.php';
$content = file_get_contents($path);

// Add Storage import if missing
if (strpos($content, 'use Illuminate\Support\Facades\Storage;') === false) {
    $content = str_replace(
        'use App\Services\LibrarianNotificationService;',
        "use App\Services\LibrarianNotificationService;\nuse Illuminate\Support\Facades\Storage;",
        $content
    );
}

// Replace storeCoverPhoto body
$content = preg_replace(
    '/private function storeCoverPhoto\(\$file\)\s*\{.*?\}/s',
    'private function storeCoverPhoto($file)
    {
        $path = $file->store(\'uploads/book-covers\', \'public\');
        return \'storage/\' . $path;
    }',
    $content
);

// Replace storeEbookFile body
$content = preg_replace(
    '/private function storeEbookFile\(\$file, \$type\)\s*\{.*?\}/s',
    'private function storeEbookFile($file, $type)
    {
        $path = $file->store(\'books/\' . $type . \'s\', \'public\');
        return \'storage/\' . $path;
    }',
    $content
);

file_put_contents($path, $content);
echo "Successfully patched LibrarianController.php\n";
