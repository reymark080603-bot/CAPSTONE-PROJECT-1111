<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== DATABASE OVERVIEW ===\n";
echo str_repeat("=", 50) . "\n";

// Check books
$bookCount = \Illuminate\Support\Facades\DB::table('books')->count();
echo "Books: {$bookCount}\n";

// Check borrow records
$borrowCount = \Illuminate\Support\Facades\DB::table('borrow_records')->count();
echo "Borrow Records: {$borrowCount}\n";

// Check authors
$authorCount = \Illuminate\Support\Facades\DB::table('authors')->count();
echo "Authors: {$authorCount}\n";

// Check categories
$categoryCount = \Illuminate\Support\Facades\DB::table('categories')->count();
echo "Categories: {$categoryCount}\n";

// Check courses
$courseCount = \Illuminate\Support\Facades\DB::table('courses')->count();
echo "Courses: {$courseCount}\n";

// Check year levels
$yearLevelCount = \Illuminate\Support\Facades\DB::table('year_levels')->count();
echo "Year Levels: {$yearLevelCount}\n";

// Check genders
$genderCount = \Illuminate\Support\Facades\DB::table('genders')->count();
echo "Genders: {$genderCount}\n";

echo "\n" . str_repeat("=", 50) . "\n";
echo "=== SAMPLE DATA ===\n";

// Show sample books if any
if ($bookCount > 0) {
    echo "\nSample Books:\n";
    $books = \Illuminate\Support\Facades\DB::table('books')->limit(3)->get();
    foreach ($books as $book) {
        echo "- ID: {$book->id}, Title: " . substr($book->title, 0, 30) . "...\n";
    }
}

// Show sample courses if any
if ($courseCount > 0) {
    echo "\nCourses:\n";
    $courses = \Illuminate\Support\Facades\DB::table('courses')->get();
    foreach ($courses as $course) {
        echo "- ID: {$course->id}, Name: {$course->name}\n";
    }
}

// Show sample year levels if any
if ($yearLevelCount > 0) {
    echo "\nYear Levels:\n";
    $yearLevels = \Illuminate\Support\Facades\DB::table('year_levels')->get();
    foreach ($yearLevels as $yearLevel) {
        echo "- ID: {$yearLevel->id}, Level: {$yearLevel->level}\n";
    }
}

echo "\n";
