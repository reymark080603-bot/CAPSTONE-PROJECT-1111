<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Book;
use App\Models\BorrowRecord;
use App\Models\ReturnRecord;
use App\Models\Fine;
use App\Models\Favorite;
use App\Models\Category;
use App\Models\Notification;
use Carbon\Carbon;

class LibrarianDashboardController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            // Check if user is authenticated
            if (!Auth::check()) {
                if ($request->expectsJson()) {
                    return response()->json(['error' => 'Authentication required'], 401);
                }
                return redirect()->route('login');
            }

            // For now, allow any authenticated user (for testing)
            // Later you can uncomment this to restrict to staff only:
            // if (Auth::user()->role !== 'staff') {
            //     if ($request->expectsJson()) {
            //         return response()->json(['error' => 'Staff access required'], 403);
            //     }
            //     return redirect()->route('home');
            // }

            return $next($request);
        });
    }

    /**
     * Show the librarian dashboard
     */
    public function dashboard()
    {
        $user = Auth::user();

        return view('dashboard.librarian', compact('user'));
    }

    /**
     * Get dashboard statistics
     */
    public function getDashboardStats(Request $request)
    {
        try {
            // Basic counts
            $totalBooks = Book::count();
            $totalStudents = User::where('role_id', 1)->count(); // role_id 1 = student
            $activeBorrows = BorrowRecord::where('status', 'borrowed')->count();
            $availableBooks = Book::where('availability_status', 'available')->count();

        // Books by status
        $booksByStatus = Book::select('availability_status', DB::raw('count(*) as count'))
            ->groupBy('availability_status')
            ->get()
            ->pluck('count', 'availability_status')
            ->toArray();

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
        
        // Debug: Log the data
        Log::info('Borrowed books by course raw:', $borrowedBooksByCourse);
        Log::info('Final course data:', $finalCourseData);

        // Recent activity (last 30 days)
        $recentBorrows = BorrowRecord::where('borrowed_date', '>=', now()->subDays(30))->count();
        $recentReturns = BorrowRecord::where('returned_date', '>=', now()->subDays(30))
            ->whereNotNull('returned_date')->count();
        $recentRegistrations = User::where('role_id', 1)
            ->where('created_at', '>=', now()->subDays(30))->count();

        // Overdue books


        // Borrowing activity by resource type
        $popularCategories = BorrowRecord::query()
            ->join('books', 'borrow_records.book_id', '=', 'books.id')
            ->selectRaw("COALESCE(NULLIF(books.resource_type, ''), 'book') as resource_type")
            ->selectRaw('COUNT(borrow_records.id) as count')
            ->groupBy('books.resource_type')
            ->orderByDesc('count')
            ->get()
            ->map(function ($item) {
                return [
                    'category' => match ($item->resource_type) {
                        'e_journal' => 'E-Journal',
                        'thesis' => 'E-Thesis',
                        default => 'Book',
                    },
                    'count' => (int) $item->count,
                ];
            });

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

            return response()->json([
                'basic_stats' => [
                    'total_books' => $totalBooks,
                    'total_students' => $totalStudents,
                    'active_borrows' => $activeBorrows,
                    'available_books' => $availableBooks,
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
            ]);

        } catch (\Exception $e) {
            Log::error('Dashboard stats error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to load dashboard statistics',
                'message' => $e->getMessage(),
                'basic_stats' => [
                    'total_books' => 0,
                    'total_students' => 0,
                    'active_borrows' => 0,
                    'available_books' => 0
                ],
                'books_by_status' => [],
                'recent_activity' => ['borrows' => 0, 'returns' => 0, 'registrations' => 0],
                'popular_categories' => [],
                'monthly_trends' => [],
                'most_borrowed_books' => [],
                'course_stats' => []
            ], 500);
        }
    }

    /**
     * Get recent activities
     */
    public function getRecentActivities(Request $request)
    {
        $limit = $request->get('limit', 20);

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

        return response()->json([
            'activities' => $allActivities
        ]);
    }

    /**
     * Get notifications for librarian
     */
    public function getNotifications(Request $request)
    {
        $user = Auth::user();

        // Create notifications for new events if not already exist
        $this->createNotificationsForUser($user);

        $notifications = Notification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'message' => $notification->message,
                    'is_read' => $notification->is_read,
                    'created_at' => $notification->created_at,
                    'data' => $notification->data
                ];
            });

        return response()->json([
            'notifications' => $notifications
        ]);
    }

    /**
     * Mark notification as read
     */
    public function markNotificationAsRead(Request $request, $id)
    {
        $user = Auth::user();
        $notification = Notification::where('id', $id)->where('user_id', $user->id)->first();

        if ($notification) {
            $notification->markAsRead();
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false, 'message' => 'Notification not found'], 404);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllNotificationsAsRead(Request $request)
    {
        $user = Auth::user();
        Notification::where('user_id', $user->id)->unread()->update([
            'is_read' => true,
            'read_at' => now()
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Clear all notifications
     */
    public function clearAllNotifications(Request $request)
    {
        $user = Auth::user();
        Notification::where('user_id', $user->id)->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Create notifications for user based on events
     */
    private function createNotificationsForUser($user)
    {
        // New registrations in last 24 hours
        $newRegistrations = User::whereHas('role', function($q) {
            $q->where('name', 'student');
        })
            ->where('created_at', '>=', now()->subDay())
            ->count();

        if ($newRegistrations > 0) {
            $existing = Notification::where('user_id', $user->id)
                ->where('type', 'new_registrations')
                ->where('created_at', '>=', now()->subDay())
                ->first();

            if (!$existing) {
                Notification::create([
                    'user_id' => $user->id,
                    'type' => 'new_registrations',
                    'message' => "{$newRegistrations} new student(s) registered in the last 24 hours",
                    'data' => ['count' => $newRegistrations, 'action_url' => '/librarian/students']
                ]);
            }
        }

        // Books returned today (auto-returned)
        $autoReturnedToday = BorrowRecord::where('status', 'returned')
            ->whereDate('returned_date', today())
            ->whereNotNull('notes')
            ->where('notes', 'like', '%Auto-returned%')
            ->count();

        if ($autoReturnedToday > 0) {
            $existing = Notification::where('user_id', $user->id)
                ->where('type', 'auto_returns')
                ->whereDate('created_at', today())
                ->first();

            if (!$existing) {
                Notification::create([
                    'user_id' => $user->id,
                    'type' => 'auto_returns',
                    'message' => "{$autoReturnedToday} book(s) were auto-returned today due to overdue status",
                    'data' => ['count' => $autoReturnedToday, 'action_url' => '/librarian/reports?type=borrowing']
                ]);
            }
        }
    }

    /**
     * Quick actions for dashboard
     */
    public function quickAction(Request $request)
    {
        $action = $request->get('action');
        $result = ['success' => false, 'message' => 'Invalid action'];

        switch ($action) {
            case 'mark_book_returned':
                $borrowId = $request->get('borrow_id');
                $borrowRecord = BorrowRecord::find($borrowId);

                if ($borrowRecord && $borrowRecord->status === 'borrowed') {
                    $borrowRecord->update([
                        'returned_date' => now(),
                        'status' => 'returned'
                    ]);

                    // Open access system - books remain available

                    $result = [
                        'success' => true,
                        'message' => 'Book marked as returned successfully'
                    ];
                } else {
                    $result = [
                        'success' => false,
                        'message' => 'Borrow record not found or book already returned'
                    ];
                }
                break;

            case 'send_overdue_reminder':
                $userId = $request->get('user_id');
                $user = User::find($userId);

                if ($user) {
                    // In a real application, you would send an email here
                    // For now, we'll just return success
                    $result = [
                        'success' => true,
                        'message' => 'Reminder sent to ' . $user->firstname . ' ' . $user->lastname
                    ];
                } else {
                    $result = [
                        'success' => false,
                        'message' => 'User not found'
                    ];
                }
                break;

            default:
                $result = [
                    'success' => false,
                    'message' => 'Unknown action'
                ];
        }

        return response()->json($result);
    }

    /**
     * Export dashboard data
     */
    public function exportData(Request $request)
    {
        $type = $request->get('type', 'overview');

        switch ($type) {
            case 'statistics':
                return $this->exportStatistics();
            default:
                return $this->exportOverview();
        }
    }


    private function exportStatistics()
    {
        $stats = $this->getDashboardStats(request())->getData(true);

        $csvData = [];
        $csvData[] = ['Metric', 'Value'];
        $csvData[] = ['Total Books', $stats['basic_stats']['total_books']];
        $csvData[] = ['Total Students', $stats['basic_stats']['total_students']];
        $csvData[] = ['Active Borrows', $stats['basic_stats']['active_borrows']];
        $csvData[] = ['Available Books', $stats['basic_stats']['available_books']];

        return $this->generateCsvResponse($csvData, 'library-statistics-' . date('Y-m-d') . '.csv');
    }

    private function exportOverview()
    {
        // Export general overview data
        return $this->exportStatistics();
    }

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
