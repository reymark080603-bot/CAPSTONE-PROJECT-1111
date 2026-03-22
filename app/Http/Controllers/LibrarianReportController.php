<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Book;
use App\Models\BorrowRecord;
use App\Models\Fine;
use App\Models\Category;
use Carbon\Carbon;

class LibrarianReportController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            if (!Auth::check()) {
                if ($request->expectsJson()) {
                    return response()->json(['error' => 'Authentication required'], 401);
                }
                return redirect()->route('login');
            }
            return $next($request);
        });
    }

    /**
     * Show reports page
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        return view('librarian.reports.index', compact('user'));
    }

    /**
     * Generate report
     */
    public function generate(Request $request)
    {
        $reportType = $request->get('type', 'borrowing');
        $dateFrom = $request->get('date_from') ? Carbon::parse($request->date_from) : now()->subMonth();
        $dateTo = $request->get('date_to') ? Carbon::parse($request->date_to) : now();
        $format = $request->get('format', 'html');

        $data = [];

        switch ($reportType) {
            case 'borrowing':
                $data = $this->generateBorrowingReport($dateFrom, $dateTo);
                break;
            case 'books':
                $data = $this->generateBooksReport($dateFrom, $dateTo);
                break;
            case 'students':
                $data = $this->generateStudentsReport($dateFrom, $dateTo);
                break;
            case 'fines':
                $data = $this->generateFinesReport($dateFrom, $dateTo);
                break;
            case 'overdue':
                $data = $this->generateOverdueReport($dateFrom, $dateTo);
                break;
            default:
                return response()->json(['error' => 'Invalid report type'], 400);
        }

        if ($format === 'csv') {
            return $this->exportReportCsv($data, $reportType, $dateFrom, $dateTo);
        } elseif ($format === 'pdf') {
            return $this->exportReportPdf($data, $reportType, $dateFrom, $dateTo);
        }

        // Return HTML view
        $user = Auth::user();
        return view('librarian.reports.show', compact('user', 'data', 'reportType', 'dateFrom', 'dateTo'));
    }

    /**
     * Show analytics page
     */
    public function analytics(Request $request)
    {
        $user = Auth::user();

        return view('librarian.reports.analytics', compact('user'));
    }

    /**
     * Get analytics data via API
     */
    public function analyticsApi(Request $request)
    {
        $period = $request->get('period', 'month'); // day, week, month, year
        $dateFrom = $request->get('date_from') ? Carbon::parse($request->date_from) : now()->subMonth();
        $dateTo = $request->get('date_to') ? Carbon::parse($request->date_to) : now();

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

        // Fine collection
        $fineStats = [
            'total_fines' => Fine::whereBetween('issued_date', [$dateFrom, $dateTo])->sum('amount'),
            'paid_fines' => Fine::where('status', 'paid')->whereBetween('issued_date', [$dateFrom, $dateTo])->sum('amount'),
            'pending_fines' => Fine::where('status', 'pending')->whereBetween('issued_date', [$dateFrom, $dateTo])->sum('amount')
        ];

        return response()->json([
            'borrowing_trends' => $borrowingTrends,
            'popular_books' => $popularBooks,
            'course_stats' => $courseStats,
            'category_stats' => $categoryStats,
            'fine_stats' => $fineStats,
            'period' => $period,
            'date_from' => $dateFrom->format('Y-m-d'),
            'date_to' => $dateTo->format('Y-m-d')
        ]);
    }

    /**
     * Generate borrowing report
     */
    private function generateBorrowingReport($dateFrom, $dateTo)
    {
        $borrowRecords = BorrowRecord::with(['user', 'book'])
            ->whereBetween('borrowed_date', [$dateFrom, $dateTo])
            ->orderBy('borrowed_date', 'desc')
            ->get();

        $summary = [
            'total_borrows' => $borrowRecords->count(),
            'returned_books' => $borrowRecords->where('status', 'returned')->count(),
            'overdue_books' => $borrowRecords->where('status', 'borrowed')
                ->where('due_date', '<', now())->count(),
            'active_borrows' => $borrowRecords->where('status', 'borrowed')->count()
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
    private function generateBooksReport($dateFrom, $dateTo)
    {
        $books = Book::withCount(['borrowRecords' => function ($query) use ($dateFrom, $dateTo) {
            $query->whereBetween('borrowed_date', [$dateFrom, $dateTo]);
        }])->get();

        $summary = [
            'total_books' => $books->count(),
            'available_books' => $books->where('availability_status', 'available')->count(),
            'borrowed_books' => $books->where('availability_status', 'borrowed')->count(),
            'most_borrowed' => $books->sortByDesc('borrow_records_count')->first()
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
    private function generateStudentsReport($dateFrom, $dateTo)
    {
        $students = User::whereHas('role', function($q) {
            $q->where('name', 'student');
        })
            ->withCount(['borrowRecords' => function ($query) use ($dateFrom, $dateTo) {
                $query->whereBetween('borrowed_date', [$dateFrom, $dateTo]);
            }])
            ->get();

        $summary = [
            'total_students' => $students->count(),
            'active_students' => $students->whereNotNull('email_verified_at')->count(),
            'inactive_students' => $students->whereNull('email_verified_at')->count(),
            'most_active_student' => $students->sortByDesc('borrow_records_count')->first()
        ];

        return [
            'summary' => $summary,
            'students' => $students,
            'date_from' => $dateFrom,
            'date_to' => $dateTo
        ];
    }

    /**
     * Generate fines report
     */
    private function generateFinesReport($dateFrom, $dateTo)
    {
        $fines = Fine::with(['user', 'borrowRecord.book'])
            ->whereBetween('issued_date', [$dateFrom, $dateTo])
            ->orderBy('issued_date', 'desc')
            ->get();

        $summary = [
            'total_fines' => $fines->count(),
            'total_amount' => $fines->sum('amount'),
            'paid_fines' => $fines->where('status', 'paid')->sum('amount'),
            'pending_fines' => $fines->where('status', 'pending')->sum('amount')
        ];

        return [
            'summary' => $summary,
            'fines' => $fines,
            'date_from' => $dateFrom,
            'date_to' => $dateTo
        ];
    }

    /**
     * Generate overdue report
     */
    private function generateOverdueReport($dateFrom, $dateTo)
    {
        $overdueRecords = BorrowRecord::with(['user', 'book'])
            ->where('status', 'borrowed')
            ->where('due_date', '<', now())
            ->whereBetween('borrowed_date', [$dateFrom, $dateTo])
            ->orderBy('due_date', 'asc')
            ->get();

        $summary = [
            'total_overdue' => $overdueRecords->count(),
            'total_fine_amount' => $overdueRecords->sum(function ($record) {
                $daysOverdue = now()->diffInDays($record->due_date);
                return $daysOverdue * 5; // 5 pesos per day
            })
        ];

        return [
            'summary' => $summary,
            'records' => $overdueRecords,
            'date_from' => $dateFrom,
            'date_to' => $dateTo
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
    private function exportReportCsv($data, $reportType, $dateFrom, $dateTo)
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
                $csvData[] = [ucfirst(str_replace('_', ' ', $key)), $value];
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

            case 'fines':
                $csvData[] = ['Fines Report'];
                $csvData[] = ['Student', 'Book', 'Amount', 'Reason', 'Status', 'Issued Date'];
                foreach ($data['fines'] as $fine) {
                    $csvData[] = [
                        $fine->user ? $fine->user->firstname . ' ' . $fine->user->lastname : 'Unknown',
                        $fine->borrowRecord && $fine->borrowRecord->book ? $fine->borrowRecord->book->title : 'Unknown',
                        $fine->amount,
                        $fine->reason,
                        $fine->status,
                        $fine->issued_date
                    ];
                }
                break;

            case 'overdue':
                $csvData[] = ['Overdue Report'];
                $csvData[] = ['Student', 'Book', 'Due Date', 'Days Overdue', 'Fine Amount'];
                foreach ($data['records'] as $record) {
                    $daysOverdue = now()->diffInDays($record->due_date);
                    $fineAmount = $daysOverdue * 5;
                    $csvData[] = [
                        $record->user ? $record->user->firstname . ' ' . $record->user->lastname : 'Unknown',
                        $record->book ? $record->book->title : 'Unknown',
                        $record->due_date,
                        $daysOverdue,
                        $fineAmount
                    ];
                }
                break;
        }

        $handle = fopen('php://temp', 'r+');

        foreach ($csvData as $row) {
            fputcsv($handle, $row);
        }

        rewind($handle);
        $csvContent = stream_get_contents($handle);
        fclose($handle);

        return response($csvContent)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="' . $reportType . '-report-' . date('Y-m-d') . '.csv"');
    }

    /**
     * Export report to PDF (placeholder - would need additional package)
     */
    private function exportReportPdf($data, $reportType, $dateFrom, $dateTo)
    {
        // This would require a PDF generation package like dompdf or tcpdf
        // For now, return CSV as fallback
        return $this->exportReportCsv($data, $reportType, $dateFrom, $dateTo);
    }
}
