<?php
$path = 'app/Http/Controllers/LibrarianController.php';
$content = file_get_contents($path);

// 1. Add popular_books query before the $data array
$popularBooksLogic = "        // Popular books for the month
        \$popularBooks = Book::withCount(['borrowRecords' => function(\$query) use (\$startDate, \$endDate) {
                                \$query->whereBetween('borrowed_date', [\$startDate, \$endDate]);
                            }])
                            ->orderByDesc('borrow_records_count')
                            ->limit(10)
                            ->get()
                            ->map(function(\$book) {
                                return [
                                    'title' => \$book->title,
                                    'author' => \$book->author,
                                    'borrow_count' => \$book->borrow_records_count
                                ];
                            });

        // Resource Types distribution
        \$resourceTypes = [
            ['type' => 'PDF / E-Book', 'count' => Book::whereNotNull('pdf_file')->count()],
            ['type' => 'Physical Book', 'count' => Book::whereNull('pdf_file')->count()],
        ];\n\n";

if (strpos($content, '$popularBooks') === false) {
    $content = str_replace(
        '$data = [',
        $popularBooksLogic . '        $data = [',
        $content
    );
}

// 2. Add to $data array
if (strpos($content, "'popular_books' => \$popularBooks") === false) {
    $content = str_replace(
        "'books_by_program' => \$booksByProgram,",
        "'books_by_program' => \$booksByProgram,\n            'popular_books' => \$popularBooks,\n            'resource_types' => \$resourceTypes,",
        $content
    );
}

file_put_contents($path, $content);
echo "Successfully updated LibrarianController.php with additional report data\n";
