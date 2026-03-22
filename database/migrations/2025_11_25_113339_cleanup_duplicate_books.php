<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Clean up duplicate books
        $this->cleanupDuplicateBooks();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is not reversible as it deletes data
        // You would need to restore from backup if needed
    }

    /**
     * Clean up duplicate books by keeping the latest version
     */
    private function cleanupDuplicateBooks(): void
    {
        // Step 1: Find exact duplicates using author relationships
        $duplicates = DB::table('books as b')
            ->join('author_book as ab', 'b.id', '=', 'ab.book_id')
            ->join('authors as a', 'ab.author_id', '=', 'a.id')
            ->select(
                'b.title',
                'a.name as author_name',
                DB::raw('COUNT(*) as duplicate_count'),
                DB::raw('MIN(b.id) as keep_id'),
                DB::raw('GROUP_CONCAT(b.id ORDER BY b.created_at DESC) as all_ids')
            )
            ->groupBy('b.title', 'a.name')
            ->having('duplicate_count', '>', 1)
            ->get();

        $totalDeleted = 0;

        foreach ($duplicates as $duplicate) {
            // Get all IDs for this duplicate group
            $allIds = explode(',', $duplicate->all_ids);
            
            // Remove the first ID (the one we want to keep - most recent)
            $keepId = array_shift($allIds);
            
            // Delete the remaining duplicates (cascade will handle author_book relationships)
            if (!empty($allIds)) {
                $deleted = DB::table('books')
                    ->whereIn('id', $allIds)
                    ->delete();
                
                $totalDeleted += $deleted;
                
                echo "Cleaned up duplicate '{$duplicate->title}' by {$duplicate->author_name}: kept ID {$keepId}, deleted " . count($allIds) . " duplicates\n";
            }
        }

        // Step 2: Find similar books (same base title, same author - different editions)
        // We'll use a simpler approach - get all books and process in PHP
        $allBooks = DB::table('books as b')
            ->join('author_book as ab', 'b.id', '=', 'ab.book_id')
            ->join('authors as a', 'ab.author_id', '=', 'a.id')
            ->select('b.id', 'b.title', 'a.name as author_name', 'b.created_at')
            ->orderBy('b.created_at', 'desc')
            ->get();

        $processedBooks = [];
        
        foreach ($allBooks as $book) {
            // Create base title by removing edition numbers
            $baseTitle = preg_replace('/\d+(st|nd|rd|th)\s+edition/i', '', strtolower($book->title));
            $baseTitle = preg_replace('/\d+/', '', $baseTitle); // Remove all numbers
            $baseTitle = trim($baseTitle);
            
            $bookKey = $baseTitle . '|' . strtolower($book->author_name);
            
            if (!isset($processedBooks[$bookKey])) {
                // First occurrence - keep this book
                $processedBooks[$bookKey] = $book->id;
            } else {
                // Similar book found - delete this one
                DB::table('books')->where('id', $book->id)->delete();
                $totalDeleted++;
                echo "Cleaned up similar book '{$book->title}' by {$book->author_name}: deleted ID {$book->id} (kept ID {$processedBooks[$bookKey]})\n";
            }
        }

        echo "Total duplicate/similar books deleted: {$totalDeleted}\n";
    }
};
