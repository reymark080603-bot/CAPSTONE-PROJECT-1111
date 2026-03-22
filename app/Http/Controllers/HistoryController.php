<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\BorrowRecord;
use Carbon\Carbon;

class HistoryController extends Controller
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

            // Check if user is a student
            if (!Auth::user()->isStudent()) {
                if ($request->expectsJson()) {
                    return response()->json(['error' => 'Student access required'], 403);
                }
                return redirect()->route('librarian.dashboard');
            }

            return $next($request);
        });
    }

    /**
     * Show borrowing history page
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Get filter parameters
        $status = $request->get('status', 'all');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        $search = $request->get('search');
        $sortBy = $request->get('sort_by', 'borrowed_date');
        $sortOrder = $request->get('sort_order', 'desc');

        return view('dashboard.history', compact(
            'user', 'status', 'dateFrom', 'dateTo', 'search', 'sortBy', 'sortOrder'
        ));
    }

    /**
     * Get borrowing history via API
     */
    public function api(Request $request)
    {
        $user = Auth::user();
        $perPage = $request->get('per_page', 15);

        // Get filter parameters
        $status = $request->get('status', 'all');
        $dateFrom = $request->get('date_from') ? Carbon::parse($request->date_from) : null;
        $dateTo = $request->get('date_to') ? Carbon::parse($request->date_to) : null;
        $search = $request->get('search');
        $sortBy = $request->get('sort_by', 'borrowed_date');
        $sortOrder = $request->get('sort_order', 'desc');

        // Build query
        $query = BorrowRecord::with(['book', 'fines'])
            ->where('user_id', $user->id);

        // Apply status filter
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        // Apply date filters
        if ($dateFrom) {
            $query->where('borrowed_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->where('borrowed_date', '<=', $dateTo);
        }

        // Apply search filter
        if ($search) {
            $query->whereHas('book', function ($bookQuery) use ($search) {
                $bookQuery->where('title', 'LIKE', "%{$search}%")
                         ->orWhere('author', 'LIKE', "%{$search}%")
                         ->orWhere('isbn', 'LIKE', "%{$search}%");
            });
        }

        // Apply sorting
        $query->orderBy($sortBy, $sortOrder);

        // Paginate results
        $history = $query->paginate($perPage);

        // Add computed fields
        $history->getCollection()->transform(function ($record) {
            $record->is_overdue = $record->status === 'borrowed' && $record->due_date < now();
            $record->days_overdue = $record->is_overdue ? now()->diffInDays($record->due_date) : 0;
            $record->fine_amount = $record->fines->sum('amount');
            return $record;
        });

        return response()->json([
            'history' => $history
        ]);
    }

    /**
     * Export borrowing history
     */
    public function export(Request $request)
    {
        $user = Auth::user();
        $format = $request->get('format', 'csv');

        // Get filter parameters (same as API)
        $status = $request->get('status', 'all');
        $dateFrom = $request->get('date_from') ? Carbon::parse($request->date_from) : null;
        $dateTo = $request->get('date_to') ? Carbon::parse($request->date_to) : null;
        $search = $request->get('search');

        // Build query
        $query = BorrowRecord::with(['book', 'fines'])
            ->where('user_id', $user->id);

        // Apply filters
        if ($status !== 'all') {
            $query->where('status', $status);
        }
        if ($dateFrom) {
            $query->where('borrowed_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->where('borrowed_date', '<=', $dateTo);
        }
        if ($search) {
            $query->whereHas('book', function ($bookQuery) use ($search) {
                $bookQuery->where('title', 'LIKE', "%{$search}%")
                         ->orWhere('author', 'LIKE', "%{$search}%");
            });
        }

        $records = $query->orderBy('borrowed_date', 'desc')->get();

        if ($format === 'csv') {
            return $this->exportHistoryCsv($records, $user);
        }

        return response()->json(['error' => 'Unsupported export format'], 400);
    }


    /**
     * Get reading statistics
     */
    public function getReadingStats(Request $request)
    {
        $user = Auth::user();
        $period = $request->get('period', 'month'); // day, week, month, year

        // Calculate date range based on period
        switch ($period) {
            case 'week':
                $dateFrom = now()->startOfWeek();
                break;
            case 'month':
                $dateFrom = now()->startOfMonth();
                break;
            case 'year':
                $dateFrom = now()->startOfYear();
                break;
            default:
                $dateFrom = now()->startOfMonth();
        }

        // Books borrowed in period
        $booksBorrowed = BorrowRecord::where('user_id', $user->id)
            ->where('borrowed_date', '>=', $dateFrom)
            ->count();

        // Books returned in period
        $booksReturned = BorrowRecord::where('user_id', $user->id)
            ->where('returned_date', '>=', $dateFrom)
            ->whereNotNull('returned_date')
            ->count();

        // Current active borrows
        $activeBorrows = BorrowRecord::where('user_id', $user->id)
            ->where('status', 'borrowed')
            ->count();

        // Overdue books
        $overdueBooks = BorrowRecord::where('user_id', $user->id)
            ->where('status', 'borrowed')
            ->where('due_date', '<', now())
            ->count();

        // Favorite genres (based on borrowed books)
        $favoriteGenres = BorrowRecord::with('book.categories')
            ->where('user_id', $user->id)
            ->get()
            ->pluck('book.categories')
            ->flatten()
            ->groupBy('name')
            ->map(function ($categories) {
                return $categories->count();
            })
            ->sortDesc()
            ->take(5);

        // Monthly borrowing trend (last 6 months)
        $monthlyTrend = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $count = BorrowRecord::where('user_id', $user->id)
                ->whereYear('borrowed_date', $month->year)
                ->whereMonth('borrowed_date', $month->month)
                ->count();
            $monthlyTrend[] = [
                'month' => $month->format('M Y'),
                'count' => $count
            ];
        }

        return response()->json([
            'stats' => [
                'books_borrowed' => $booksBorrowed,
                'books_returned' => $booksReturned,
                'active_borrows' => $activeBorrows,
                'overdue_books' => $overdueBooks,
                'favorite_genres' => $favoriteGenres,
                'monthly_trend' => $monthlyTrend
            ],
            'period' => $period
        ]);
    }

    /**
     * Export history to CSV
     */
    private function exportHistoryCsv($records, $user)
    {
        $csvData = [];
        $csvData[] = ['Borrowing History Export'];
        $csvData[] = ['Student', $user->firstname . ' ' . $user->lastname];
        $csvData[] = ['Email', $user->email];
        $csvData[] = ['Export Date', now()->format('Y-m-d H:i:s')];
        $csvData[] = [];

        $csvData[] = [
            'Book Title', 'Author', 'ISBN', 'Borrowed Date',
            'Due Date', 'Returned Date', 'Status', 'Days Overdue', 'Fine Amount'
        ];

        foreach ($records as $record) {
            // With automatic returns, no books should be overdue
            $daysOverdue = 0;
            $fineAmount = $record->fines->sum('amount');

            $csvData[] = [
                $record->book ? $record->book->title : 'Unknown',
                $record->book ? $record->book->authors->pluck('name')->implode(', ') : 'Unknown',
                $record->book ? $record->book->isbn : '',
                $record->borrowed_date ? $record->borrowed_date->format('Y-m-d') : '',
                $record->due_date ? $record->due_date->format('Y-m-d') : '',
                $record->returned_date ? $record->returned_date->format('Y-m-d') : '',
                $record->status,
                $daysOverdue,
                $fineAmount
            ];
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
            ->header('Content-Disposition', 'attachment; filename="borrowing-history-' . date('Y-m-d') . '.csv"');
    }
}
