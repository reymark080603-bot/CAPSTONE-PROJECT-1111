<?php
$path = 'app/Models/Book.php';
$content = file_get_contents($path);

// Update ALL asset('storage/...') calls to asset('library-assets/...')
$content = str_replace(
    "asset('storage/",
    "asset('library-assets/",
    $content
);

// Specifically handle cases where 'storage/' is part of a dynamic string
$content = str_replace(
    "return asset('library-assets/' . \$normalized);",
    "return asset('library-assets/' . \$normalized);", // already done but being sure
    $content
);

file_put_contents($path, $content);
echo "Successfully updated Book.php fallback URLs\n";
