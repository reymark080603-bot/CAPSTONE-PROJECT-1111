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
        // Aggressive cleanup of all duplicate and similar books
        $this->aggressiveCleanup();
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
     * Aggressive cleanup - keep only one book per title/author combination
     */
    private function aggressiveCleanup(): void
    {
        echo "Starting aggressive duplicate cleanup...\n";
        
        $isSqlite = DB::getDriverName() === 'sqlite';
        $groupConcatSql = $isSqlite 
            ? 'GROUP_CONCAT(a.name) as author_names' 
            : 'GROUP_CONCAT(a.name ORDER BY a.name) as author_names';

        // Get ALL books with their authors
        $allBooks = DB::table('books as b')
            ->leftJoin('author_book as ab', 'b.id', '=', 'ab.book_id')
            ->leftJoin('authors as a', 'ab.author_id', '=', 'a.id')
            ->select(
                'b.id', 
                'b.title', 
                'b.created_at',
                DB::raw($groupConcatSql),
                DB::raw('COUNT(ab.author_id) as author_count')
            )
            ->groupBy('b.id', 'b.title', 'b.created_at')
            ->orderBy('b.created_at', 'desc') // Most recent first
            ->get();

        $processedBooks = [];
        $booksToDelete = [];
        $totalDeleted = 0;

        foreach ($allBooks as $book) {
            if ($isSqlite && $book->author_names) {
                // In SQLite, GROUP_CONCAT doesn't support ORDER BY. 
                // We split and sort the author names to ensure consistent key comparison.
                $names = explode(',', $book->author_names);
                sort($names);
                $book->author_names = implode(',', $names);
            }

            // Handle books with no authors
            $authorName = $book->author_names ?: 'Unknown Author';
            
            // Create normalized key for comparison
            $normalizedTitle = $this->normalizeTitle($book->title);
            $normalizedAuthor = strtolower(trim($authorName));
            $bookKey = $normalizedTitle . '|' . $normalizedAuthor;
            
            if (!isset($processedBooks[$bookKey])) {
                // First occurrence - keep this book
                $processedBooks[$bookKey] = $book->id;
                echo "KEEPING: \"{$book->title}\" by {$authorName} (ID: {$book->id})\n";
            } else {
                // Duplicate found - mark for deletion
                $booksToDelete[] = $book->id;
                echo "DELETING: \"{$book->title}\" by {$authorName} (ID: {$book->id}) - DUPLICATE of ID {$processedBooks[$bookKey]}\n";
            }
        }

        // Delete all marked books
        if (!empty($booksToDelete)) {
            // Delete in batches to avoid memory issues
            $chunks = array_chunk($booksToDelete, 100);
            
            foreach ($chunks as $chunk) {
                $deleted = DB::table('books')->whereIn('id', $chunk)->delete();
                $totalDeleted += $deleted;
            }
            
            echo "Aggressive cleanup complete. Deleted {$totalDeleted} duplicate books.\n";
        } else {
            echo "No duplicates found. Database is already clean.\n";
        }
    }

    /**
     * Normalize title for comparison - removes edition numbers, special chars, etc.
     */
    private function normalizeTitle($title): string
    {
        $normalized = strtolower($title);
        
        // Remove edition numbers (10th, 9th, 8th, etc.)
        $normalized = preg_replace('/\d+(st|nd|rd|th)\s+edition/', '', $normalized);
        
        // Remove all numbers
        $normalized = preg_replace('/\d+/', '', $normalized);
        
        // Remove special characters except spaces and hyphens
        $normalized = preg_replace('/[^\w\s-]/', '', $normalized);
        
        // Normalize multiple spaces to single space
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        
        // Trim whitespace
        return trim($normalized);
    }
};
