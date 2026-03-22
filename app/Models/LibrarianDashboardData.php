<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Category;

class LibrarianDashboardData extends Model
{
    use HasFactory;

    /**
     * Get basic statistics for librarian dashboard
     */
    public static function getBasicStats()
    {
        $totalBooks = Book::count();
        $totalStudents = User::whereHas('role', function($q) {
            $q->where('name', 'student');
        })->count();
        $activeBorrows = BorrowRecord::where('status', 'borrowed')->count();
        $availableBooks = Book::where('availability_status', 'available')->count();

        return [
            'total_books' => $totalBooks,
            'total_students' => $totalStudents,
            'active_borrows' => $activeBorrows,
            'available_books' => $availableBooks
        ];
    }

    /**
     * Get books by status for pie chart
     */
    public static function getBooksByStatus()
    {
        return Book::select('availability_status', DB::raw('count(*) as count'))
            ->groupBy('availability_status')
            ->get()
            ->pluck('count', 'availability_status')
            ->toArray();
    }

    /**
     * Get monthly borrowing trends
     */
    public static function getMonthlyTrends($months = 12)
    {
        $driver = DB::getDriverName();
        $query = BorrowRecord::query();

        if ($driver === 'sqlite') {
            $query->selectRaw("strftime('%Y', borrowed_date) as year")
                ->selectRaw("strftime('%m', borrowed_date) as month")
                ->selectRaw('COUNT(*) as count');
        } elseif ($driver === 'pgsql') {
            $query->selectRaw("EXTRACT(YEAR FROM borrowed_date)::int as year")
                ->selectRaw("EXTRACT(MONTH FROM borrowed_date)::int as month")
                ->selectRaw('COUNT(*) as count');
        } else { // mysql/mariadb default
            $query->selectRaw('YEAR(borrowed_date) as year')
                ->selectRaw('MONTH(borrowed_date) as month')
                ->selectRaw('COUNT(*) as count');
        }

        return $query
            ->where('borrowed_date', '>=', now()->subMonths($months))
            ->groupBy('year', 'month')
            ->orderBy('year', 'asc')
            ->orderBy('month', 'asc')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => Carbon::createFromDate($item->year, (int)$item->month, 1)->format('Y-m'),
                    'count' => (int)$item->count
                ];
            });
    }

    /**
     * Get popular categories
     */
    public static function getPopularCategories($limit = 5)
    {
        return Category::select('categories.name as category', DB::raw('COUNT(book_category.book_id) as count'))
            ->join('book_category', 'categories.id', '=', 'book_category.category_id')
            ->groupBy('categories.name')
            ->orderByDesc('count')
            ->limit($limit)
            ->get();
    }

    /**
     * Get most borrowed books
     */
    public static function getMostBorrowedBooks($limit = 10)
    {
        $books = Book::withCount('borrowRecords')
            ->orderByDesc('borrow_records_count')
            ->limit($limit)
            ->get(['id', 'title']);

        // Map to include author and category via accessors
        return $books->map(function ($book) {
            return [
                'id' => $book->id,
                'title' => $book->title,
                'author' => $book->author, // accessor returns normalized names
                'category' => $book->category, // accessor returns normalized names
                'borrow_count' => $book->borrow_records_count,
            ];
        });
    }

    /**
     * Get today's activity summary
     */
    public static function getTodaysSummary()
    {
        $today = now()->toDateString();

        $todayBorrows = BorrowRecord::whereDate('borrowed_date', $today)->count();
        $todayReturns = BorrowRecord::whereDate('returned_date', $today)
            ->whereNotNull('returned_date')->count();
        $todayRegistrations = User::whereHas('role', function($q) {
            $q->where('name', 'student');
        })
            ->whereDate('created_at', $today)->count();

        return [
            'borrows' => $todayBorrows,
            'returns' => $todayReturns,
            'registrations' => $todayRegistrations
        ];
    }
}