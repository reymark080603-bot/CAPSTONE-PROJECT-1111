<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== FINDING BOOK WITH WORKING PDF ===\n";
$book = \App\Models\Book::where('pdf_file', 'like', '%1766064839_694402c752ff3.pdf%')->first();
if ($book) {
    echo "Book ID: {$book->id}\n";
    echo "Title: {$book->title}\n";
    echo "PDF File: {$book->pdf_file}\n";
    echo "Has PDF File: " . ($book->hasPdfFile() ? 'YES' : 'NO') . "\n";
    echo "PDF URL: " . $book->getPdfUrl() . "\n";
} else {
    echo "No book found with that PDF file\n";
}

echo "\n=== ALL BOOKS WITH PDF FILES ===\n";
$books = \App\Models\Book::whereNotNull('pdf_file')->get();
foreach ($books as $book) {
    echo "Book ID: {$book->id} - Title: {$book->title}\n";
    echo "  PDF File: " . basename($book->pdf_file) . "\n";
    echo "  Has File: " . ($book->hasPdfFile() ? 'YES' : 'NO') . "\n";
    echo "  PDF URL: " . $book->getPdfUrl() . "\n";
    echo "\n";
}
