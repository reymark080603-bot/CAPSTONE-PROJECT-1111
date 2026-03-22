<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== EXPECTED PDF FILES ===\n";
$books = \App\Models\Book::whereNotNull('pdf_file')->get();
foreach ($books as $book) {
    echo basename($book->pdf_file) . "\n";
}

echo "\n=== CHECKING IF FILES EXIST ===\n";
foreach ($books as $book) {
    $filename = basename($book->pdf_file);
    $path = storage_path('app/public/books/pdfs/' . $filename);
    $exists = file_exists($path) ? 'YES' : 'NO';
    echo $filename . " - Exists: " . $exists . "\n";
}
