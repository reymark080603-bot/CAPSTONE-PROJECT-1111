<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DashboardData extends Model
{
    use HasFactory;

    /**
     * Get student dashboard statistics
     */
    public static function getStudentStats($userId)
    {
        $totalBorrowed = BorrowRecord::where('user_id', $userId)->count();
        $currentBorrows = BorrowRecord::where('user_id', $userId)
            ->where('status', 'borrowed')
            ->count();
        $totalReturned = BorrowRecord::where('user_id', $userId)
            ->where('status', 'returned')
            ->count();
        $overdueBooks = BorrowRecord::where('user_id', $userId)
            ->where('status', 'borrowed')
            ->where('due_date', '<', now())
            ->count();
        $favoriteBooks = Favorite::where('user_id', $userId)->count();

        return [
            'total_borrowed' => $totalBorrowed,
            'current_borrows' => $currentBorrows,
            'total_returned' => $totalReturned,
        ];
    }

    /**
     * Get recommended books for student
     */
    public static function getRecommendedBooks($userId, $limit = 6)
    {
        $user = User::find($userId);
        if (!$user) return collect();

        // Get books based on user's course and year level
        return Book::where('availability_status', 'available')
            ->where(function ($query) use ($user) {
                $query->where('course', $user->course)
                      ->orWhere('year_level', $user->year)
                      ->orWhereNull('course');
            })
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get recent books added to library
     */
    public static function getRecentBooks($limit = 6)
    {
        return Book::where('availability_status', 'available')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get popular books based on borrow count
     */
    public static function getPopularBooks($limit = 6)
    {
        return Book::select('books.*', DB::raw('COUNT(borrow_records.id) as borrow_count'))
            ->leftJoin('borrow_records', 'books.id', '=', 'borrow_records.book_id')
            ->where('books.availability_status', 'available')
            ->groupBy('books.id')
            ->orderBy('borrow_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get reading history for student
     */
    public static function getReadingHistory($userId, $limit = 10)
    {
        return BorrowRecord::with(['book'])
            ->where('user_id', $userId)
            ->orderBy('borrowed_date', 'desc')
            ->limit($limit)
            ->get();
    }
}