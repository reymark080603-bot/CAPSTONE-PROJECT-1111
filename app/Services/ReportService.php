<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Book;
use App\Models\BorrowRecord;
use App\Models\Category;
use Carbon\Carbon;

class ReportService
{
    /**
     * Generate borrowing report
     */
    public function generateBorrowingReport($dateFrom, $dateTo, $filters = [])
    {
        $query = BorrowRecord::with(['user', 'book'])
            ->whereBetween('borrowed_date', [$dateFrom, $dateTo]);

        // Apply additional filters
        if (isset($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['course']) && $filters['course']) {
            $query->whereHas('user', function ($q) use ($filters) {
                $q->where('course', $filters['course']);
            });
        }

        $borrowRecords = $query->orderBy('borrowed_date', 'desc')->get();

        $summary = [
            'total_borrows' => $borrowRecords->count(),
            'returned_books' => $borrowRecords->where('status', 'returned')->count(),
            'active_borrows' => $borrowRecords->where('status', 'borrowed')->count(),
            'unique_students' => $borrowRecords->pluck('user_id')->unique()->count(),
            'unique_books' => $borrowRecords->pluck('book_id')->unique()->count()
        ];

        return [
            'summary' => $summary,
            'records' => $borrowRecords,
            'date_from' => $dateFrom,
            'date_to' => $dateTo
        ];
    }

    /**
     * Generate books report
     */
    public function generateBooksReport($dateFrom, $dateTo, $filters = [])
    {
        $query = Book::withCount(['borrowRecords' => function ($query) use ($dateFrom, $dateTo) {
            $query->whereBetween('borrowed_date', [$dateFrom, $dateTo]);
        }]);

        // Apply filters
        if (isset($filters['status']) && $filters['status'] !== 'all') {
            $query->where('availability_status', $filters['status']);
        }

        if (isset($filters['category']) && $filters['category']) {
            $query->whereHas('categories', function ($q) use ($filters) {
                $q->where('name', $filters['category']);
            });
        }

        $books = $query->get();

        $summary = [
            'total_books' => $books->count(),
            'available_books' => $books->where('availability_status', 'available')->count(),
            'borrowed_books' => $books->where('availability_status', 'borrowed')->count(),
            'total_borrows' => $books->sum('borrow_records_count'),
            'most_borrowed' => $books->sortByDesc('borrow_records_count')->first(),
            'least_borrowed' => $books->where('borrow_records_count', '>', 0)->sortBy('borrow_records_count')->first(),
            'never_borrowed' => $books->where('borrow_records_count', 0)->count()
        ];

        return [
            'summary' => $summary,
            'books' => $books,
            'date_from' => $dateFrom,
            'date_to' => $dateTo
        ];
    }

    /**
     * Generate students report
     */
    public function generateStudentsReport($dateFrom, $dateTo, $filters = [])
    {
        $query = User::whereHas('role', function($q) {
            $q->where('name', 'student');
        })
            ->withCount(['borrowRecords' => function ($query) use ($dateFrom, $dateTo) {
                $query->whereBetween('borrowed_date', [$dateFrom, $dateTo]);
            }]);

        // Apply filters
        if (isset($filters['course']) && $filters['course']) {
            $query->where('course', $filters['course']);
        }

        if (isset($filters['year']) && $filters['year']) {
            $query->where('year', $filters['year']);
        }

        $students = $query->get();

        $summary = [
            'total_students' => $students->count(),
            'active_students' => $students->whereNotNull('email_verified_at')->count(),
            'inactive_students' => $students->whereNull('email_verified_at')->count(),
            'total_borrows' => $students->sum('borrow_records_count'),
            'most_active_student' => $students->sortByDesc('borrow_records_count')->first(),
            'average_borrows_per_student' => $students->avg('borrow_records_count'),
            'students_with_borrows' => $students->where('borrow_records_count', '>', 0)->count()
        ];

        return [
            'summary' => $summary,
            'students' => $students,
            'date_from' => $dateFrom,
            'date_to' => $dateTo
        ];
    }

    /**
     * Generate analytics data
     */
    public function generateAnalytics($period = 'month', $dateFrom = null, $dateTo = null)
    {
        $dateFrom = $dateFrom ?: now()->subMonth();
        $dateTo = $dateTo ?: now();

        // Borrowing trends
        $borrowingTrends = $this->getBorrowingTrends($dateFrom, $dateTo, $period);

        // Popular books
        $popularBooks = Book::withCount('borrowRecords')
            ->orderByDesc('borrow_records_count')
            ->limit(10)
            ->get()
            ->map(function ($book) {
                return [
                    'title' => $book->title,
                    'author' => $book->author,
                    'borrow_count' => $book->borrow_records_count
                ];
            });

        // Course-wise borrowing
        $courseStats = User::select('course', DB::raw('COUNT(DISTINCT users.id) as student_count'))
            ->leftJoin('borrow_records', 'users.id', '=', 'borrow_records.user_id')
            ->whereHas('role', function($q) {
                $q->where('name', 'student');
            })
            ->whereBetween('borrow_records.borrowed_date', [$dateFrom, $dateTo])
            ->groupBy('course')
            ->get();

        // Category-wise borrowing
        $categoryStats = Category::select('categories.name as category', DB::raw('COUNT(book_category.book_id) as book_count'))
            ->join('book_category', 'categories.id', '=', 'book_category.category_id')
            ->leftJoin('borrow_records', 'book_category.book_id', '=', 'borrow_records.book_id')
            ->whereBetween('borrow_records.borrowed_date', [$dateFrom, $dateTo])
            ->groupBy('categories.name')
            ->get();

        return [
            'borrowing_trends' => $borrowingTrends,
            'popular_books' => $popularBooks,
            'course_stats' => $courseStats,
            'category_stats' => $categoryStats,
            'period' => $period,
            'date_from' => $dateFrom->format('Y-m-d'),
            'date_to' => $dateTo->format('Y-m-d')
        ];
    }

    /**
     * Get borrowing trends data
     */
    private function getBorrowingTrends($dateFrom, $dateTo, $period)
    {
        $driver = DB::getDriverName();
        $query = BorrowRecord::query();

        // Determine date format based on period
        switch ($period) {
            case 'day':
                if ($driver === 'sqlite') {
                    $query->selectRaw("strftime('%Y-%m-%d', borrowed_date) as date");
                } elseif ($driver === 'pgsql') {
                    $query->selectRaw("DATE(borrowed_date) as date");
                } else {
                    $query->selectRaw('DATE(borrowed_date) as date');
                }
                break;
            case 'week':
                if ($driver === 'sqlite') {
                    $query->selectRaw("strftime('%Y-%W', borrowed_date) as date");
                } elseif ($driver === 'pgsql') {
                    $query->selectRaw("EXTRACT(YEAR FROM borrowed_date) || '-' || EXTRACT(WEEK FROM borrowed_date) as date");
                } else {
                    $query->selectRaw('YEARWEEK(borrowed_date) as date');
                }
                break;
            case 'month':
                if ($driver === 'sqlite') {
                    $query->selectRaw("strftime('%Y-%m', borrowed_date) as date");
                } elseif ($driver === 'pgsql') {
                    $query->selectRaw("EXTRACT(YEAR FROM borrowed_date) || '-' || LPAD(EXTRACT(MONTH FROM borrowed_date)::text, 2, '0') as date");
                } else {
                    $query->selectRaw("DATE_FORMAT(borrowed_date, '%Y-%m') as date");
                }
                break;
            case 'year':
                if ($driver === 'sqlite') {
                    $query->selectRaw("strftime('%Y', borrowed_date) as date");
                } elseif ($driver === 'pgsql') {
                    $query->selectRaw("EXTRACT(YEAR FROM borrowed_date)::text as date");
                } else {
                    $query->selectRaw('YEAR(borrowed_date) as date');
                }
                break;
        }

        $trends = $query->selectRaw('COUNT(*) as count')
            ->whereBetween('borrowed_date', [$dateFrom, $dateTo])
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'count' => (int)$item->count
                ];
            });

        return $trends;
    }

    /**
     * Export report to CSV
     */
    public function exportReportCsv($data, $reportType, $dateFrom, $dateTo)
    {
        $csvData = [];

        // Add report header
        $csvData[] = ['Report Type', ucfirst($reportType) . ' Report'];
        $csvData[] = ['Date From', $dateFrom->format('Y-m-d')];
        $csvData[] = ['Date To', $dateTo->format('Y-m-d')];
        $csvData[] = ['Generated At', now()->format('Y-m-d H:i:s')];
        $csvData[] = [];

        // Add summary data
        if (isset($data['summary'])) {
            $csvData[] = ['Summary'];
            foreach ($data['summary'] as $key => $value) {
                if (is_object($value) && method_exists($value, 'getAttributes')) {
                    // Handle model objects
                    $csvData[] = [ucfirst(str_replace('_', ' ', $key)), $value->title ?? $value->firstname . ' ' . $value->lastname ?? 'N/A'];
                } else {
                    $csvData[] = [ucfirst(str_replace('_', ' ', $key)), $value];
                }
            }
            $csvData[] = [];
        }

        // Add detailed data based on report type
        switch ($reportType) {
            case 'borrowing':
                $csvData[] = ['Borrowing Records'];
                $csvData[] = ['Student', 'Book', 'Borrowed Date', 'Due Date', 'Return Date', 'Status'];
                foreach ($data['records'] as $record) {
                    $csvData[] = [
                        $record->user ? $record->user->firstname . ' ' . $record->user->lastname : 'Unknown',
                        $record->book ? $record->book->title : 'Unknown',
                        $record->borrowed_date,
                        $record->due_date,
                        $record->returned_date ?: 'Not returned',
                        $record->status
                    ];
                }
                break;

            case 'books':
                $csvData[] = ['Books Report'];
                $csvData[] = ['Title', 'Author', 'Status', 'Borrow Count'];
                foreach ($data['books'] as $book) {
                    $csvData[] = [
                        $book->title,
                        $book->author,
                        $book->availability_status,
                        $book->borrow_records_count
                    ];
                }
                break;

            case 'students':
                $csvData[] = ['Students Report'];
                $csvData[] = ['Name', 'Email', 'Course', 'Year', 'Status', 'Borrow Count'];
                foreach ($data['students'] as $student) {
                    $csvData[] = [
                        $student->firstname . ' ' . $student->lastname,
                        $student->email,
                        $student->course,
                        $student->year,
                        $student->email_verified_at ? 'Active' : 'Inactive',
                        $student->borrow_records_count
                    ];
                }
                break;
        }

        return $this->generateCsvResponse($csvData, $reportType . '-report-' . date('Y-m-d') . '.csv');
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
