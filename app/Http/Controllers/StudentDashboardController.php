<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
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
        $recommendedBooks = $this->buildCourseResourceQuery($user)
            ->whereDoesntHave('borrowRecords', function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->where('status', 'borrowed');
            })
            ->withCount('borrowRecords')
            ->orderByDesc('borrow_records_count')
            ->orderByDesc('created_at')
            ->limit(6)
            ->get();

        $courseRelatedBooks = $recommendedBooks;
        $popularBooks = collect();

        // Get recent e-resources for horizontal carousel using query caching (3 minutes TTL)
        $recentBooks = Cache::remember('student_dashboard_recent_books', 180, function () {
            return Book::where('availability_status', 'available')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
        });

        $recentEJournalResources = $this->buildCourseResourceQuery($user)
            ->where('resource_type', 'e_journal')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Backward-compatible alias for older dashboard view references.
        $recentEBookResources = $recentEJournalResources;

        $recentThesisResources = $this->buildCourseResourceQuery($user)
            ->where('resource_type', 'thesis')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('dashboard.student', compact(
            'user', 'currentBorrows', 'recentHistory',
            'totalBorrows', 'activeBorrows', 'overdueBooks',
            'recommendedBooks', 'courseRelatedBooks', 'popularBooks',
            'recentBooks', 'recentEJournalResources', 'recentEBookResources', 'recentThesisResources'
        ));
    }

    private function buildCourseResourceQuery($user)
    {
        $courseVariants = $this->getUserCourseVariants($user);

        $query = Book::where('availability_status', 'available');

        if (empty($courseVariants)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function ($courseQuery) use ($courseVariants) {
            foreach ($courseVariants as $variant) {
                $courseQuery->orWhere('course', $variant)
                    ->orWhere('program', $variant)
                    ->orWhere('course', 'LIKE', '%' . $variant . '%')
                    ->orWhere('program', 'LIKE', '%' . $variant . '%');
            }
        });
    }

    private function getUserCourseVariants($user): array
    {
        $variants = collect([
            $user->course_name ?? null,
            $user->course ?? null,
        ]);

        if ($user->course_id) {
            $course = DB::table('courses')->find($user->course_id);
            if ($course) {
                $variants = $variants->merge([
                    $course->name ?? null,
                    $course->code ?? null,
                ]);
            }
        }

        $courseMappings = [
            'BSIT' => ['Information Technology', 'BS Information Technology'],
            'BSN' => ['Nursing', 'BS Nursing'],
            'BSNURSING' => ['Nursing', 'BS Nursing'],
            'NURSING' => ['BSN', 'BS Nursing'],
            'BSHM' => ['Hospitality Management', 'BS Hospitality Management'],
            'HM' => ['BSHM', 'Hospitality Management'],
            'BSED' => ['Education', 'BS Education'],
            'EDUCATION' => ['BSED', 'BS Education'],
            'BSE' => ['Entrepreneurship', 'BS Entrepreneurship'],
            'BSENTREP' => ['Entrepreneurship', 'BS Entrepreneurship'],
            'ENTREP' => ['BS Entrepreneurship', 'Entrepreneurship'],
            'BSBM' => ['Business Management', 'BS Business Management'],
            'BUSINESS MANAGEMENT' => ['BSBM', 'BS Business Management'],
            'BSTOURISM' => ['Tourism', 'BS Tourism'],
            'TOURISM' => ['BSTourism', 'BS Tourism'],
        ];

        $variants = $variants->flatMap(function ($value) use ($courseMappings) {
            $value = trim((string) $value);
            if ($value === '') {
                return [];
            }

            $normalized = strtoupper(str_replace(['.', '-', '_'], '', $value));

            return array_merge([$value], $courseMappings[$normalized] ?? []);
        });

        return $variants
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique(fn ($value) => strtolower($value))
            ->values()
            ->all();
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
                    'days_remaining' => $borrow->days_remaining,
                    'days_overdue' => $borrow->days_past_due
                ];
            });

        return response()->json([
            'borrows' => $borrows
        ]);
    }

    /**
     * Show student profile page
     */
    public function profile()
    {
        $user = Auth::guard('student')->user();
        return view('dashboard.profile', compact('user'));
    }

    /**
     * Update student profile
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::guard('student')->user();

        $rules = [
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'mi' => 'nullable|string|max:10',
            'course' => 'nullable|string|max:255',
            'campus' => 'nullable|string|max:255',
            'year' => 'nullable|string|max:255',
            'gender' => 'nullable|string|max:50',
        ];

        // Add password validation rules if password fields are filled
        if ($request->filled('new_password')) {
            $rules['current_password'] = 'required|string';
            $rules['new_password'] = 'required|string|min:8|confirmed';
        }

        $validated = $request->validate($rules);

        // Verify current password if user is attempting to change password
        if ($request->filled('new_password')) {
            if (!\Illuminate\Support\Facades\Hash::check($request->current_password, $user->password)) {
                return back()->withErrors(['current_password' => 'The provided current password is incorrect.'])->withInput();
            }
            $user->password = \Illuminate\Support\Facades\Hash::make($request->new_password);
        }

        // Update profile fields (email is explicitly omitted to prevent modification)
        $user->firstname = $validated['firstname'];
        $user->lastname = $validated['lastname'];
        $user->mi = $validated['mi'] ?? $user->mi;
        $user->name = trim($validated['firstname'] . ' ' . $validated['lastname']);
        $user->course = $validated['course'] ?? $user->course;
        $user->campus = $validated['campus'] ?? $user->campus;
        $user->year = $validated['year'] ?? $user->year;
        $user->gender = $validated['gender'] ?? $user->gender;

        $user->save();

        return back()->with('success', 'Profile updated successfully!');
    }
}
