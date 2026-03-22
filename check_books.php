<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== BOOKS TABLE STRUCTURE ===\n";
$columns = \Illuminate\Support\Facades\Schema::getColumnListing('books');
foreach ($columns as $column) {
    echo "- {$column}\n";
}

echo "\n=== SAMPLE BOOKS ===\n";
$books = \Illuminate\Support\Facades\DB::table('books')->limit(3)->get();
foreach ($books as $book) {
    echo "ID: {$book->id}, Title: " . substr($book->title ?? 'N/A', 0, 30) . "...\n";
    echo "  Available columns: " . implode(', ', array_keys((array)$book)) . "\n\n";
}
