<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Book;
use App\Models\BorrowRecord;
use Carbon\Carbon;

class StudentDashboardController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth:student');
        $this->middleware(function ($request, $next) {
            if (!Auth::guard('student')->check()) {
                if ($request->expectsJson()) {
                    return response()->json(['error' => 'Authentication required'], 401);
                }
                return redirect()->route('login');
            }

            // Check if user is a student
            if (!Auth::guard('student')->user()->isStudent()) {
                if ($request->expectsJson()) {
                    return response()->json(['error' => 'Student access required'], 403);
                }
                return redirect()->route('login');
            }

            return $next($request);
        });
    }

    /**
     * Show the student dashboard
     */
    public function index()
    {
        $user = Auth::guard('student')->user();

        // Get user's current borrows
        $currentBorrows = BorrowRecord::with('book')
            ->where('user_id', $user->id)
            ->where('status', 'borrowed')
            ->get();

        // Get user's recent history
        $recentHistory = BorrowRecord::with('book')
            ->where('user_id', $user->id)
            ->orderBy('borrowed_date', 'desc')
            ->limit(5)
            ->get();

        // Get user's statistics
        $totalBorrows = BorrowRecord::where('user_id', $user->id)->count();
        $activeBorrows = $currentBorrows->count();
        $overdueBooks = $currentBorrows->filter(function ($borrow) {
            return $borrow->due_date < now();
        })->count();


        // Get user's course for personalized recommendations
        $userCourse = null;
        if ($user->course_id) {
            $course = \Illuminate\Support\Facades\DB::table('courses')->find($user->course_id);
            $userCourse = $course ? $course->name : null;
        }
        
        // Get course-related books (books related to student's course)
        $courseRelatedBooks = [];
        if ($userCourse) {
            $courseRelatedBooks = Book::where('availability_status', 'available')
                ->whereDoesntHave('borrowRecords', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->where('course', $userCourse)
                ->withCount('borrowRecords')
                ->orderByDesc('borrow_records_count')
                ->limit(3)
                ->get();
        }

        // Get popular books (overall popular not borrowed by user)
        $popularBooks = Book::where('availability_status', 'available')
            ->whereDoesntHave('borrowRecords', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->withCount('borrowRecords')
            ->orderByDesc('borrow_records_count')
            ->limit(3)
            ->get();

        // If no course-related books, show popular books as recommendations
        if ($courseRelatedBooks->isEmpty()) {
            $courseRelatedBooks = $popularBooks->take(3);
            $popularBooks = collect(); // Clear popular books to avoid duplicates
        }

        // Combine recommendations: course-related first, then popular
        $recommendedBooks = $courseRelatedBooks->concat($popularBooks)->take(6);

        // Get recent books for horizontal carousel
        $recentBooks = Book::orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('dashboard.student', compact(
            'user', 'currentBorrows', 'recentHistory',
            'totalBorrows', 'activeBorrows', 'overdueBooks',
            'recommendedBooks', 'courseRelatedBooks', 'popularBooks',
            'recentBooks'
        ));
    }

    /**
     * Get dashboard statistics via API
     */
    public function getStats(Request $request)
    {
        $user = Auth::guard('student')->user();

        try {
            // Current borrows
            $currentBorrows = BorrowRecord::where('user_id', $user->id)
                ->where('status', 'borrowed')
                ->count();

            // Total borrows
            $totalBorrows = BorrowRecord::where('user_id', $user->id)->count();

            // Overdue books
            $overdueBooks = BorrowRecord::where('user_id', $user->id)
                ->where('status', 'borrowed')
                ->where('due_date', '<', now())
                ->count();



            // Recent activity (last 30 days)
            $recentBorrows = BorrowRecord::where('user_id', $user->id)
                ->where('borrowed_date', '>=', now()->subDays(30))
                ->count();

            // Books due soon (next 3 days)
            $booksDueSoon = BorrowRecord::where('user_id', $user->id)
                ->where('status', 'borrowed')
                ->where('due_date', '>=', now())
                ->where('due_date', '<=', now()->addDays(3))
                ->count();

            return response()->json([
                'current_borrows' => $currentBorrows,
                'total_borrows' => $totalBorrows,
                'recent_borrows' => $recentBorrows,
                'books_due_soon' => $booksDueSoon
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to load dashboard statistics',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get recent activity
     */
    public function getRecentActivity(Request $request)
    {
        $user = Auth::guard('student')->user();
        $limit = $request->get('limit', 10);

        $activities = BorrowRecord::with('book')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($borrow) {
                return [
                    'type' => $borrow->status === 'returned' ? 'return' : 'borrow',
                    'book_title' => $borrow->book ? $borrow->book->title : 'Unknown Book',
                    'date' => $borrow->borrowed_date,
                    'status' => $borrow->status,
                    'due_date' => $borrow->due_date,
                    'returned_date' => $borrow->returned_date
                ];
            });

        return response()->json([
            'activities' => $activities
        ]);
    }

    /**
     * Get notifications/alerts for student
     */
    public function getNotifications(Request $request)
    {
        $user = Auth::guard('student')->user();
        $notifications = [];

        // Books due soon (within 3 days)
        $booksDueSoon = BorrowRecord::with('book')
            ->where('user_id', $user->id)
            ->where('status', 'borrowed')
            ->where('due_date', '>=', now())
            ->where('due_date', '<=', now()->addDays(3))
            ->get();

        foreach ($booksDueSoon as $borrow) {
            $daysLeft = now()->diffInDays($borrow->due_date);
            $notifications[] = [
                'type' => 'due_soon',
                'severity' => $daysLeft <= 1 ? 'danger' : 'warning',
                'title' => 'Book Due Soon',
                'message' => "\"{$borrow->book->title}\" is due in {$daysLeft} day(s)",
                'action_url' => route('student.loans'),
                'created_at' => now()
            ];
        }

        // Overdue books
        $overdueBooks = BorrowRecord::with('book')
            ->where('user_id', $user->id)
            ->where('status', 'borrowed')
            ->where('due_date', '<', now())
            ->get();

        foreach ($overdueBooks as $borrow) {
            $daysOverdue = now()->diffInDays($borrow->due_date);

            $notifications[] = [
                'type' => 'overdue',
                'severity' => 'danger',
                'title' => 'Overdue Book',
                'action_url' => route('student.loans'),
                'created_at' => now()
            ];
        }

        return response()->json([
            'notifications' => collect($notifications)->sortByDesc('created_at')->values()
        ]);
    }

    /**
     * Get current borrows for dashboard
     */
    public function getCurrentBorrows(Request $request)
    {
        $user = Auth::guard('student')->user();

        $borrows = BorrowRecord::with('book')
            ->where('user_id', $user->id)
            ->where('status', 'borrowed')
            ->orderBy('due_date', 'asc')
            ->get()
            ->map(function ($borrow) {
                return [
                    'id' => $borrow->id,
                    'book_title' => $borrow->book ? $borrow->book->title : 'Unknown Book',
                    'book_author' => $borrow->book ? $borrow->book->author : 'Unknown Author',
                    'borrowed_date' => $borrow->borrowed_date,
                    'due_date' => $borrow->due_date,
                    'is_overdue' => $borrow->due_date < now(),
                    'days_remaining' => $borrow->due_date >= now() ? now()->diffInDays($borrow->due_date) : 0,
                    'days_overdue' => $borrow->due_date < now() ? now()->diffInDays($borrow->due_date) : 0
                ];
            });

        return response()->json([
            'borrows' => $borrows
        ]);
    }
}
