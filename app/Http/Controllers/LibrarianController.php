<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\Book;
use App\Models\BorrowRecord;
use App\Models\ReturnRecord;
use App\Models\Fine;
use App\Models\Category;
use App\Models\Notification;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;
use App\Services\PdfCoverService;
use App\Services\LibrarianNotificationService;
use Illuminate\Support\Facades\Storage;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class LibrarianController extends Controller
{
    protected PdfCoverService $pdfCoverService;
    protected LibrarianNotificationService $librarianNotificationService;

    /**
     * Create a new controller instance.
     */
    public function __construct(PdfCoverService $pdfCoverService, LibrarianNotificationService $librarianNotificationService)
    {
        $this->pdfCoverService = $pdfCoverService;
        $this->librarianNotificationService = $librarianNotificationService;
        $this->middleware('auth:librarian');
        
        $this->middleware(function ($request, $next) {
            // Check if user is authenticated
            if (!Auth::guard('librarian')->check()) {
                if ($request->expectsJson()) {
                    return response()->json(['error' => 'Authentication required'], 401);
                }
                return redirect()->route('librarian.login');
            }
            
            // Ensure the user has admin/librarian privileges
            if (!Auth::guard('librarian')->user()->hasAdminPrivileges()) {
                if ($request->expectsJson()) {
                    return response()->json(['error' => 'Admin access required'], 403);
                }
                return redirect()->route('login');
            }
            
            return $next($request);
        });
    }

    /**
     * Show the librarian dashboard
     */
    public function dashboard()
    {
        // Get the authenticated user
        $user = Auth::guard('librarian')->user();
        
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
        $recentRegistrations = User::whereHas('role', function($query) {
                $query->where('name', 'student');
            })
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

        $programOptions = ['BSE', 'BSHM', 'BSIT', 'BSN', 'BSTM'];

        // Student distribution by program
        $courseStatsRaw = User::query()
            ->leftJoin('courses', 'users.course_id', '=', 'courses.id')
            ->whereHas('role', function($query) {
                $query->where('name', 'student');
            })
            ->selectRaw("COALESCE(NULLIF(courses.code, ''), 'Unassigned') as program")
            ->selectRaw('COUNT(users.id) as student_count')
            ->groupBy('program')
            ->get();

        $courseStats = collect($programOptions)
            ->map(function ($program) use ($courseStatsRaw) {
                $match = $courseStatsRaw->firstWhere('program', $program);

                return [
                    'program' => $program,
                    'student_count' => (int) optional($match)->student_count,
                ];
            })
            ->values();

        $genderDistribution = User::query()
            ->whereHas('role', function($query) {
                $query->where('name', 'student');
            })
            ->selectRaw("LOWER(COALESCE(NULLIF(gender, ''), 'not specified')) as gender")
            ->selectRaw('COUNT(*) as count')
            ->groupBy('gender')
            ->get()
            ->map(function ($item) {
                return [
                    'gender' => $item->gender,
                    'count' => (int) $item->count,
                ];
            })
            ->values();

        $campusDistribution = User::query()
            ->whereHas('role', function($query) {
                $query->where('name', 'student');
            })
            ->selectRaw("COALESCE(NULLIF(campus, ''), 'Unassigned') as campus")
            ->selectRaw('COUNT(*) as count')
            ->groupBy('campus')
            ->orderBy('campus')
            ->get()
            ->map(function ($item) {
                return [
                    'campus' => $item->campus,
                    'count' => (int) $item->count,
                ];
            })
            ->values();

        // Books distribution by course/program for dashboard charts
        $booksByCourse = array_fill_keys($programOptions, 0);

        Book::query()
            ->get(['course', 'program'])
            ->each(function ($book) use (&$booksByCourse) {
                $course = trim((string) ($book->course ?: $book->program ?: ''));

                if (isset($booksByCourse[$course])) {
                    $booksByCourse[$course]++;
                }
            });

            return response()->json([
                'basic_stats' => [
                    'total_books' => $totalBooks,
                    'total_students' => $totalStudents,
                    'active_borrows' => $activeBorrows,
                    'available_books' => $availableBooks,
                    'total_loans' => $totalLoans,
                ],
                'books_by_status' => $booksByStatus,
                'recent_activity' => [
                    'borrows' => $recentBorrows,
                    'returns' => $recentReturns,
                    'registrations' => $recentRegistrations
                ],
                'popular_categories' => $popularCategories,
                'monthly_trends' => $monthlyBorrows,
                'most_borrowed_books' => $mostBorrowedBooks,
                'course_stats' => $courseStats,
                'books_by_course' => $booksByCourse,
                'gender_distribution' => $genderDistribution,
                'campus_distribution' => $campusDistribution,
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
                    'available_books' => 0,
                    'total_loans' => 0
                ],
                'books_by_status' => [],
                'recent_activity' => ['borrows' => 0, 'returns' => 0, 'registrations' => 0],
                'popular_categories' => [],
                'monthly_trends' => [],
                'most_borrowed_books' => [],
                'course_stats' => [],
                'books_by_course' => [],
                'gender_distribution' => [],
                'campus_distribution' => []
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
        $recentRegistrations = User::whereHas('role', function($query) {
                $query->where('name', 'student');
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
                        'course' => $user->course?->name ?? 'N/A',
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
            'success' => true,
            'activities' => $allActivities
        ]);
    }

    /**
     * Get overdue books with details
     */

    /**
     * Get notifications for the librarian
     */
    public function getNotifications(Request $request)
    {
        $librarian = Auth::guard('librarian')->user();

        $query = Notification::where('user_id', $librarian->id)
            ->orderBy('created_at', 'desc');

        // Filter by read status if specified
        if ($request->has('read')) {
            $isRead = filter_var($request->get('read'), FILTER_VALIDATE_BOOLEAN);
            $query->where('is_read', $isRead);
        }

        $limit = $request->get('limit', 50);
        $notifications = $query->limit($limit)->get()->map(function ($notification) {
            return [
                'id' => $notification->id,
                'type' => $notification->type,
                'message' => $notification->message,
                'description' => data_get($notification->data, 'description', ''),
                'data' => $notification->data,
                'is_read' => $notification->is_read,
                'read_at' => $notification->read_at,
                'created_at' => $notification->created_at,
            ];
        });

        $unreadCount = Notification::where('user_id', $librarian->id)
            ->unread()
            ->count();

        return response()->json([
            'success' => true,
            'notifications' => $notifications,
            'unread_count' => $unreadCount
        ]);
    }

    /**
     * Get unread notifications count for the librarian
     */
    public function getUnreadNotificationsCount(Request $request)
    {
        $librarian = Auth::guard('librarian')->user();

        $count = Notification::where('user_id', $librarian->id)
            ->unread()
            ->count();

        return response()->json([
            'success' => true,
            'unread_count' => $count
        ]);
    }

    /**
     * Mark a notification as read
     */
    public function markNotificationAsRead(Request $request, Notification $notification)
    {
        // Ensure the notification belongs to the current librarian
        if ($notification->user_id !== Auth::guard('librarian')->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read'
        ]);
    }

    /**
     * Mark all notifications as read for the librarian
     */
    public function markAllNotificationsAsRead(Request $request)
    {
        $librarian = Auth::guard('librarian')->user();

        Notification::where('user_id', $librarian->id)
            ->unread()
            ->update([
                'is_read' => true,
                'read_at' => now()
            ]);

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read'
        ]);
    }

    /**
     * Delete a notification
     */
    public function deleteNotification(Request $request, Notification $notification)
    {
        // Ensure the notification belongs to the current librarian
        if ($notification->user_id !== Auth::guard('librarian')->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notification deleted'
        ]);
    }

    /**
     * Clear all notifications for the librarian
     */
    public function clearAllNotifications(Request $request)
    {
        $librarian = Auth::guard('librarian')->user();
        
        // Delete all notifications for the current librarian
        Notification::where('user_id', $librarian->id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'All notifications cleared'
        ]);
    }

    /**
     * Create a notification for a user (librarian can send notifications to students)
     */
    public function createNotification(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'type' => 'required|string|max:50',
            'message' => 'required|string|max:1000',
            'data' => 'nullable|array'
        ]);

        // Optional: Check if the target user is a student
        $targetUser = User::find($request->user_id);
        if (!$targetUser || !$targetUser->isStudent()) {
            return response()->json([
                'success' => false,
                'message' => 'Can only send notifications to students'
            ], 400);
        }

        $notification = Notification::create([
            'user_id' => $request->user_id,
            'type' => $request->type,
            'message' => $request->message,
            'data' => $request->data,
            'is_read' => false
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Notification sent successfully',
            'notification' => $notification
        ]);
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

    // ===== LOANS MANAGEMENT METHODS =====

    /**
     * Display manage loans page
     */
    public function manageLoans(Request $request)
    {
        return view('librarian.loans.index');
    }

    /**
     * Get loans data for AJAX requests
     */
    public function getLoansData(Request $request)
    {
        try {
            $search = trim((string) $request->get('search'));
            $status = $request->get('status'); // borrowed|overdue|returned|all
            $dateFrom = $request->get('date_from');
            $dateTo = $request->get('date_to');

            $query = BorrowRecord::with(['user.course', 'user.yearLevel', 'book.authors', 'book.publisher']);

            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('user', function ($uq) use ($search) {
                        $uq->where('firstname', 'LIKE', "%{$search}%")
                           ->orWhere('lastname', 'LIKE', "%{$search}%")
                           ->orWhere('library_id', 'LIKE', "%{$search}%");
                    })->orWhereHas('book', function ($bq) use ($search) {
                        $bq->where('title', 'LIKE', "%{$search}%");
                    });
                });
            }

            if ($status && $status !== 'all') {
                if ($status === 'overdue') {
                    $query->borrowed()->where('due_date', '<', now());
                } else {
                    $query->where('status', $status);
                }
            }

        if ($request->filled('course')) {
                $query->whereHas('user', function ($uq) use ($request) {
                    $uq->whereHas('course', function($cq) use ($request) {
                        $cq->where('name', $request->get('course'));
                    });
                });
            }
            if ($request->filled('year')) {
                $query->whereHas('user', function ($uq) use ($request) {
                    $uq->whereHas('yearLevel', function($yl) use ($request) {
                        $yl->where('level', $request->get('year'));
                    });
                });
            }
            if ($request->filled('campus')) {
                $query->whereHas('user', function ($uq) use ($request) {
                    $uq->where('campus', $request->get('campus'));
                });
            }

            if ($dateFrom) {
                $query->whereDate('borrowed_date', '>=', $dateFrom);
            }
            if ($dateTo) {
                $query->whereDate('borrowed_date', '<=', $dateTo);
            }

            $query->orderByDesc('borrowed_date');

            $loans = $query->limit(200)->get()->map(function ($loan) {
                $program = $loan->user->course?->code
                    ?? $loan->user->course?->name
                    ?? $loan->user->course
                    ?? '';

                return [
                    'id' => $loan->id,
                    'student' => trim(($loan->user->firstname ?? '') . ' ' . ($loan->user->lastname ?? '')),
                    'program' => $program,
                    'year' => $loan->user->yearLevel?->level ?? $loan->user->year ?? '',
                    'campus' => $loan->user->campus ?? '',
                    'book_title' => $loan->book->title ?? 'Unknown',
                    'author' => $loan->book?->author ?? 'Unknown Author',
                    'borrowed_date' => optional($loan->borrowed_date)->toDateString(),
                    'due_date' => optional($loan->due_date)->toDateString(),
                    'returned_date' => optional($loan->returned_date)->toDateString(),
                    'status' => $loan->status,
                ];
            });

            return response()->json([
                'success' => true,
                'loans' => $loans,
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load loans data: ' . $e->getMessage(),
                'loans' => []
            ]);
        }
    }

    /**
     * Mark a loan as returned
     */
    public function returnLoan(Request $request, BorrowRecord $borrowRecord)
    {
        if ($borrowRecord->status !== 'borrowed') {
            return response()->json(['success' => false, 'message' => 'Loan is not active'], 400);
        }

        $borrowRecord->update([
            'returned_date' => now(),
            'status' => 'returned'
        ]);

        // Update book availability
        $book = $borrowRecord->book;
        if ($book) {
            $book->update(['availability_status' => 'available']);
        }

        return response()->json(['success' => true, 'message' => 'Book marked as returned']);
    }

    /**
     * Renew a loan (extend due date)
     */
    public function renewLoan(Request $request, BorrowRecord $borrowRecord)
    {
        if ($borrowRecord->status !== 'borrowed') {
            return response()->json(['success' => false, 'message' => 'Only active loans can be renewed'], 400);
        }

        $days = (int) ($request->get('days', 7));
        $maxRenewals = 2;
        $currentCount = (int) ($borrowRecord->renewal_count ?? 0);
        if ($currentCount >= $maxRenewals) {
            return response()->json(['success' => false, 'message' => 'Renewal limit reached'], 400);
        }

        $newDue = (clone $borrowRecord->due_date)->addDays($days);
        $borrowRecord->update([
            'due_date' => $newDue,
            'renewal_count' => $currentCount + 1,
        ]);

        return response()->json(['success' => true, 'message' => 'Loan renewed', 'due_date' => $newDue->toDateString()]);
    }

    // ===== BOOK MANAGEMENT METHODS =====

    /**
     * Display book management page
     */
    public function manageBooks(Request $request)
    {
        return view('librarian.books.Manage Book');
    }

    /**
     * Get books data for DataTable
     */
    public function getBooksData(Request $request)
    {
        $query = Book::query();

        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%")
                  ->orWhere('course', 'LIKE', "%{$search}%")
                  ->orWhere('isbn', 'LIKE', "%{$search}%")
                  ->orWhere('publisher', 'LIKE', "%{$search}%")
                  // Legacy columns for backward compatibility
                  ->orWhere('author', 'LIKE', "%{$search}%")
                  ->orWhere('category', 'LIKE', "%{$search}%")
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

        // Filter by resource type
        if ($request->has('resource_type') && !empty($request->resource_type)) {
            $query->where('resource_type', $request->resource_type);
        }

        // Filter by category (support both normalized relation and legacy column)
        if ($request->has('category') && !empty($request->category)) {
            $selected = $request->category;
            $query->where(function ($q) use ($selected) {
                $q->whereHas('categories', function ($c) use ($selected) {
                    $c->where('name', $selected);
                })->orWhere('category', $selected);
            });
        }

        // Filter by course
        if ($request->has('course') && !empty($request->course)) {
            $query->where('course', $request->course);
        }

        // Filter by availability
        if ($request->has('availability') && !empty($request->availability)) {
            $query->where('availability_status', $request->availability);
        }

        // Get total count before pagination
        $totalRecords = $query->count();

        // Pagination
        $start = $request->get('start', 0);
        $length = $request->get('length', 10);
        
        $titleSort = $request->get('title_sort');
        if (in_array($titleSort, ['asc', 'desc'], true)) {
            $query->orderBy('title', $titleSort);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $books = $query->with(['authors', 'categories', 'publisher'])
                      ->offset($start)
                      ->limit($length)
                      ->get();

        $totalBooksCount = Book::count();
        
        $response = [
            'draw' => intval($request->get('draw', 1)),
            'recordsTotal' => $totalBooksCount,
            'recordsFiltered' => $totalRecords,
            'data' => $books->map(function($book) {
                $authorNames = $book->authors->pluck('name')->implode(', ');
                $categoryNames = $book->categories->pluck('name')->implode(', ');

                return [
                    'id' => $book->id,
                    'title' => $book->title ?? 'N/A',
                    'author' => $authorNames !== '' ? $authorNames : ($book->getOriginal('author') ?? 'N/A'),
                    'isbn' => $book->isbn ?? '',
                    'publisher' => $book->publisher_name,
                    'resource_type' => $book->resource_type ?? 'book',
                    'category' => $categoryNames !== '' ? $categoryNames : ($book->getOriginal('category') ?? ''),
                    'course' => $book->course ?: ($book->program ?? ''),
                    'program' => $book->program ?? '',
                    'year_level' => $book->year_level ?? '',
                    'availability_status' => $book->availability_status ?? 'available',
                    'published_year' => $book->published_year ?? '',
                    'cover_photo' => $book->cover_photo ?? null,
                    'cover_image' => $book->cover_image ?? null,
                    'cover_url' => $book->display_cover_url,
                    'created_at' => $book->created_at ? $book->created_at->format('Y-m-d H:i:s') : '',
                    'actions' => [
                        'edit_url' => route('librarian.books.edit', $book->id),
                        'show_url' => route('librarian.books.show', $book->id),
                        'delete_url' => route('librarian.books.destroy', $book->id)
                    ]
                ];
            })
        ];
        
        return response()->json($response);
    }

    /**
     * Bulk upload books and resources
     */
    public function bulkUpload(Request $request)
    {
        try {
            $request->validate([
                'files' => 'required|array|min:1',
                'files.*' => 'required|file|max:102400|mimes:pdf,epub,doc,docx,jpg,jpeg,png',
                'covers.*' => 'nullable|image|mimes:jpg,jpeg,png|max:5120',
                'cover_map' => 'nullable|string',
                'resource_type' => 'required|in:book,e_journal,thesis',
                'category' => 'nullable|string',
                'course' => 'nullable|string'
            ]);

            $uploadedCount = 0;
            $errors = [];
            $createdIds = [];

            $coverMap = [];
            $coverMapRaw = $request->input('cover_map');
            if (is_string($coverMapRaw) && $coverMapRaw !== '') {
                $decoded = json_decode($coverMapRaw, true);
                if (is_array($decoded)) {
                    $coverMap = $decoded;
                }
            }
            $coverFiles = $request->file('covers', []);

            foreach ($request->file('files') as $index => $file) {
                try {
                    // Extract title from filename (remove extension)
                    $title = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $originalName = $file->getClientOriginalName();
                    
                    // Create book record
                    $book = new Book();
                    $book->title = $title;
                    if (\Illuminate\Support\Facades\Schema::hasColumn('books', 'author')) $book->author = 'Bulk Upload';
                    if (\Illuminate\Support\Facades\Schema::hasColumn('books', 'resource_type')) $book->resource_type = $request->resource_type;
                    if (\Illuminate\Support\Facades\Schema::hasColumn('books', 'category')) $book->category = $request->category;
                    if (\Illuminate\Support\Facades\Schema::hasColumn('books', 'course')) $book->course = $request->course;
                    if (\Illuminate\Support\Facades\Schema::hasColumn('books', 'availability_status')) $book->availability_status = 'available';
                    if (\Illuminate\Support\Facades\Schema::hasColumn('books', 'copies_total')) $book->copies_total = 1;
                    if (\Illuminate\Support\Facades\Schema::hasColumn('books', 'copies_available')) $book->copies_available = 1;
                    if (\Illuminate\Support\Facades\Schema::hasColumn('books', 'description')) $book->description = 'Bulk uploaded resource';
                    if (\Illuminate\Support\Facades\Schema::hasColumn('books', 'published_year')) $book->published_year = intval(date('Y'));
                    if (\Illuminate\Support\Facades\Schema::hasColumn('books', 'language')) $book->language = 'English';
                    
                    // Handle different resource types
                    switch ($request->resource_type) {
                        case 'e_journal':
                            if (\Illuminate\Support\Facades\Schema::hasColumn('books', 'volume')) $book->volume = '1';
                            if (\Illuminate\Support\Facades\Schema::hasColumn('books', 'issue')) $book->issue = '1';
                            break;
                        case 'thesis':
                            if (\Illuminate\Support\Facades\Schema::hasColumn('books', 'degree')) $book->degree = 'Undergraduate';
                            if (\Illuminate\Support\Facades\Schema::hasColumn('books', 'defense_date')) $book->defense_date = now();
                            break;
                    }
                    
                    // Store file based on type
                    $extension = strtolower($file->getClientOriginalExtension());
                    
                    if (in_array($extension, ['jpg', 'jpeg', 'png'])) {
                        if (\Illuminate\Support\Facades\Schema::hasColumn('books', 'cover_photo')) $book->cover_photo = $this->storeCoverPhoto($file);
                        if (\Illuminate\Support\Facades\Schema::hasColumn('books', 'cover_image')) $book->cover_image = $book->cover_photo;
                        if (\Illuminate\Support\Facades\Schema::hasColumn('books', 'file_type')) $book->file_type = 'html';
                    } elseif (in_array($extension, ['pdf', 'epub', 'doc', 'docx'])) {
                        $type = $extension;
                        if ($extension === 'docx') {
                            $type = 'doc';
                        }
                        
                        $path = $this->storeEbookFile($file, $type);
                        $column = $type . '_file';
                        if (\Illuminate\Support\Facades\Schema::hasColumn('books', $column)) $book->{$column} = $path;
                        if ($type === 'pdf' && \Illuminate\Support\Facades\Schema::hasColumn('books', 'file_path')) $book->file_path = $path;
                        if (\Illuminate\Support\Facades\Schema::hasColumn('books', 'file_type')) $book->file_type = $type;

                        if (\Illuminate\Support\Facades\Schema::hasColumn('books', 'cover_photo') && isset($coverMap[$originalName])) {
                            $coverIndex = $coverMap[$originalName];
                            if (is_int($coverIndex) || ctype_digit((string) $coverIndex)) {
                                $coverIndex = intval($coverIndex);
                                if (isset($coverFiles[$coverIndex])) {
                                    $book->cover_photo = $this->storeCoverPhoto($coverFiles[$coverIndex]);
                                    if (\Illuminate\Support\Facades\Schema::hasColumn('books', 'cover_image')) $book->cover_image = $book->cover_photo;
                                }
                            }
                        }

                        // If this is a PDF and no manual cover is mapped, auto-generate from first page.
                        if ($type === 'pdf' && empty($book->cover_photo)) {
                            $generatedCover = $this->generatePdfCoverFromStoredPath($path, $title);
                            if (!empty($generatedCover)) {
                                if (\Illuminate\Support\Facades\Schema::hasColumn('books', 'cover_image')) {
                                    $book->cover_image = $generatedCover;
                                }
                                if (\Illuminate\Support\Facades\Schema::hasColumn('books', 'cover_photo')) {
                                    $book->cover_photo = $generatedCover;
                                }
                            }
                        }
                    } else {
                        throw new \Exception("Unsupported file type: {$extension}");
                    }
                    
                    $book->save();
                    $uploadedCount++;
                    $createdIds[] = $book->id;
                    
                } catch (\Exception $e) {
                    $errors[] = "Failed to upload {$file->getClientOriginalName()}: " . $e->getMessage();
                }
            }

            if ($uploadedCount === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No files were uploaded.',
                    'uploaded_count' => 0,
                    'errors' => $errors
                ], 422);
            }

            \Illuminate\Support\Facades\Cache::flush();

            return response()->json([
                'success' => true,
                'message' => "Successfully uploaded {$uploadedCount} files",
                'uploaded_count' => $uploadedCount,
                'created_ids' => $createdIds,
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Bulk upload failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show form to create new book
     */
    public function createBook()
    {
        $categories = Category::orderBy('name')->pluck('name');
        $courses = ['BSE', 'BSHM', 'BSIT', 'BSN', 'BSTM'];
        
        return view('librarian.books.Add Book', compact('categories', 'courses'));
    }

    /**
     * Store new book
     */
    public function storeBook(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'resource_type' => 'required|in:book,e_journal,thesis',
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'isbn' => 'nullable|string|max:20|unique:books,isbn',
            'category' => 'nullable|string|max:100',
            'course' => 'nullable|string|max:50',
            'resource_type' => 'required|in:book,e_journal,thesis',
            'description' => 'nullable|string',
            'published_year' => 'nullable|integer|min:1000|max:' . date('Y'),
            'publisher' => 'nullable|string|max:255',
            'language' => 'nullable|string|max:50',
            'cover_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'ebook_file' => 'nullable|file|mimes:pdf,epub,doc,docx|max:102400', // 100MB
            // E-Journal specific fields
            'volume' => 'nullable|string|max:50|required_if:resource_type,e_journal',
            'issue' => 'nullable|string|max:50|required_if:resource_type,e_journal',
            // Thesis specific fields
            'advisor' => 'nullable|string|max:255|required_if:resource_type,thesis',
            'defense_date' => 'nullable|date|required_if:resource_type,thesis',
            'degree' => 'nullable|string|max:100|required_if:resource_type,thesis'
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Check for duplicate book based on multiple criteria for accuracy
        $duplicateQuery = Book::where('title', trim($request->title));
        
        // If ISBN is provided, use it as the primary duplicate check (most reliable)
        if ($request->filled('isbn')) {
            $existingBook = Book::where('isbn', trim($request->isbn))->first();
            if ($existingBook) {
                $message = 'This book already exists in the system (Book ID: ' . $existingBook->getCustomIdAttribute() . '). ';
                
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $message . 'The ISBN matches an existing book.',
                        'duplicate_book_id' => $existingBook->id,
                        'duplicate_book' => [
                            'id' => $existingBook->id,
                            'title' => $existingBook->title,
                            'author' => $existingBook->author,
                            'isbn' => $existingBook->isbn,
                            'custom_id' => $existingBook->getCustomIdAttribute()
                        ]
                    ], 422);
                }
                
                return redirect()->back()
                    ->with('error', $message . 'The ISBN matches an existing book.')
                    ->with('duplicate_book_id', $existingBook->id)
                    ->withInput();
            }
        }
        
        // If no ISBN, check combination of title + author + publisher + year for more accuracy
        $duplicateQuery->where('author', trim($request->author));
        
        // Only include publisher in duplicate check if it's provided
        if ($request->filled('publisher')) {
            $duplicateQuery->where('publisher', trim($request->publisher));
        }
        
        // Only check published year if provided for more accurate duplicate detection
        if ($request->filled('published_year')) {
            $duplicateQuery->where('published_year', $request->published_year);
        }
        
        $existingBook = $duplicateQuery->first();
        
        if ($existingBook) {
            $message = 'This book already exists in the system (Book ID: ' . $existingBook->getCustomIdAttribute() . '). ';
            
            // Provide more specific guidance based on what matched
            if ($request->filled('publisher') && $request->filled('published_year')) {
                $duplicateReason = 'The title, author, publisher, and year match an existing book.';
            } elseif ($request->filled('publisher')) {
                $duplicateReason = 'The title, author, and publisher match an existing book.';
            } else {
                $duplicateReason = 'The title and author match an existing book with the same publisher and year.';
            }
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message . $duplicateReason . ' Please edit the existing book instead of creating a duplicate.',
                    'duplicate_book_id' => $existingBook->id,
                    'duplicate_book' => [
                        'id' => $existingBook->id,
                        'title' => $existingBook->title,
                        'author' => $existingBook->author,
                        'publisher' => $existingBook->publisher,
                        'published_year' => $existingBook->published_year,
                        'custom_id' => $existingBook->getCustomIdAttribute()
                    ]
                ], 422);
            }
            
            return redirect()->back()
                ->with('error', $message . $duplicateReason . ' Please edit the existing book instead of creating a duplicate.')
                ->with('duplicate_book_id', $existingBook->id)
                ->withInput();
        }

        $bookData = $request->except(['cover_photo', 'ebook_file', 'publisher', 'author', 'category']);
        $bookData['availability_status'] = 'available';
        
        // Handle resource type
        $bookData['resource_type'] = $request->resource_type ?? 'book';
        
        // Handle ISBN field
        if ($request->filled('isbn')) {
            $bookData['isbn'] = trim($request->isbn);
        }
        
        // Handle published_year field (no mapping needed since field name matches database)
        if ($request->filled('published_year')) {
            $bookData['published_year'] = $request->published_year;
        }
        
        // Handle E-Journal specific fields
        if ($request->resource_type === 'e_journal') {
            if ($request->filled('volume')) {
                $bookData['volume'] = trim($request->volume);
            }
            if ($request->filled('issue')) {
                $bookData['issue'] = trim($request->issue);
            }
        }
        
        // Handle Thesis specific fields
        if ($request->resource_type === 'thesis') {
            if ($request->filled('advisor')) {
                $bookData['advisor'] = trim($request->advisor);
            }
            if ($request->filled('defense_date')) {
                $bookData['defense_date'] = $request->defense_date;
            }
            if ($request->filled('degree')) {
                $bookData['degree'] = $request->degree;
            }
        }
        
        // Handle publisher relationship
        if ($request->filled('publisher')) {
            $publisher = \App\Models\Publisher::firstOrCreate(['name' => $request->publisher]);
            $bookData['publisher_id'] = $publisher->id;
        }
        
        // Handle author relationship
        $authorIds = [];
        if ($request->filled('author')) {
            $authorNames = explode(',', $request->author);
            foreach ($authorNames as $authorName) {
                $author = \App\Models\Author::firstOrCreate(['name' => trim($authorName)]);
                $authorIds[] = $author->id;
            }
        }
        
        // Only update category relationships when the form explicitly sends category.
        $categoryIds = null;
        if ($request->has('category')) {
            $categoryIds = [];
            if ($request->filled('category')) {
                $category = \App\Models\Category::firstOrCreate(['name' => $request->category]);
                $categoryIds[] = $category->id;
            }
        }
        
        // Handle cover photo upload
        if ($request->hasFile('cover_photo')) {
            $bookData['cover_photo'] = $this->storeCoverPhoto($request->file('cover_photo'));
        }
        
        // Handle ebook file upload
        if ($request->hasFile('ebook_file')) {
            $file = $request->file('ebook_file');
            $extension = strtolower($file->getClientOriginalExtension());
            
            // Determine file type and store accordingly
            switch ($extension) {
                case 'pdf':
                    $bookData['pdf_file'] = $this->storeEbookFile($file, 'pdf');
                    $bookData['file_type'] = 'pdf';
                    break;
                case 'epub':
                    $bookData['epub_file'] = $this->storeEbookFile($file, 'epub');
                    $bookData['file_type'] = 'epub';
                    break;
                case 'doc':
                case 'docx':
                    $bookData['doc_file'] = $this->storeEbookFile($file, 'doc');
                    $bookData['file_type'] = 'doc';
                    break;
                default:
                    // This shouldn't happen due to validation, but just in case
                    $bookData['file_type'] = 'html';
            }
        } else {
            // If no file uploaded, default to HTML content
            $bookData['file_type'] = 'html';
        }

        // Auto-generate PDF cover when no manual cover was uploaded.
        if (empty($bookData['cover_photo']) && !empty($bookData['pdf_file'])) {
            $generatedCover = $this->generatePdfCoverFromStoredPath($bookData['pdf_file'], $bookData['title'] ?? 'book');
            if (!empty($generatedCover)) {
                $bookData['cover_image'] = $generatedCover;
                $bookData['cover_photo'] = $generatedCover; // Keep legacy views compatible
            }
        }

        try {
            $book = Book::create($bookData);
            \Illuminate\Support\Facades\Cache::flush();
            
            // Sync author relationships if provided
            if (!empty($authorIds)) {
                $book->authors()->sync($authorIds);
            }
            
            // Sync category relationships if provided
            if (!empty($categoryIds)) {
                $book->categories()->sync($categoryIds);
            }

            $librarian = Auth::guard('librarian')->user() ?: Auth::user();
            if ($librarian) {
                $this->librarianNotificationService->notifySingleResourceUploaded($librarian, $book, 'manual_create');
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Book created successfully',
                    'book' => $book
                ]);
            }
            
            return redirect()->route('librarian.books.index')->with('success', 'Book created successfully!');
            
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create book: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->back()->with('error', 'Failed to create book: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Show book details
     */
    public function showBook(Request $request, Book $book)
    {
        // If it's an API request, return JSON
        if ($request->expectsJson() || $request->wantsJson()) {
            $book->load(['borrowRecords.user', 'authors', 'categories', 'publisher']);
            return response()->json([
                'success' => true,
                'book' => $book
            ]);
        }

        // Otherwise return the view
        $book->load(['borrowRecords.user', 'authors', 'categories', 'publisher']);
        return view('librarian.books.View Book', compact('book'));
    }

    /**
     * Show form to edit book
     */
    public function editBook(Book $book)
    {
        // Load relationships for the accessors to work
        $book->load(['authors', 'categories', 'publisher']);
        
        $categories = Category::orderBy('name')->pluck('name');
        $courses = ['BSE', 'BSHM', 'BSIT', 'BSN', 'BSTM'];
        
        return view('librarian.books.Edit Book', compact('book', 'categories', 'courses'));
    }

    /**
     * Update book
     */
    public function updateBook(Request $request, Book $book)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
            'course' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'published_year' => 'nullable|integer|min:1000|max:' . date('Y'),
            'publisher' => 'nullable|string|max:255',
            'language' => 'nullable|string|max:50',
            'cover_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $bookData = $request->except(['cover_photo', 'publisher', 'author', 'category']);
        
        // Handle published_year field (no mapping needed since field name matches database)
        if ($request->filled('published_year')) {
            $bookData['published_year'] = $request->published_year;
        }
        
        // Keep publisher unchanged unless a publisher field is explicitly submitted.
        if ($request->has('publisher')) {
            if ($request->filled('publisher')) {
                $publisher = \App\Models\Publisher::firstOrCreate(['name' => $request->publisher]);
                $bookData['publisher_id'] = $publisher->id;
            } else {
                $bookData['publisher_id'] = null;
            }
        }
        
        // Handle author relationship
        $authorIds = [];
        if ($request->filled('author')) {
            $authorNames = explode(',', $request->author);
            foreach ($authorNames as $authorName) {
                $author = \App\Models\Author::firstOrCreate(['name' => trim($authorName)]);
                $authorIds[] = $author->id;
            }
        }
        
        // Handle category relationship
        $categoryIds = [];
        if ($request->filled('category')) {
            $category = \App\Models\Category::firstOrCreate(['name' => $request->category]);
            $categoryIds[] = $category->id;
        }
        
        // Handle file upload
        if ($request->hasFile('cover_photo')) {
            // Delete old cover photo if exists
            $this->deleteCoverPhoto($book->cover_photo);
            
            // Store new cover photo
            $bookData['cover_photo'] = $this->storeCoverPhoto($request->file('cover_photo'));
        }

        try {
            $book->update($bookData);
            \Illuminate\Support\Facades\Cache::flush();
            
            // Sync author relationships if provided
            if (isset($authorIds)) {
                $book->authors()->sync($authorIds);
            }
            
            // Leave existing categories untouched when category is not part of the form.
            if ($categoryIds !== null) {
                $book->categories()->sync($categoryIds);
            }

            return response()->json([
                'success' => true,
                'message' => 'Book updated successfully',
                'book' => $book
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update book: ' . $e->getMessage(),
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while updating the book.'
            ], 500);
        }
    }

    /**
     * Delete book
     */
    public function destroyBook(Book $book)
    {
        // Check if book has active borrows
        $activeBorrows = BorrowRecord::where('book_id', $book->id)
                                   ->where('status', 'borrowed')
                                   ->count();
        
        if ($activeBorrows > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete book with active borrows'
            ], 400);
        }

        try {
            DB::beginTransaction();

            $this->deleteCoverPhoto($book->cover_photo);
            $this->deleteEbookFile($book->pdf_file);
            $this->deleteEbookFile($book->epub_file);
            $this->deleteEbookFile($book->doc_file);

            $book->authors()->detach();
            $book->categories()->detach();

            $book->delete();
            \Illuminate\Support\Facades\Cache::flush();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Book deleted successfully'
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Delete Book Error', [
                'book_id' => $book->id,
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete book.'
            ], 500);
        }
    }

    // ===== HELPER METHODS FOR FILE MANAGEMENT =====

    /**
     * Generate a PDF first-page cover from a stored public path.
     */
    private function generatePdfCoverFromStoredPath(string $storedPdfPath, string $title): ?string
    {
        $normalized = ltrim($storedPdfPath, '/');

        if (str_starts_with($normalized, 'storage/')) {
            $relative = substr($normalized, 8);
            $fullPdfPath = \Illuminate\Support\Facades\Storage::disk('public')->path($relative);
        } else {
            $fullPdfPath = \Illuminate\Support\Facades\Storage::disk('public')->path($normalized);
        }

        if (!file_exists($fullPdfPath) || !is_readable($fullPdfPath)) {
            Log::warning('PDF cover generation skipped: file missing/unreadable', [
                'stored_path' => $storedPdfPath,
                'full_path' => $fullPdfPath,
            ]);
            return null;
        }

        $generated = $this->pdfCoverService->generateCover($fullPdfPath, $title);

        return $generated !== false ? $generated : null;
    }

    /**
     * Store uploaded cover photo
     */
    private function storeCoverPhoto($file)
    {
        try {
            // If Cloudinary is configured, use it
            if (env('CLOUDINARY_URL')) {
                $result = Cloudinary::upload($file->getRealPath(), [
                    'folder' => 'knowly/covers'
                ]);
                return $result->getSecurePath();
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Cloudinary Upload Failed: ' . $e->getMessage());
        }

        // Fallback to local storage
        $path = $file->store('uploads/book-covers', 'public');
        return 'storage/' . $path;
    }

    /**
     * Delete cover photo file
     */
    private function deleteCoverPhoto($coverPhotoPath)
    {
        if (!$coverPhotoPath) return false;

        $normalized = ltrim($coverPhotoPath, '/');

        try {
            if (file_exists(public_path($normalized))) {
                return unlink(public_path($normalized));
            }
        } catch (\Throwable $e) {
        }

        $storagePath = str_replace('storage/', '', $normalized);
        $storagePath = ltrim($storagePath, '/');

        try {
            if (\Illuminate\Support\Facades\Storage::disk('public')->exists($storagePath)) {
                return \Illuminate\Support\Facades\Storage::disk('public')->delete($storagePath);
            }
        } catch (\Throwable $e) {
        }

        return false;
    }
    
    /**
     * Store uploaded ebook file
     */
    private function storeEbookFile($file, $type)
    {
        // Store relative path on the public disk (e.g. books/pdfs/abc.pdf)
        return $file->store('books/' . $type . 's', 'public');
    }
    
    /**
     * Delete ebook file
     */
    private function deleteEbookFile($filePath)
    {
        if (!$filePath) return false;

        $normalized = ltrim($filePath, '/');

        try {
            if (file_exists(public_path($normalized))) {
                return unlink(public_path($normalized));
            }
        } catch (\Throwable $e) {
        }

        $storagePath = str_replace('storage/', '', $normalized);
        $storagePath = ltrim($storagePath, '/');

        try {
            if (\Illuminate\Support\Facades\Storage::disk('public')->exists($storagePath)) {
                return \Illuminate\Support\Facades\Storage::disk('public')->delete($storagePath);
            }
        } catch (\Throwable $e) {
        }

        return false;
    }

    /**
     * Get file information
     */
    private function getCoverPhotoInfo($coverPhotoPath)
    {
        if (!$coverPhotoPath) {
            return null;
        }
        
        $fullPath = public_path($coverPhotoPath);
        
        if (!file_exists($fullPath)) {
            return null;
        }
        
        return [
            'path' => $coverPhotoPath,
            'full_path' => $fullPath,
            'url' => asset($coverPhotoPath),
            'size' => filesize($fullPath),
            'mime_type' => mime_content_type($fullPath),
            'exists' => true
        ];
    }

    // ===== STUDENT MANAGEMENT METHODS =====

    /**
     * Display student management page
     */
    public function manageStudents(Request $request)
    {
        return view('librarian.students.index');
    }

    /**
     * Get students data for DataTable
     */
    public function getStudentsData(Request $request)
    {
        $query = User::whereHas('role', function($query) {
            $query->where('name', 'student');
        });

        // Search functionality (avoid referencing non-existent columns)
        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function($q) use ($search) {
                $q->where('firstname', 'LIKE', "%{$search}%")
                  ->orWhere('lastname', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('library_id', 'LIKE', "%{$search}%")
                  ->orWhere('course', 'LIKE', "%{$search}%");
            });
        }

          // Filter by course
          if ($request->filled('course')) {
              $query->where('course', $request->course);
          }

          // Filter by campus
          if ($request->filled('campus')) {
              $query->where('campus', $request->campus);
          }

        // Filter by year level
        if ($request->filled('year')) {
            $query->whereHas('yearLevel', function($yl) use ($request) {
                $yl->where('level', $request->year);
            });
        }

        // Total count before pagination
        $totalRecords = (clone $query)->count();

        // Determine mode
        $simple = filter_var($request->get('simple', false), FILTER_VALIDATE_BOOLEAN);

        // Pagination / limits
        if ($simple) {
            $start = 0;
            $length = (int) $request->get('limit', 200); // show up to 200 by default in simple mode
        } else {
            $start = (int) $request->get('start', 0);
            $length = (int) $request->get('length', 10);
            if ($length <= 0) { $length = 10; }
        }

        $students = $query->with(['course', 'yearLevel', 'role'])
                         ->orderBy('created_at', 'desc')
                         ->offset($start)
                         ->limit($length)
                         ->get();

        $totalStudentsCount = User::whereHas('role', function($query) {
            $query->where('name', 'student');
        })->count();

        $data = $students->map(function($student) {
            return [
                'id' => $student->id,
                'name' => trim(($student->firstname ?? '') . ' ' . ($student->lastname ?? '')),
                'firstname' => $student->firstname ?? '',
                'lastname' => $student->lastname ?? '',
                'mi' => $student->mi ?? '',
                'gender' => $student->gender ?? '',
                'email' => $student->email ?? '',
                  'library_id' => $student->library_id ?? '',
                  'course' => $student->course?->name ?? '',
                  'year' => $student->yearLevel?->level ?? $student->year ?? '',
                  'campus' => $student->campus ?? '',
                  'created_at' => optional($student->created_at)->format('Y-m-d H:i:s') ?? '',
                  'email_verified_at' => $student->email_verified_at,
                  'status' => $student->email_verified_at ? 'Active' : 'Pending',
            ];
        })->values()->all();

        if ($simple) {
            return response()->json([
                'success' => true,
                'total' => $totalRecords,
                'students' => $data,
            ]);
        }

        return response()->json([
            'draw' => intval($request->get('draw', 1)),
            'recordsTotal' => $totalStudentsCount,
            'recordsFiltered' => $totalRecords,
            'data' => $data
        ]);
    }

    /**
     * Show student details
     */
    public function showStudent(Request $request, User $user)
    {
        if (!$user->isStudent()) {
            return response()->json([
                'success' => false,
                'message' => 'User is not a student'
            ], 400);
        }

        // Load student's borrowing history and statistics
        $user->load(['borrowRecords.book']);

        $statistics = [
            'total_borrowed' => $user->borrowRecords->count(),
            'currently_borrowed' => $user->borrowRecords->where('status', 'borrowed')->count(),
            'total_returned' => $user->borrowRecords->where('status', 'returned')->count(),
            'overdue_books' => $user->borrowRecords->where('status', 'borrowed')
                                                  ->where('due_date', '<', now())
                                                  ->count(),
        ];

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'student' => $user,
                'statistics' => $statistics
            ]);
        }
        
        return view('librarian.students.show', compact('user', 'statistics'));
    }

    /**
     * Activate student account
     */
    public function activateStudent(User $user)
    {
        if (!$user->isStudent()) {
            return response()->json([
                'success' => false,
                'message' => 'User is not a student'
            ], 400);
        }

        $user->email_verified_at = now();
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Student account activated successfully'
        ]);
    }

    /**
     * Deactivate student account
     */
    public function deactivateStudent(User $user)
    {
        if (!$user->isStudent()) {
            return response()->json([
                'success' => false,
                'message' => 'User is not a student'
            ], 400);
        }

        $user->email_verified_at = null;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Student account deactivated successfully'
        ]);
    }

    /**
     * Delete student account
     */
    public function deleteStudent(User $user)
    {
        if (!$user->isStudent()) {
            return response()->json([
                'success' => false,
                'message' => 'User is not a student'
            ], 400);
        }

        // Check if student has active borrows
        $activeBorrows = BorrowRecord::where('user_id', $user->id)
                                   ->where('status', 'borrowed')
                                   ->count();
        
        if ($activeBorrows > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete student with active book borrowings'
            ], 400);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Student account deleted successfully'
        ]);
    }
    
    // ===== REPORTS MANAGEMENT METHODS =====
    
    /**
     * Show reports dashboard
     */
    public function reportsIndex()
    {
        // Get summary data for reports dashboard
        $totalBooks = Book::count();
        $totalStudents = User::whereHas('role', function($query) {
            $query->where('name', 'student');
        })->count();
        $activeBorrows = BorrowRecord::where('status', 'borrowed')->count();
        $returnedThisMonth = BorrowRecord::where('status', 'returned')
                                       ->whereMonth('returned_date', now()->month)
                                       ->whereYear('returned_date', now()->year)
                                       ->count();
        $borrowedThisMonth = BorrowRecord::whereMonth('borrowed_date', now()->month)
                                       ->whereYear('borrowed_date', now()->year)
                                       ->count();
        
        $summaryStats = [
            'total_books' => $totalBooks,
            'total_students' => $totalStudents,
            'active_borrows' => $activeBorrows,
            'returned_this_month' => $returnedThisMonth,
            'borrowed_this_month' => $borrowedThisMonth,
        ];
        return view('librarian.reports.index', compact('summaryStats'));
    }
    
    /**
     * Generate custom report based on parameters
     */
    public function generateReport(Request $request)
    {
        $type = $request->get('type');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        $course = $request->get('course');
        $year = $request->get('year');

        switch ($type) {
            case 'borrowing-statistics':
                return $this->borrowingStatisticsReport($request);
            case 'student-activity':
                return $this->studentActivityReport($request);
            case 'book-usage':
                return $this->bookUsageReport($request);
            case 'overdue-books':
                return $this->overdueBooksReport($request);
            case 'popular-books':
                return $this->popularBooksReport($request);
            case 'course-analysis':
                return $this->courseAnalysisReport($request);
            case 'monthly-summary':
                return $this->monthlySummaryReport($request);
            default:
                return response()->json(['error' => 'Invalid report type'], 400);
        }
    }
    
    /**
     * Borrowing Statistics Report
     */
    public function borrowingStatisticsReport(?Request $request = null)
    {
        $dateFrom = $request ? $request->get('date_from', now()->subMonths(6)->toDateString()) : now()->subMonths(6)->toDateString();
        $dateTo = $request ? $request->get('date_to', now()->toDateString()) : now()->toDateString();
        
        // Overall statistics
        $totalBorrows = BorrowRecord::whereBetween('borrowed_date', [$dateFrom, $dateTo])->count();
        $totalReturns = BorrowRecord::whereBetween('returned_date', [$dateFrom, $dateTo])
                                  ->whereNotNull('returned_date')
                                  ->count();
        $averagePerDay = $totalBorrows / max(1, Carbon::parse($dateFrom)->diffInDays(Carbon::parse($dateTo)));
        
        // Monthly breakdown (database agnostic)
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
        } else { // mysql/mariadb
            $monthlyQuery->selectRaw('YEAR(borrowed_date) as year')
                         ->selectRaw('MONTH(borrowed_date) as month')
                         ->selectRaw('COUNT(*) as count');
        }
        $monthlyData = $monthlyQuery
            ->whereBetween('borrowed_date', [$dateFrom, $dateTo])
            ->groupBy('year', 'month')
            ->orderBy('year', 'asc')
            ->orderBy('month', 'asc')
            ->get()
            ->map(function($item) {
                return [
                    'period' => Carbon::createFromDate((int)$item->year, (int)$item->month, 1)->format('M Y'),
                    'count' => (int)$item->count
                ];
            });
        
        // Category breakdown (normalized categories)
        $categoryData = Book::join('borrow_records', 'books.id', '=', 'borrow_records.book_id')
                           ->join('book_category', 'books.id', '=', 'book_category.book_id')
                           ->join('categories', 'categories.id', '=', 'book_category.category_id')
                           ->whereBetween('borrow_records.borrowed_date', [$dateFrom, $dateTo])
                           ->selectRaw('categories.name as category, COUNT(*) as count')
                           ->groupBy('categories.name')
                           ->orderByDesc('count')
                           ->get();
        
        $data = [
            'period' => ['from' => $dateFrom, 'to' => $dateTo],
            'summary' => [
                'total_borrows' => $totalBorrows,
                'total_returns' => $totalReturns,
                'average_per_day' => round($averagePerDay, 2)
            ],
            'monthly_data' => $monthlyData,
            'category_data' => $categoryData
        ];
        
        if ($request && $request->expectsJson()) {
            return response()->json(['success' => true, 'data' => $data]);
        }
        
        return view('librarian.reports.borrowing-statistics', compact('data'));
    }
    
    /**
     * Student Activity Report
     */
    public function studentActivityReport(?Request $request = null)
    {
        $dateFrom = $request ? $request->get('date_from', now()->subMonths(3)->toDateString()) : now()->subMonths(3)->toDateString();
        $dateTo = $request ? $request->get('date_to', now()->toDateString()) : now()->toDateString();
        $course = $request ? $request->get('course') : null;
        $year = $request ? $request->get('year') : null;
        
        $query = User::whereHas('role', function($query) {
                    $query->where('name', 'student');
                })
                    ->with(['borrowRecords' => function($q) use ($dateFrom, $dateTo) {
                        $q->whereBetween('borrowed_date', [$dateFrom, $dateTo]);
                    }]);
        
        if ($course) {
            $query->whereHas('course', function($cq) use ($course) {
                $cq->where('name', $course);
            });
        }
        
        if ($year) {
            $query->where('year', $year);
        }
        
        $students = $query->get()->map(function($student) {
            $borrowCount = $student->borrowRecords->count();
            $returnedCount = $student->borrowRecords->whereNotNull('returned_date')->count();
            $overdueCount = $student->borrowRecords->where('status', 'borrowed')
                                                  ->where('due_date', '<', now())
                                                  ->count();
            
            return [
                'id' => $student->id,
                'name' => trim($student->firstname . ' ' . $student->lastname),
                'library_id' => $student->library_id,
                'course' => $student->course?->name ?? 'N/A',
                'year' => $student->year?->numeric_level ?? 'N/A',
                'email' => $student->email,
                'total_borrowed' => $borrowCount,
                'total_returned' => $returnedCount,
                'currently_borrowed' => $borrowCount - $returnedCount,
                'overdue_books' => $overdueCount,
                'activity_level' => $borrowCount > 5 ? 'High' : ($borrowCount > 2 ? 'Medium' : 'Low')
            ];
        })->sortByDesc('total_borrowed');
        
        // Summary statistics
        $totalActiveStudents = $students->where('total_borrowed', '>', 0)->count();
        $averageBorrowsPerStudent = $students->where('total_borrowed', '>', 0)->avg('total_borrowed');
        $mostActiveStudent = $students->first();
        
        $data = [
            'period' => ['from' => $dateFrom, 'to' => $dateTo],
            'filters' => ['course' => $course, 'year' => $year],
            'summary' => [
                'total_active_students' => $totalActiveStudents,
                'average_borrows_per_student' => round($averageBorrowsPerStudent, 2),
                'most_active_student' => $mostActiveStudent
            ],
            'students' => $students->values()->all()
        ];
        
        if ($request && $request->expectsJson()) {
            return response()->json(['success' => true, 'data' => $data]);
        }
        
        return view('librarian.reports.student-activity', compact('data'));
    }
    
    /**
     * Book Usage Report
     */
    public function bookUsageReport(?Request $request = null)
    {
        $dateFrom = $request ? $request->get('date_from', now()->subMonths(6)->toDateString()) : now()->subMonths(6)->toDateString();
        $dateTo = $request ? $request->get('date_to', now()->toDateString()) : now()->toDateString();
        
        // Most borrowed books (normalized categories)
        $mostBorrowedBooks = Book::leftJoin('borrow_records', 'books.id', '=', 'borrow_records.book_id')
                                ->leftJoin('book_category', 'books.id', '=', 'book_category.book_id')
                                ->leftJoin('categories', 'categories.id', '=', 'book_category.category_id')
                                ->leftJoin('author_book', 'books.id', '=', 'author_book.book_id')
                                ->leftJoin('authors', 'authors.id', '=', 'author_book.author_id')
                                ->whereBetween('borrow_records.borrowed_date', [$dateFrom, $dateTo])
                                ->select(
                                    'books.id',
                                    'books.title',
                                    'books.course',
                                    'books.year_level',
                                    DB::raw('MIN(authors.name) as author'),
                                    DB::raw('MIN(categories.name) as category'),
                                    DB::raw('COUNT(borrow_records.id) as borrow_count')
                                )
                                ->groupBy('books.id', 'books.title', 'books.course', 'books.year_level')
                                ->orderByDesc('borrow_count')
                                ->limit(20)
                                ->get();
        
        // Least borrowed books (including never borrowed) - normalized categories
        $leastBorrowedBooks = Book::leftJoin('borrow_records', 'books.id', '=', 'borrow_records.book_id')
                                 ->leftJoin('book_category', 'books.id', '=', 'book_category.book_id')
                                 ->leftJoin('categories', 'categories.id', '=', 'book_category.category_id')
                                 ->leftJoin('author_book', 'books.id', '=', 'author_book.book_id')
                                 ->leftJoin('authors', 'authors.id', '=', 'author_book.author_id')
                                 ->select(
                                     'books.id',
                                     'books.title',
                                     'books.course',
                                     'books.year_level',
                                     'books.created_at',
                                     DB::raw('MIN(authors.name) as author'),
                                     DB::raw('MIN(categories.name) as category'),
                                     DB::raw('COUNT(borrow_records.id) as borrow_count')
                                 )
                                 ->groupBy('books.id', 'books.title', 'books.course', 'books.year_level', 'books.created_at')
                                 ->orderBy('borrow_count', 'asc')
                                 ->orderBy('books.created_at', 'desc')
                                 ->limit(20)
                                 ->get();
        
        // Category usage statistics (normalized categories)
        $categoryUsage = Book::leftJoin('borrow_records', 'books.id', '=', 'borrow_records.book_id')
                            ->leftJoin('book_category', 'books.id', '=', 'book_category.book_id')
                            ->leftJoin('categories', 'categories.id', '=', 'book_category.category_id')
                            ->whereBetween('borrow_records.borrowed_date', [$dateFrom, $dateTo])
                            ->selectRaw('categories.name as category, COUNT(borrow_records.id) as borrow_count, COUNT(DISTINCT books.id) as unique_books')
                            ->whereNotNull('categories.name')
                            ->groupBy('categories.name')
                            ->orderByDesc('borrow_count')
                            ->get();
        
        $data = [
            'period' => ['from' => $dateFrom, 'to' => $dateTo],
            'most_borrowed_books' => $mostBorrowedBooks,
            'least_borrowed_books' => $leastBorrowedBooks,
            'category_usage' => $categoryUsage,
            'total_books' => Book::count(),
            'books_never_borrowed' => Book::leftJoin('borrow_records', 'books.id', '=', 'borrow_records.book_id')
                                        ->whereNull('borrow_records.id')
                                        ->count()
        ];
        
        if ($request && $request->expectsJson()) {
            return response()->json(['success' => true, 'data' => $data]);
        }
        
        return view('librarian.reports.book-usage', compact('data'));
    }
    
    /**
     * Overdue Books Report (Not applicable - books are auto-returned)
     */
    public function overdueBooksReport(?Request $request = null)
    {
        // Since books are automatically returned when due, there are no overdue books
        $data = [
            'period' => ['from' => now()->subMonths(3)->toDateString(), 'to' => now()->toDateString()],
            'filters' => ['course' => null, 'year' => null],
            'summary' => [
                'total_overdue_books' => 0,
                'note' => 'Books are automatically returned when due, so no overdue books exist'
            ],
            'overdue_books' => [],
            'overdue_by_course' => [],
            'overdue_by_category' => []
        ];

        if ($request && $request->expectsJson()) {
            return response()->json(['success' => true, 'data' => $data]);
        }

        return view('librarian.reports.overdue-books', compact('data'));
    }
    
    /**
     * Popular Books Report
     */
    public function popularBooksReport(?Request $request = null)
    {
        $dateFrom = $request ? $request->get('date_from', now()->subYear()->toDateString()) : now()->subYear()->toDateString();
        $dateTo = $request ? $request->get('date_to', now()->toDateString()) : now()->toDateString();
        
        $popularBooks = Book::leftJoin('borrow_records', 'books.id', '=', 'borrow_records.book_id')
                           ->leftJoin('book_category', 'books.id', '=', 'book_category.book_id')
                           ->leftJoin('categories', 'categories.id', '=', 'book_category.category_id')
                           ->leftJoin('author_book', 'books.id', '=', 'author_book.book_id')
                           ->leftJoin('authors', 'authors.id', '=', 'author_book.author_id')
                           ->leftJoin('publishers', 'publishers.id', '=', 'books.publisher_id')
                           ->whereBetween('borrow_records.borrowed_date', [$dateFrom, $dateTo])
                           ->select(
                               'books.id',
                               'books.title',
                               'books.course',
                               'books.year_level',
                               'books.published_year',
                               DB::raw('MIN(authors.name) as author'),
                               DB::raw('MIN(categories.name) as category'),
                               DB::raw('MIN(publishers.name) as publisher'),
                               DB::raw('COUNT(borrow_records.id) as borrow_count'),
                               DB::raw('COUNT(DISTINCT borrow_records.user_id) as unique_borrowers')
                           )
                           ->groupBy('books.id', 'books.title', 'books.course', 'books.year_level', 'books.published_year')
                           ->having('borrow_count', '>', 0)
                           ->orderByDesc('borrow_count')
                           ->limit(50)
                           ->get();
        
        // Popular by category
        $popularByCategory = Book::leftJoin('borrow_records', 'books.id', '=', 'borrow_records.book_id')
                                ->leftJoin('book_category', 'books.id', '=', 'book_category.book_id')
                                ->leftJoin('categories', 'categories.id', '=', 'book_category.category_id')
                                ->whereBetween('borrow_records.borrowed_date', [$dateFrom, $dateTo])
                                ->selectRaw('categories.name as category, COUNT(borrow_records.id) as total_borrows, COUNT(DISTINCT books.id) as unique_books')
                                ->whereNotNull('categories.name')
                                ->groupBy('categories.name')
                                ->orderByDesc('total_borrows')
                                ->get();
        
        // Popular by course
        $popularByCourse = Book::leftJoin('borrow_records', 'books.id', '=', 'borrow_records.book_id')
                              ->whereBetween('borrow_records.borrowed_date', [$dateFrom, $dateTo])
                              ->selectRaw('books.course, COUNT(borrow_records.id) as total_borrows, COUNT(DISTINCT books.id) as unique_books')
                              ->whereNotNull('books.course')
                              ->groupBy('books.course')
                              ->orderByDesc('total_borrows')
                              ->get();
        
        $data = [
            'period' => ['from' => $dateFrom, 'to' => $dateTo],
            'popular_books' => $popularBooks,
            'popular_by_category' => $popularByCategory,
            'popular_by_course' => $popularByCourse
        ];
        
        if ($request && $request->expectsJson()) {
            return response()->json(['success' => true, 'data' => $data]);
        }
        
        return view('librarian.reports.popular-books', compact('data'));
    }
    
    /**
     * Course Analysis Report
     */
    public function courseAnalysisReport(?Request $request = null)
    {
        $dateFrom = $request ? $request->get('date_from', now()->subMonths(6)->toDateString()) : now()->subMonths(6)->toDateString();
        $dateTo = $request ? $request->get('date_to', now()->toDateString()) : now()->toDateString();
        
        $driver = DB::getDriverName();
        $nowExpr = $driver === 'sqlite' ? 'CURRENT_TIMESTAMP' : 'NOW()';
        
        $courseStats = User::whereHas('role', function($query) {
                          $query->where('name', 'student');
                      })
                          ->select('courses.name as course')
                          ->selectRaw('COUNT(DISTINCT users.id) as total_students')
                          ->selectRaw('COUNT(borrow_records.id) as total_borrows')
                          ->selectRaw("COUNT(CASE WHEN borrow_records.status = 'borrowed' THEN 1 END) as active_borrows")
                          ->selectRaw("COUNT(CASE WHEN borrow_records.status = 'borrowed' AND borrow_records.due_date < {$nowExpr} THEN 1 END) as overdue_borrows")
                          ->leftJoin('borrow_records', function($join) use ($dateFrom, $dateTo) {
                              $join->on('users.id', '=', 'borrow_records.user_id')
                                   ->whereBetween('borrow_records.borrowed_date', [$dateFrom, $dateTo]);
                          })
                          ->leftJoin('courses', 'users.course_id', '=', 'courses.id')
                          ->groupBy('courses.name')
                          ->orderBy('courses.name')
                          ->get()
                          ->map(function($course) {
                              $borrowRate = $course->total_students > 0 ? round($course->total_borrows / $course->total_students, 2) : 0;
                              $overdueRate = $course->total_borrows > 0 ? round(($course->overdue_borrows / $course->total_borrows) * 100, 2) : 0;
                              
                              return [
                                  'course' => $course->course,
                                  'total_students' => $course->total_students,
                                  'total_borrows' => $course->total_borrows,
                                  'active_borrows' => $course->active_borrows,
                                  'overdue_borrows' => $course->overdue_borrows,
                                  'borrow_rate_per_student' => $borrowRate,
                                  'overdue_rate_percent' => $overdueRate
                              ];
                          });
        
        // Books popular by course
        $booksByCourse = [];
        foreach ($courseStats as $courseData) {
            $courseBooks = Book::join('borrow_records', 'books.id', '=', 'borrow_records.book_id')
                              ->join('users', 'borrow_records.user_id', '=', 'users.id')
                              ->leftJoin('courses', 'users.course_id', '=', 'courses.id')
                              ->leftJoin('book_category', 'books.id', '=', 'book_category.book_id')
                              ->leftJoin('categories', 'categories.id', '=', 'book_category.category_id')
                              ->leftJoin('author_book', 'books.id', '=', 'author_book.book_id')
                              ->leftJoin('authors', 'authors.id', '=', 'author_book.author_id')
                              ->where('courses.name', $courseData['course'])
                              ->whereBetween('borrow_records.borrowed_date', [$dateFrom, $dateTo])
                              ->select('books.title')
                              ->selectRaw('MIN(authors.name) as author')
                              ->selectRaw('MIN(categories.name) as category')
                              ->selectRaw('COUNT(*) as borrow_count')
                              ->groupBy('books.id', 'books.title')
                              ->orderByDesc('borrow_count')
                              ->limit(5)
                              ->get();
            
            $booksByCourse[$courseData['course']] = $courseBooks;
        }
        
        $data = [
            'period' => ['from' => $dateFrom, 'to' => $dateTo],
            'course_statistics' => $courseStats,
            'books_by_course' => $booksByCourse
        ];
        
        if ($request && $request->expectsJson()) {
            return response()->json(['success' => true, 'data' => $data]);
        }
        
        return view('librarian.reports.course-analysis', compact('data'));
    }

    /**
     * Loans Statistics (by course and year)
     */
    public function loansStatistics(Request $request)
    {
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        $status = $request->get('status'); // borrowed|returned|all

        $base = BorrowRecord::query()
            ->join('users', 'borrow_records.user_id', '=', 'users.id')
            ->leftJoin('courses', 'users.course_id', '=', 'courses.id')
            ->leftJoin('year_levels', 'users.year_level_id', '=', 'year_levels.id')
            ->select('borrow_records.*', 'courses.name as course', 'year_levels.level as year_level');

        if ($dateFrom) {
            $base->whereDate('borrowed_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $base->whereDate('borrowed_date', '<=', $dateTo);
        }
        if ($status && $status !== 'all') {
            $base->where('borrow_records.status', $status);
        }
        // Filter by student's course and year level if provided
        if ($request->filled('course')) {
            $base->where('courses.name', $request->get('course'));
        }
        if ($request->filled('year')) {
            $base->where('year_levels.level', $request->get('year'));
        }
        if ($request->filled('campus')) {
            $base->where('users.campus', $request->get('campus'));
        }

        // Summary totals
        $totalLoans = (clone $base)->count();
        $activeLoans = (clone $base)->where('borrow_records.status', 'borrowed')->count();
        $returnedLoans = (clone $base)->where('borrow_records.status', 'returned')->count();

        // Group by course
        $byCourse = BorrowRecord::query()
            ->join('users', 'borrow_records.user_id', '=', 'users.id')
            ->leftJoin('courses', 'users.course_id', '=', 'courses.id')
            ->selectRaw("courses.name as course, COUNT(borrow_records.id) as total_loans, COUNT(DISTINCT borrow_records.user_id) as unique_borrowers, SUM(CASE WHEN borrow_records.status = 'borrowed' THEN 1 ELSE 0 END) as active_loans, SUM(CASE WHEN borrow_records.status = 'returned' THEN 1 ELSE 0 END) as returned_loans")
            ->when($dateFrom, function($query) use ($dateFrom) {
                $query->whereDate('borrowed_date', '>=', $dateFrom);
            })
            ->when($dateTo, function($query) use ($dateTo) {
                $query->whereDate('borrowed_date', '<=', $dateTo);
            })
            ->when($status && $status !== 'all', function($query) use ($status) {
                $query->where('borrow_records.status', $status);
            })
            ->when($request->filled('course'), function($query) use ($request) {
                $query->where('courses.name', $request->get('course'));
            })
            ->when($request->filled('year'), function($query) use ($request) {
                $query->where('year_levels.level', $request->get('year'));
            })
            ->when($request->filled('campus'), function($query) use ($request) {
                $query->where('users.campus', $request->get('campus'));
            })
            ->groupBy('courses.name')
            ->orderBy('courses.name')
            ->get();

        // Group by year level
        $byYear = BorrowRecord::query()
            ->join('users', 'borrow_records.user_id', '=', 'users.id')
            ->leftJoin('year_levels', 'users.year_level_id', '=', 'year_levels.id')
            ->selectRaw("year_levels.level as year_level, COUNT(borrow_records.id) as total_loans, COUNT(DISTINCT borrow_records.user_id) as unique_borrowers, SUM(CASE WHEN borrow_records.status = 'borrowed' THEN 1 ELSE 0 END) as active_loans, SUM(CASE WHEN borrow_records.status = 'returned' THEN 1 ELSE 0 END) as returned_loans")
            ->when($dateFrom, function($query) use ($dateFrom) {
                $query->whereDate('borrowed_date', '>=', $dateFrom);
            })
            ->when($dateTo, function($query) use ($dateTo) {
                $query->whereDate('borrowed_date', '<=', $dateTo);
            })
            ->when($status && $status !== 'all', function($query) use ($status) {
                $query->where('borrow_records.status', $status);
            })
            ->when($request->filled('course'), function($query) use ($request) {
                $query->where('courses.name', $request->get('course'));
            })
            ->when($request->filled('year'), function($query) use ($request) {
                $query->where('year_levels.level', $request->get('year'));
            })
            ->when($request->filled('campus'), function($query) use ($request) {
                $query->where('users.campus', $request->get('campus'));
            })
            ->groupBy('year_levels.level')
            ->orderBy('year_levels.level')
            ->get();

        return response()->json([
            'success' => true,
            'summary' => [
                'total_loans' => $totalLoans,
                'active_loans' => $activeLoans,
                'returned_loans' => $returnedLoans,
            ],
            'by_course' => $byCourse,
            'by_year' => $byYear,
        ]);
    }

    /**
     * Monthly Summary Report
     */
    public function monthlySummaryReport(?Request $request = null)
    {
        $year = $request ? $request->get('year', now()->year) : now()->year;
        $month = $request ? $request->get('month', now()->month) : now()->month;
        
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();
        $programOptions = ['BSE', 'BSHM', 'BSIT', 'BSN', 'BSTM'];
        
        // Basic statistics for the month
        $monthlyStats = [
            'books_borrowed' => BorrowRecord::whereBetween('borrowed_date', [$startDate, $endDate])->count(),
            'books_returned' => BorrowRecord::whereBetween('returned_date', [$startDate, $endDate])->whereNotNull('returned_date')->count(),
            'new_students' => User::whereHas('role', function($query) {
                $query->where('name', 'student');
            })->whereBetween('created_at', [$startDate, $endDate])->count(),
            'active_students' => BorrowRecord::whereBetween('borrowed_date', [$startDate, $endDate])
                                            ->distinct('user_id')
                                            ->count('user_id'),
            'overdue_books' => BorrowRecord::where('status', 'borrowed')
                                          ->where('due_date', '<', now())
                                          ->whereBetween('borrowed_date', [$startDate, $endDate])
                                          ->count()
        ];
        
        // Daily breakdown
        $dailyStats = [];
        for ($date = $startDate->copy(); $date <= $endDate; $date->addDay()) {
            $dayBorrows = BorrowRecord::whereDate('borrowed_date', $date)->count();
            $dayReturns = BorrowRecord::whereDate('returned_date', $date)->count();
            
            $dailyStats[] = [
                'date' => $date->format('Y-m-d'),
                'day_name' => $date->format('l'),
                'borrows' => $dayBorrows,
                'returns' => $dayReturns
            ];
        }

        $programDistributionRaw = BorrowRecord::query()
            ->join('users', 'borrow_records.user_id', '=', 'users.id')
            ->leftJoin('courses', 'users.course_id', '=', 'courses.id')
            ->whereBetween('borrow_records.borrowed_date', [$startDate, $endDate])
            ->whereHas('user.role', function($query) {
                $query->where('name', 'student');
            })
            ->selectRaw("COALESCE(NULLIF(courses.code, ''), 'Unassigned') as program")
            ->selectRaw('COUNT(DISTINCT users.id) as student_count')
            ->groupBy('program')
            ->get();

        $programDistribution = collect($programOptions)
            ->map(function ($program) use ($programDistributionRaw) {
                $match = $programDistributionRaw->firstWhere('program', $program);

                return [
                    'program' => $program,
                    'student_count' => (int) optional($match)->student_count,
                ];
            })
            ->values();

        $genderDistribution = BorrowRecord::query()
            ->join('users', 'borrow_records.user_id', '=', 'users.id')
            ->whereBetween('borrow_records.borrowed_date', [$startDate, $endDate])
            ->whereHas('user.role', function($query) {
                $query->where('name', 'student');
            })
            ->selectRaw("LOWER(COALESCE(NULLIF(users.gender, ''), 'not specified')) as gender")
            ->selectRaw('COUNT(DISTINCT users.id) as count')
            ->groupBy('gender')
            ->get()
            ->map(function ($item) {
                return [
                    'gender' => $item->gender,
                    'count' => (int) $item->count,
                ];
            })
            ->values();

        $booksByProgram = array_fill_keys($programOptions, 0);

        Book::query()
            ->get(['course', 'program'])
            ->each(function ($book) use (&$booksByProgram) {
                $program = trim((string) ($book->course ?: $book->program ?: ''));

                if (isset($booksByProgram[$program])) {
                    $booksByProgram[$program]++;
                }
            });
        
        // Top categories for the month
        $topCategories = Book::join('borrow_records', 'books.id', '=', 'borrow_records.book_id')
                            ->join('book_category', 'books.id', '=', 'book_category.book_id')
                            ->join('categories', 'categories.id', '=', 'book_category.category_id')
                            ->whereBetween('borrow_records.borrowed_date', [$startDate, $endDate])
                            ->selectRaw('categories.name as category, COUNT(*) as borrow_count')
                            ->whereNotNull('categories.name')
                            ->groupBy('categories.name')
                            ->orderByDesc('borrow_count')
                            ->limit(10)
                            ->get();
        
        // Top students for the month
        $topStudents = User::join('borrow_records', 'users.id', '=', 'borrow_records.user_id')
                          ->whereBetween('borrow_records.borrowed_date', [$startDate, $endDate])
                          ->whereHas('role', function($query) {
                              $query->where('name', 'student');
                          })
                          ->leftJoin('courses', 'users.course_id', '=', 'courses.id')
                          ->selectRaw('users.firstname, users.lastname, users.library_id, courses.name as course, COUNT(*) as borrow_count')
                          ->groupBy('users.id', 'users.firstname', 'users.lastname', 'users.library_id', 'courses.name')
                          ->orderByDesc('borrow_count')
                          ->limit(10)
                          ->get()
                          ->map(function($student) {
                              return [
                                  'name' => trim($student->firstname . ' ' . $student->lastname),
                                  'library_id' => $student->library_id,
                                  'course' => $student->course,
                                  'borrow_count' => $student->borrow_count
                              ];
                          });
        
        // 1. Campus distribution (registered students by campus)
        $campusDistribution = User::query()
            ->whereNotNull('campus')
            ->where('campus', '<>', '')
            ->selectRaw('campus, COUNT(*) as count')
            ->groupBy('campus')
            ->orderByDesc('count')
            ->get()
            ->map(function($item) {
                return [
                    'campus' => $item->campus,
                    'count' => (int) $item->count,
                ];
            });

        // 2. Resource types stats by borrowing activity for the selected month
        $resourceTypes = BorrowRecord::query()
            ->join('books', 'borrow_records.book_id', '=', 'books.id')
            ->whereBetween('borrow_records.borrowed_date', [$startDate, $endDate])
            ->selectRaw("COALESCE(NULLIF(books.resource_type, ''), 'book') as resource_type")
            ->selectRaw('COUNT(*) as count')
            ->groupBy('resource_type')
            ->orderByDesc('count')
            ->get()
            ->map(function($item) {
                return [
                    'category' => match ($item->resource_type) {
                        'e_journal' => 'E-Journal',
                        'thesis' => 'E-Thesis',
                        default => 'Book',
                    },
                    'count' => (int) $item->count,
                ];
            });

        // 3. Most borrowed books (Top 10) for the selected month
        $popularBooks = Book::query()
            ->join('borrow_records', 'books.id', '=', 'borrow_records.book_id')
            ->whereBetween('borrow_records.borrowed_date', [$startDate, $endDate])
            ->select('books.id', 'books.title', 'books.author', DB::raw('COUNT(borrow_records.id) as borrow_count'))
            ->groupBy('books.id', 'books.title', 'books.author')
            ->orderByDesc('borrow_count')
            ->limit(10)
            ->get()
            ->map(function($item) {
                return [
                    'title' => $item->title,
                    'author' => $item->author ?? 'Unknown Author',
                    'borrow_count' => (int) $item->borrow_count,
                ];
            });

        $data = [
            'period' => [
                'year' => $year,
                'month' => $month,
                'month_name' => $startDate->format('F Y'),
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d')
            ],
            'monthly_stats' => $monthlyStats,
            'daily_stats' => $dailyStats,
            'top_categories' => $topCategories,
            'top_students' => $topStudents,
            'program_distribution' => $programDistribution,
            'gender_distribution' => $genderDistribution,
            'books_by_program' => $booksByProgram,
            'campus_distribution' => $campusDistribution,
            'resource_types' => $resourceTypes,
            'popular_books' => $popularBooks,
        ];
        
        if ($request && $request->expectsJson()) {
            return response()->json(['success' => true, 'data' => $data]);
        }
        
        return view('librarian.reports.monthly-summary', compact('data'));
    }
    
    /**
     * Print report
     */
    public function printReport(Request $request, $type)
    {
        // Set parameters for print-friendly output
        $request->merge(['print' => true]);
        
        switch ($type) {
            case 'borrowing-statistics':
                return $this->borrowingStatisticsReport($request);
            case 'student-activity':
                return $this->studentActivityReport($request);
            case 'book-usage':
                return $this->bookUsageReport($request);
            case 'popular-books':
                return $this->popularBooksReport($request);
            case 'course-analysis':
                return $this->courseAnalysisReport($request);
            case 'monthly-summary':
                $data = $this->monthlySummaryReport($request)->getData()['data'];
                $isPrintView = true;
                return response()->view('librarian.reports.monthly-summary-pdf', compact('data', 'isPrintView'));
            default:
                abort(404, 'Report type not found');
        }
    }
    
    /**
     * Export report to CSV/Excel
     */
    public function exportReport(Request $request, $type)
    {
        if ($request->get('format') === 'pdf') {
            return $this->exportReportAsPdf($request, $type);
        }

        switch ($type) {
            case 'borrowing-statistics':
                return $this->exportBorrowingStatistics($request);
            case 'student-activity':
                return $this->exportStudentActivity($request);
            case 'book-usage':
                return $this->exportBookUsage($request);
            case 'popular-books':
                return $this->exportPopularBooks($request);
            case 'course-analysis':
                return $this->exportCourseAnalysis($request);
            case 'monthly-summary':
                return $this->exportMonthlySummary($request);
            default:
                abort(404, 'Export type not found');
        }
    }

    private function exportReportAsPdf(Request $request, string $type)
    {
        $request->merge(['pdf' => true]);

        $reports = [
            'borrowing-statistics' => [
                'data' => $this->borrowingStatisticsReport($request)->getData()['data'],
                'view' => 'librarian.reports.borrowing-statistics-pdf',
                'filename' => 'borrowing-statistics-' . date('Y-m-d') . '.pdf',
                'paper' => ['A4', 'portrait'],
            ],
            'student-activity' => [
                'data' => $this->studentActivityReport($request)->getData()['data'],
                'view' => 'librarian.reports.student-activity-pdf',
                'filename' => 'student-activity-' . date('Y-m-d') . '.pdf',
                'paper' => ['A4', 'landscape'],
            ],
            'book-usage' => [
                'data' => $this->bookUsageReport($request)->getData()['data'],
                'view' => 'librarian.reports.book-usage-pdf',
                'filename' => 'book-usage-' . date('Y-m-d') . '.pdf',
                'paper' => ['A4', 'landscape'],
            ],
            'popular-books' => [
                'data' => $this->popularBooksReport($request)->getData()['data'],
                'view' => 'librarian.reports.popular-books-pdf',
                'filename' => 'popular-books-' . date('Y-m-d') . '.pdf',
                'paper' => ['A4', 'landscape'],
            ],
            'course-analysis' => [
                'data' => $this->courseAnalysisReport($request)->getData()['data'],
                'view' => 'librarian.reports.course-analysis-pdf',
                'filename' => 'course-analysis-' . date('Y-m-d') . '.pdf',
                'paper' => ['A4', 'landscape'],
            ],
            'monthly-summary' => [
                'data' => $this->monthlySummaryReport($request)->getData()['data'],
                'view' => 'librarian.reports.monthly-summary-pdf',
                'filename' => 'monthly-summary-' . date('Y-m-d') . '.pdf',
                'paper' => ['A4', 'portrait'],
            ],
        ];

        if (!isset($reports[$type])) {
            abort(404, 'Export type not found');
        }

        $config = $reports[$type];

        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);

        $isPrintView = false;
        $htmlContent = view($config['view'], [
            'data' => $config['data'],
            'isPrintView' => $isPrintView,
        ])->render();

        $dompdf->loadHtml($htmlContent);
        $dompdf->setPaper($config['paper'][0], $config['paper'][1]);
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $config['filename'] . '"',
        ]);
    }
    
    private function exportBorrowingStatistics(Request $request)
    {
        $data = $this->borrowingStatisticsReport($request)->getData()['data'];
        
        $csvData = [];
        $csvData[] = ['Borrowing Statistics Report'];
        $csvData[] = ['Period', $data['period']['from'] . ' to ' . $data['period']['to']];
        $csvData[] = [];
        $csvData[] = ['Summary'];
        $csvData[] = ['Total Borrows', $data['summary']['total_borrows']];
        $csvData[] = ['Total Returns', $data['summary']['total_returns']];
        $csvData[] = ['Average Per Day', $data['summary']['average_per_day']];
        $csvData[] = [];
        $csvData[] = ['Monthly Breakdown'];
        $csvData[] = ['Period', 'Count'];
        
        foreach ($data['monthly_data'] as $month) {
            $csvData[] = [$month['period'], $month['count']];
        }
        
        return $this->generateCsvResponse($csvData, 'borrowing-statistics-' . date('Y-m-d') . '.csv');
    }
    
    private function exportStudentActivity(Request $request)
    {
        $data = $this->studentActivityReport($request)->getData()['data'];
        
        $csvData = [];
        $csvData[] = ['Student Activity Report'];
        $csvData[] = ['Period', $data['period']['from'] . ' to ' . $data['period']['to']];
        $csvData[] = [];
        $csvData[] = ['Name', 'Library ID', 'Course', 'Year', 'Total Borrowed', 'Total Returned', 'Currently Borrowed', 'Overdue Books', 'Activity Level'];
        
        foreach ($data['students'] as $student) {
            $csvData[] = [
                $student['name'],
                $student['library_id'],
                $student['course'],
                $student['year'],
                $student['total_borrowed'],
                $student['total_returned'],
                $student['currently_borrowed'],
                $student['overdue_books'],
                $student['activity_level']
            ];
        }
        
        return $this->generateCsvResponse($csvData, 'student-activity-' . date('Y-m-d') . '.csv');
    }
    
    private function exportBookUsage(Request $request)
    {
        $data = $this->bookUsageReport($request)->getData()['data'];
        
        $csvData = [];
        $csvData[] = ['Book Usage Report'];
        $csvData[] = ['Period', $data['period']['from'] . ' to ' . $data['period']['to']];
        $csvData[] = [];
        $csvData[] = ['Most Borrowed Books'];
        $csvData[] = ['Title', 'Author', 'Category', 'Course', 'Borrow Count'];
        
        foreach ($data['most_borrowed_books'] as $book) {
            $csvData[] = [$book->title, $book->author, $book->category, $book->course, $book->borrow_count];
        }
        
        return $this->generateCsvResponse($csvData, 'book-usage-' . date('Y-m-d') . '.csv');
    }
    
    
    private function exportPopularBooks(Request $request)
    {
        $data = $this->popularBooksReport($request)->getData()['data'];
        
        $csvData = [];
        $csvData[] = ['Popular Books Report'];
        $csvData[] = ['Period', $data['period']['from'] . ' to ' . $data['period']['to']];
        $csvData[] = [];
        $csvData[] = ['Title', 'Author', 'Category', 'Course', 'Borrow Count', 'Unique Borrowers'];
        
        foreach ($data['popular_books'] as $book) {
            $csvData[] = [$book->title, $book->author, $book->category, $book->course, $book->borrow_count, $book->unique_borrowers];
        }
        
        return $this->generateCsvResponse($csvData, 'popular-books-' . date('Y-m-d') . '.csv');
    }
    
    private function exportCourseAnalysis(Request $request)
    {
        $data = $this->courseAnalysisReport($request)->getData()['data'];
        
        $csvData = [];
        $csvData[] = ['Course Analysis Report'];
        $csvData[] = ['Period', $data['period']['from'] . ' to ' . $data['period']['to']];
        $csvData[] = [];
        $csvData[] = ['Course', 'Total Students', 'Total Borrows', 'Active Borrows', 'Overdue Borrows', 'Borrow Rate Per Student', 'Overdue Rate %'];
        
        foreach ($data['course_statistics'] as $course) {
            $csvData[] = [
                $course['course'],
                $course['total_students'],
                $course['total_borrows'],
                $course['active_borrows'],
                $course['overdue_borrows'],
                $course['borrow_rate_per_student'],
                $course['overdue_rate_percent']
            ];
        }
        
        return $this->generateCsvResponse($csvData, 'course-analysis-' . date('Y-m-d') . '.csv');
    }
    
    private function exportMonthlySummary(Request $request)
    {
        $format = $request->get('format', 'csv');
        $data = $this->monthlySummaryReport($request)->getData()['data'];
        
        if ($format === 'pdf') {
            // Configure DomPDF
            $options = new Options();
            $options->set('defaultFont', 'Arial');
            $options->set('isRemoteEnabled', true);
            $options->set('isHtml5ParserEnabled', true);
            
            // Instantiate DomPDF
            $dompdf = new Dompdf($options);
            
            // Generate HTML content
            $isPrintView = false;
            $htmlContent = view('librarian.reports.monthly-summary-pdf', compact('data', 'isPrintView'))->render();
            
            // Load HTML to DomPDF
            $dompdf->loadHtml($htmlContent);
            
            // Set paper size and orientation
            $dompdf->setPaper('A4', 'portrait');
            
            // Render the HTML as PDF
            $dompdf->render();
            
            // Generate filename
            $filename = 'monthly-summary-' . $data['period']['month_name'] . '.pdf';
            
            // Download the PDF
            return $dompdf->stream($filename);
        }
        
        // Original CSV export
        $csvData = [];
        $csvData[] = ['Monthly Summary Report'];
        $csvData[] = ['Period', $data['period']['month_name']];
        $csvData[] = [];
        $csvData[] = ['Monthly Statistics'];
        $csvData[] = ['Books Borrowed', $data['monthly_stats']['books_borrowed']];
        $csvData[] = ['Books Returned', $data['monthly_stats']['books_returned']];
        $csvData[] = ['New Students', $data['monthly_stats']['new_students']];
        $csvData[] = ['Overdue Books', $data['monthly_stats']['overdue_books']];
        $csvData[] = ['Active Students', $data['monthly_stats']['active_students']];
        $csvData[] = [];
        $csvData[] = ['Top Students'];
        $csvData[] = ['Name', 'Library ID', 'Course', 'Borrow Count'];
        
        foreach ($data['top_students'] as $student) {
            $csvData[] = [$student['name'], $student['library_id'], $student['course'], $student['borrow_count']];
        }
        
        return $this->generateCsvResponse($csvData, 'monthly-summary-' . date('Y-m-d') . '.csv');
    }
}
