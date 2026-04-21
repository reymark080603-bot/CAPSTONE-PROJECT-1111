<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Book;
use App\Models\BorrowRecord;
use App\Models\Favorite;
use App\Models\Category;
use Carbon\Carbon;

class LibrarianDashboardService
{
    /**
     * Get dashboard statistics
     */
    public function getDashboardStats()
    {
        // Basic counts
        $totalBooks = Book::count();
        $totalStudents = User::where('role_id', 1)->count(); // role_id 1 = student
        $activeBorrows = BorrowRecord::where('status', 'borrowed')->count();
        $totalLoans = BorrowRecord::count();

        // Books by status
        $booksByStatus = Book::select('availability_status', DB::raw('count(*) as count'))
            ->groupBy('availability_status')
            ->get()
            ->pluck('count', 'availability_status')
            ->toArray();

        // Recent activity (last 30 days)
        $recentBorrows = BorrowRecord::where('borrowed_date', '>=', now()->subDays(30))->count();
        $recentReturns = BorrowRecord::where('returned_date', '>=', now()->subDays(30))
            ->whereNotNull('returned_date')->count();
        $recentRegistrations = User::where('role_id', 1)
            ->where('created_at', '>=', now()->subDays(30))->count();

        // Popular categories (top 5) via normalized categories
        $popularCategories = Category::select('categories.name as category', DB::raw('COUNT(book_category.book_id) as count'))
            ->join('book_category', 'categories.id', '=', 'book_category.category_id')
            ->groupBy('categories.name')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        // Monthly borrowing trends (last 12 months) - database agnostic
        $driver = DB::getDriverName();
        $monthlyQuery = BorrowRecord::query();

        if ($driver === 'sqlite') {
            $monthlyQuery->selectRaw("strftime('%Y', borrowed_date) as year")
                ->selectRaw("strftime('%m', borrowed_date) as month")
                ->selectRaw('COUNT(*) as count');
        } elseif ($driver === 'pgsql') {
            $monthlyQuery->selectRaw("EXTRACT(YEAR FROM borrowed_date)::int as year")
                ->selectRaw("EXTRACT(MONTH FROM borrowed_date)::int as month")
                ->selectRaw('COUNT(*) as count');
        } else { // mysql/mariadb default
            $monthlyQuery->selectRaw('YEAR(borrowed_date) as year')
                ->selectRaw('MONTH(borrowed_date) as month')
                ->selectRaw('COUNT(*) as count');
        }

        $monthlyBorrows = $monthlyQuery
            ->where('borrowed_date', '>=', now()->subMonths(12))
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

        // Most borrowed books (top 10) using withCount and accessors
        $mostBorrowedBooks = Book::withCount('borrowRecords')
            ->orderByDesc('borrow_records_count')
            ->limit(10)
            ->get(['id', 'title'])
            ->map(function ($book) {
                return [
                    'id' => $book->id,
                    'title' => $book->title,
                    'author' => $book->author,
                    'category' => $book->category,
                    'borrow_count' => $book->borrow_records_count,
                ];
            });

        // Course-wise borrowing statistics
        $courseStats = User::select('courses.code', 'courses.name', DB::raw('COUNT(DISTINCT users.id) as student_count'))
            ->leftJoin('courses', 'users.course_id', '=', 'courses.id')
            ->where('users.role_id', 1) // role_id 1 = student
            ->whereNotNull('courses.code')
            ->groupBy('courses.code', 'courses.name')
            ->get();

        // Borrowed books by program
        $targetCourses = ['BSE', 'BSHM', 'BSIT', 'BSN', 'BSTM'];
        
        $borrowedBooksByCourse = User::select('courses.code', DB::raw('COUNT(borrow_records.id) as borrowed_count'))
            ->join('borrow_records', 'users.id', '=', 'borrow_records.user_id')
            ->leftJoin('courses', 'users.course_id', '=', 'courses.id')
            ->where('users.role_id', 1) // role_id 1 = student
            ->where('borrow_records.status', 'borrowed')
            ->whereNotNull('courses.code')
            ->whereIn('courses.code', $targetCourses)
            ->groupBy('courses.code')
            ->get()
            ->pluck('borrowed_count', 'courses.code')
            ->toArray();

        // Ensure all target courses are included with zero values
        $finalCourseData = [];
        foreach ($targetCourses as $course) {
            $finalCourseData[$course] = $borrowedBooksByCourse[$course] ?? 0;
        }

        return [
            'basic_stats' => [
                'total_books' => $totalBooks,
                'total_students' => $totalStudents,
                'active_borrows' => $activeBorrows,
                'total_loans' => $totalLoans,
            ],
            'books_by_status' => $booksByStatus,
            'borrowed_books_by_course' => $finalCourseData,
            'recent_activity' => [
                'borrows' => $recentBorrows,
                'returns' => $recentReturns,
                'registrations' => $recentRegistrations
            ],
            'popular_categories' => $popularCategories,
            'monthly_trends' => $monthlyBorrows,
            'most_borrowed_books' => $mostBorrowedBooks,
            'course_stats' => $courseStats
        ];
    }

    /**
     * Get recent activities
     */
    public function getRecentActivities($limit = 20)
    {
        // Get recent borrow records with user and book info
        $recentBorrows = BorrowRecord::with(['user', 'book'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($borrow) {
                return [
                    'type' => 'borrow',
                    'user' => $borrow->user ? $borrow->user->firstname . ' ' . $borrow->user->lastname : 'Unknown',
                    'book' => $borrow->book ? $borrow->book->title : 'Unknown',
                    'status' => $borrow->status,
                    'date' => $borrow->created_at,
                    'details' => [
                        'borrowed_date' => $borrow->borrowed_date,
                        'due_date' => $borrow->due_date,
                        'returned_date' => $borrow->returned_date
                    ]
                ];
            });

        // Get recent user registrations
        $recentRegistrations = User::whereHas('role', function($q) {
            $q->where('name', 'student');
        })
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($user) {
                return [
                    'type' => 'registration',
                    'user' => $user->firstname . ' ' . $user->lastname,
                    'book' => null,
                    'status' => 'registered',
                    'date' => $user->created_at,
                    'details' => [
                        'course' => $user->course,
                        'year' => $user->year,
                        'email' => $user->email
                    ]
                ];
            });

        // Combine and sort all activities
        $allActivities = collect($recentBorrows)
            ->concat($recentRegistrations)
            ->sortByDesc('date')
            ->take($limit)
            ->values();

        return $allActivities;
    }

    /**
     * Get system alerts and notifications for librarian
     */
    public function getSystemAlerts()
    {
        $alerts = [];

        // Check for new registrations in the last 24 hours
        $newRegistrations = User::whereHas('role', function($q) {
            $q->where('name', 'student');
        })
            ->where('created_at', '>=', now()->subDay())
            ->count();

        if ($newRegistrations > 0) {
            $alerts[] = [
                'type' => 'new_registrations',
                'severity' => 'success',
                'title' => 'New Student Registrations',
                'message' => "{$newRegistrations} new student(s) registered in the last 24 hours",
                'action_url' => '/librarian/students',
                'created_at' => now()
            ];
        }

        return collect($alerts)->sortByDesc('created_at')->values();
    }

    /**
     * Quick actions for dashboard
     */
    public function performQuickAction($action, $data)
    {
        $result = ['success' => false, 'message' => 'Invalid action'];

        switch ($action) {
            case 'mark_book_returned':
                $borrowId = $data['borrow_id'] ?? null;
                $result = $this->markBookReturned($borrowId);
                break;

            case 'send_overdue_reminder':
                $userId = $data['user_id'] ?? null;
                $result = $this->sendOverdueReminder($userId);
                break;

            default:
                $result = [
                    'success' => false,
                    'message' => 'Unknown action'
                ];
        }

        return $result;
    }

    /**
     * Mark book as returned
     */
    private function markBookReturned($borrowId)
    {
        $borrowRecord = BorrowRecord::find($borrowId);

        if ($borrowRecord && $borrowRecord->status === 'borrowed') {
            $borrowRecord->update([
                'returned_date' => now(),
                'status' => 'returned'
            ]);

            // Open access system - books remain available

            return [
                'success' => true,
                'message' => 'Book marked as returned successfully'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Borrow record not found or book already returned'
            ];
        }
    }

    /**
     * Send overdue reminder
     */
    private function sendOverdueReminder($userId)
    {
        $user = User::find($userId);

        if ($user) {
            // In a real application, you would send an email here
            // For now, we'll just return success
            return [
                'success' => true,
                'message' => 'Reminder sent to ' . $user->firstname . ' ' . $user->lastname
            ];
        } else {
            return [
                'success' => false,
                'message' => 'User not found'
            ];
        }
    }

    /**
     * Export dashboard data
     */
    public function exportData($type = 'overview')
    {
        switch ($type) {
            case 'statistics':
                return $this->exportStatistics();
            default:
                return $this->exportOverview();
        }
    }

    /**
     * Export statistics
     */
    private function exportStatistics()
    {
        $stats = $this->getDashboardStats();

        $csvData = [];
        $csvData[] = ['Metric', 'Value'];
        $csvData[] = ['Total Books', $stats['basic_stats']['total_books']];
        $csvData[] = ['Total Students', $stats['basic_stats']['total_students']];
        $csvData[] = ['Active Borrows', $stats['basic_stats']['active_borrows']];
        $csvData[] = ['Available Books', $stats['basic_stats']['available_books']];

        return $this->generateCsvResponse($csvData, 'library-statistics-' . date('Y-m-d') . '.csv');
    }

    /**
     * Export overview
     */
    private function exportOverview()
    {
        // Export general overview data
        return $this->exportStatistics();
    }

    /**
     * Generate CSV response
     */
    private function generateCsvResponse($data, $filename)
    {
        $handle = fopen('php://temp', 'r+');

        foreach ($data as $row) {
            fputcsv($handle, $row);
        }

        rewind($handle);
        $csvContent = stream_get_contents($handle);
        fclose($handle);

        return response($csvContent)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }
}
