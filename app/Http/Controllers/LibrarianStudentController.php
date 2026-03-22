<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\BorrowRecord;
use App\Models\Fine;
use Carbon\Carbon;

class LibrarianStudentController extends Controller
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
     * Display students management page
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Get filter parameters
        $status = $request->get('status', 'all');
        $course = $request->get('course');
        $year = $request->get('year');
        $search = $request->get('search');
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        return view('librarian.students.index', compact(
            'user', 'status', 'course', 'year', 'search', 'sortBy', 'sortOrder'
        ));
    }

    /**
     * Get students data via API
     */
    public function api(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $status = $request->get('status', 'all');
        $course = $request->get('course');
        $year = $request->get('year');
        $search = $request->get('search');
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        // Build query
        $query = User::whereHas('role', function($q) {
            $q->where('name', 'student');
        });

        // Apply status filter
        if ($status === 'active') {
            $query->whereNotNull('email_verified_at');
        } elseif ($status === 'inactive') {
            $query->whereNull('email_verified_at');
        }

        // Apply course filter
        if ($course) {
            $query->where('course', $course);
        }

        // Apply year filter
        if ($year) {
            $query->where('year', $year);
        }

        // Apply search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('firstname', 'LIKE', "%{$search}%")
                  ->orWhere('lastname', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('student_id', 'LIKE', "%{$search}%");
            });
        }

        // Apply sorting
        $query->orderBy($sortBy, $sortOrder);

        // Paginate results
        $students = $query->paginate($perPage);

        // Add computed fields
        $students->getCollection()->transform(function ($student) {
            $student->full_name = $student->firstname . ' ' . $student->lastname;
            $student->is_active = !is_null($student->email_verified_at);
            $student->current_borrows = BorrowRecord::where('user_id', $student->id)
                ->where('status', 'borrowed')
                ->count();
            $student->total_borrows = BorrowRecord::where('user_id', $student->id)->count();
            $student->overdue_books = BorrowRecord::where('user_id', $student->id)
                ->where('status', 'borrowed')
                ->where('due_date', '<', now())
                ->count();
            $student->pending_fines = Fine::where('user_id', $student->id)
                ->where('status', 'pending')
                ->sum('amount');
            return $student;
        });

        return response()->json([
            'students' => $students
        ]);
    }

    /**
     * Show student details
     */
    public function show(User $student)
    {
        // Ensure it's a student
        if (!$student->isStudent()) {
            abort(404);
        }

        $user = Auth::user();

        // Load additional data
        $student->load(['borrowRecords' => function($query) {
            $query->with('book')->orderBy('borrowed_date', 'desc')->limit(10);
        }, 'fines' => function($query) {
            $query->orderBy('issued_date', 'desc');
        }]);

        // Calculate statistics
        $student->current_borrows = BorrowRecord::where('user_id', $student->id)
            ->where('status', 'borrowed')
            ->count();
        $student->total_borrows = BorrowRecord::where('user_id', $student->id)->count();
        $student->returned_books = BorrowRecord::where('user_id', $student->id)
            ->where('status', 'returned')
            ->count();
        $student->overdue_books = BorrowRecord::where('user_id', $student->id)
            ->where('status', 'borrowed')
            ->where('due_date', '<', now())
            ->count();
        $student->pending_fines = Fine::where('user_id', $student->id)
            ->where('status', 'pending')
            ->sum('amount');
        $student->paid_fines = Fine::where('user_id', $student->id)
            ->where('status', 'paid')
            ->sum('amount');

        return view('librarian.students.show', compact('user', 'student'));
    }

    /**
     * Update student status
     */
    public function updateStatus(Request $request, User $student)
    {
        // Ensure it's a student
        if (!$student->isStudent()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid user type.'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:active,inactive'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            if ($request->status === 'active') {
                $student->update([
                    'email_verified_at' => now()
                ]);
                $message = 'Student account activated successfully!';
            } else {
                $student->update([
                    'email_verified_at' => null
                ]);
                $message = 'Student account deactivated successfully!';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'student' => $student
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update student status.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Deactivate student account
     */
    public function deactivate(User $student)
    {
        // Ensure it's a student
        if (!$student->isStudent()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid user type.'
            ], 400);
        }

        try {
            $student->update([
                'email_verified_at' => null
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Student account deactivated successfully!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to deactivate student account.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Activate student account
     */
    public function activate(User $student)
    {
        // Ensure it's a student
        if (!$student->isStudent()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid user type.'
            ], 400);
        }

        try {
            $student->update([
                'email_verified_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Student account activated successfully!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to activate student account.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Reset student password
     */
    public function resetPassword(Request $request, User $student)
    {
        // Ensure it's a student
        if (!$student->isStudent()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid user type.'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'new_password' => 'required|string|min:8|confirmed'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $student->update([
                'password' => Hash::make($request->new_password)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Student password reset successfully!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reset student password.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Export students data
     */
    public function export(Request $request)
    {
        $format = $request->get('format', 'csv');

        // Get filtered students
        $query = User::whereHas('role', function($q) {
            $q->where('name', 'student');
        });

        // Apply same filters as index
        if ($request->status === 'active') {
            $query->whereNotNull('email_verified_at');
        } elseif ($request->status === 'inactive') {
            $query->whereNull('email_verified_at');
        }

        if ($request->course) {
            $query->where('course', $request->course);
        }

        if ($request->year) {
            $query->where('year', $request->year);
        }

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('firstname', 'LIKE', "%{$request->search}%")
                  ->orWhere('lastname', 'LIKE', "%{$request->search}%")
                  ->orWhere('email', 'LIKE', "%{$request->search}%");
            });
        }

        $students = $query->get();

        if ($format === 'csv') {
            return $this->exportStudentsCsv($students);
        }

        return response()->json(['error' => 'Unsupported export format'], 400);
    }

    /**
     * Get student borrow history
     */
    public function borrowHistory(User $student)
    {
        // Ensure it's a student
        if (!$student->isStudent()) {
            abort(404);
        }

        $user = Auth::user();

        // Get borrow history with pagination
        $borrowHistory = BorrowRecord::with(['book'])
            ->where('user_id', $student->id)
            ->orderBy('borrowed_date', 'desc')
            ->paginate(20);

        return view('librarian.students.borrow-history', compact('user', 'student', 'borrowHistory'));
    }

    /**
     * Export students to CSV
     */
    private function exportStudentsCsv($students)
    {
        $csvData = [];
        $csvData[] = [
            'Student ID', 'First Name', 'Last Name', 'Email', 'Course',
            'Year', 'Status', 'Total Borrows', 'Current Borrows', 'Overdue Books',
            'Pending Fines', 'Registration Date'
        ];

        foreach ($students as $student) {
            $currentBorrows = BorrowRecord::where('user_id', $student->id)
                ->where('status', 'borrowed')
                ->count();
            $totalBorrows = BorrowRecord::where('user_id', $student->id)->count();
            $overdueBooks = BorrowRecord::where('user_id', $student->id)
                ->where('status', 'borrowed')
                ->where('due_date', '<', now())
                ->count();
            $pendingFines = Fine::where('user_id', $student->id)
                ->where('status', 'pending')
                ->sum('amount');

            $csvData[] = [
                $student->student_id,
                $student->firstname,
                $student->lastname,
                $student->email,
                $student->course,
                $student->year,
                $student->email_verified_at ? 'Active' : 'Inactive',
                $totalBorrows,
                $currentBorrows,
                $overdueBooks,
                $pendingFines,
                $student->created_at->format('Y-m-d H:i:s')
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
            ->header('Content-Disposition', 'attachment; filename="students-' . date('Y-m-d') . '.csv"');
    }
}
