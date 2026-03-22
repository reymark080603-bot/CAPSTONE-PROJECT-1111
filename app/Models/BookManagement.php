<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Models\Category;
use Illuminate\Support\Facades\Schema;

class BookManagement extends Model
{
    use HasFactory;

    /**
     * Get all books with pagination and filters
     */
    public static function getFilteredBooks($filters = [])
    {
        $query = Book::query();

        // Apply search filter
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%")
                  // Legacy columns
                  ->orWhere('author', 'LIKE', "%{$search}%")
                  ->orWhere('category', 'LIKE', "%{$search}%")
                  ->orWhere('publisher', 'LIKE', "%{$search}%")
                  // Normalized relations
                  ->orWhereHas('authors', function ($a) use ($search) {
                      $a->where('name', 'LIKE', "%{$search}%");
                  })
                  ->orWhereHas('categories', function ($c) use ($search) {
                      $c->where('name', 'LIKE', "%{$search}%");
                  })
                  ->orWhereHas('publisher', function ($p) use ($search) {
                      $p->where('name', 'LIKE', "%{$search}%");
                  });
            });
        }

        // Apply category filter
        if (!empty($filters['category'])) {
            $query->where(function($q) use ($filters) {
                $q->where('category', $filters['category'])
                  ->orWhereHas('categories', function ($c) use ($filters) {
                      $c->where('name', $filters['category']);
                  });
            });
        }

        // Apply course filter
        if (!empty($filters['course'])) {
            $query->where('course', $filters['course']);
        }

        // Apply year level filter
        if (!empty($filters['year_level'])) {
            $query->where('year_level', $filters['year_level']);
        }

        // Apply availability filter
        if (!empty($filters['availability'])) {
            $query->where('availability_status', $filters['availability']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get available categories
     */
    public static function getAvailableCategories()
    {
        $legacy = collect();
        if (Schema::hasColumn('books', 'category')) {
            $legacy = Book::select('category')
                ->distinct()
                ->whereNotNull('category')
                ->where('category', '!=', '')
                ->pluck('category');
        }

        $normalized = Category::orderBy('name')->pluck('name');

        return $normalized->merge($legacy)->unique()->values();
    }

    /**
     * Get book statistics
     */
    public static function getBookStatistics()
    {
        return [
            'total_books' => Book::count(),
            'available_books' => Book::where('availability_status', 'available')->count(),
            'borrowed_books' => Book::where('availability_status', 'borrowed')->count(),
            'categories_count' => Book::distinct('category')->whereNotNull('category')->count(),
        ];
    }

    /**
     * Get books for student dashboard
     */
    public static function getBooksForStudent($userId, $filters = [])
    {
        $user = User::find($userId);
        if (!$user) return collect();

        $query = Book::where('availability_status', 'available');

        // Apply search filter
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%")
                  // Legacy columns
                  ->orWhere('author', 'LIKE', "%{$search}%")
                  // Normalized relations
                  ->orWhereHas('authors', function ($a) use ($search) {
                      $a->where('name', 'LIKE', "%{$search}%");
                  })
                  ->orWhereHas('categories', function ($c) use ($search) {
                      $c->where('name', 'LIKE', "%{$search}%");
                  });
            });
        }

        // Apply category filter
        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get book details with borrowing info
     */
    public static function getBookWithBorrowInfo($bookId)
    {
        $book = Book::with(['borrowRecords.user'])->find($bookId);
        
        if (!$book) return null;

        return [
            'book' => $book,
            'current_borrower' => $book->borrowRecords()
                ->where('status', 'borrowed')
                ->with('user')
                ->first(),
            'borrow_history' => $book->borrowRecords()
                ->where('status', 'returned')
                ->with('user')
                ->orderBy('returned_date', 'desc')
                ->limit(10)
                ->get(),
            'total_borrows' => $book->borrowRecords->count(),
            'is_popular' => $book->borrowRecords->count() >= 5
        ];
    }

    /**
     * Get recently added books
     */
    public static function getRecentlyAdded($limit = 12)
    {
        return Book::where('availability_status', 'available')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get featured books (most borrowed)
     */
    public static function getFeaturedBooks($limit = 6)
    {
        return Book::select('books.*', DB::raw('COUNT(borrow_records.id) as borrow_count'))
            ->leftJoin('borrow_records', 'books.id', '=', 'borrow_records.book_id')
            ->where('books.availability_status', 'available')
            ->groupBy('books.id')
            ->orderBy('borrow_count', 'desc')
            ->limit($limit)
            ->get();
    }
}