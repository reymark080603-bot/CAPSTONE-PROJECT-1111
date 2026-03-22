<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\BorrowRecord;
use Carbon\Carbon;

class StudentService
{
    /**
     * Get students with filters and pagination
     */
    public function getStudents($filters = [], $perPage = 15)
    {
        $query = User::whereHas('role', function($q) {
            $q->where('name', 'student');
        });

        // Apply status filter
        if (isset($filters['status']) && $filters['status'] !== 'all') {
            if ($filters['status'] === 'active') {
                $query->whereNotNull('email_verified_at');
            } elseif ($filters['status'] === 'inactive') {
                $query->whereNull('email_verified_at');
            }
        }

        // Apply course filter
        if (isset($filters['course']) && $filters['course']) {
            $query->where('course', $filters['course']);
        }

        // Apply year filter
        if (isset($filters['year']) && $filters['year']) {
            $query->where('year', $filters['year']);
        }

        // Apply search filter
        if (isset($filters['search']) && $filters['search']) {
            $query->where(function ($q) use ($filters) {
                $q->where('firstname', 'LIKE', "%{$filters['search']}%")
                  ->orWhere('lastname', 'LIKE', "%{$filters['search']}%")
                  ->orWhere('email', 'LIKE', "%{$filters['search']}%")
                  ->orWhere('student_id', 'LIKE', "%{$filters['search']}%");
            });
        }

        // Apply sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
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
            return $student;
        });

        return $students;
    }

    /**
     * Get student details with related data
     */
    public function getStudentDetails(User $student)
    {
        // Load additional data
        $student->load(['borrowRecords' => function($query) {
            $query->with('book')->orderBy('borrowed_date', 'desc')->limit(10);
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

        return $student;
    }

    /**
     * Update student status
     */
    public function updateStudentStatus(User $student, $status)
    {
        if (!$student->isStudent()) {
            throw new \Exception('Invalid user type.');
        }

        if ($status === 'active') {
            $student->update([
                'email_verified_at' => now()
            ]);
        } else {
            $student->update([
                'email_verified_at' => null
            ]);
        }

        return $student;
    }

    /**
     * Reset student password
     */
    public function resetStudentPassword(User $student, $newPassword)
    {
        if (!$student->isStudent()) {
            throw new \Exception('Invalid user type.');
        }

        $student->update([
            'password' => Hash::make($newPassword)
        ]);

        return $student;
    }

    /**
     * Update student profile
     */
    public function updateStudentProfile(User $student, $data, $files = [])
    {
        // Handle profile photo upload
        if (isset($files['profile_photo'])) {
            // Delete old profile photo
            if ($student->profile_photo && Storage::disk('public')->exists($student->profile_photo)) {
                Storage::disk('public')->delete($student->profile_photo);
            }
            $profilePhotoPath = $files['profile_photo']->store('profiles', 'public');
            $student->profile_photo = $profilePhotoPath;
        }

        // Update user data
        $student->update([
            'firstname' => $data['firstname'],
            'lastname' => $data['lastname'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'course' => $data['course'] ?? null,
            'year' => $data['year'] ?? null,
        ]);

        return $student;
    }

    /**
     * Change student password
     */
    public function changeStudentPassword(User $student, $currentPassword, $newPassword)
    {
        // Check current password
        if (!Hash::check($currentPassword, $student->password)) {
            throw new \Exception('Current password is incorrect.');
        }

        $student->update([
            'password' => Hash::make($newPassword)
        ]);

        return $student;
    }

    /**
     * Get student's borrowing history
     */
    public function getStudentBorrowingHistory($studentId, $filters = [], $perPage = 15)
    {
        $query = BorrowRecord::with(['book'])
            ->where('user_id', $studentId);

        // Apply status filter
        if (isset($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        // Apply date filters
        if (isset($filters['date_from']) && $filters['date_from']) {
            $query->where('borrowed_date', '>=', Carbon::parse($filters['date_from']));
        }
        if (isset($filters['date_to']) && $filters['date_to']) {
            $query->where('borrowed_date', '<=', Carbon::parse($filters['date_to']));
        }

        // Apply search filter
        if (isset($filters['search']) && $filters['search']) {
            $query->whereHas('book', function ($bookQuery) use ($filters) {
                $bookQuery->where('title', 'LIKE', "%{$filters['search']}%")
                         ->orWhere('author', 'LIKE', "%{$filters['search']}%")
                         ->orWhere('isbn', 'LIKE', "%{$filters['search']}%");
            });
        }

        // Apply sorting
        $sortBy = $filters['sort_by'] ?? 'borrowed_date';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        $history = $query->paginate($perPage);

        // Add computed fields
        $history->getCollection()->transform(function ($record) {
            $record->is_overdue = $record->status === 'borrowed' && $record->due_date < now();
            $record->days_overdue = $record->is_overdue ? now()->diffInDays($record->due_date) : 0;
            return $record;
        });

        return $history;
    }

    /**
     * Get student's reading statistics
     */
    public function getStudentReadingStats($studentId, $period = 'month')
    {
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
        $booksBorrowed = BorrowRecord::where('user_id', $studentId)
            ->where('borrowed_date', '>=', $dateFrom)
            ->count();

        // Books returned in period
        $booksReturned = BorrowRecord::where('user_id', $studentId)
            ->where('returned_date', '>=', $dateFrom)
            ->whereNotNull('returned_date')
            ->count();

        // Current active borrows
        $activeBorrows = BorrowRecord::where('user_id', $studentId)
            ->where('status', 'borrowed')
            ->count();

        // Overdue books
        $overdueBooks = BorrowRecord::where('user_id', $studentId)
            ->where('status', 'borrowed')
            ->where('due_date', '<', now())
            ->count();

        // Favorite genres (based on borrowed books)
        $favoriteGenres = BorrowRecord::with('book.categories')
            ->where('user_id', $studentId)
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
            $count = BorrowRecord::where('user_id', $studentId)
                ->whereYear('borrowed_date', $month->year)
                ->whereMonth('borrowed_date', $month->month)
                ->count();
            $monthlyTrend[] = [
                'month' => $month->format('M Y'),
                'count' => $count
            ];
        }

        return [
            'stats' => [
                'books_borrowed' => $booksBorrowed,
                'books_returned' => $booksReturned,
                'active_borrows' => $activeBorrows,
                'overdue_books' => $overdueBooks,
                'favorite_genres' => $favoriteGenres,
                'monthly_trend' => $monthlyTrend
            ],
            'period' => $period
        ];
    }

    /**
     * Get student's dashboard statistics
     */
    public function getStudentDashboardStats($studentId)
    {
        $stats = [
            'current_borrows' => BorrowRecord::where('user_id', $studentId)
                ->where('status', 'borrowed')
                ->count(),
            'total_borrows' => BorrowRecord::where('user_id', $studentId)->count(),
            'overdue_books' => BorrowRecord::where('user_id', $studentId)
                ->where('status', 'borrowed')
                ->where('due_date', '<', now())
                ->count(),
            'recent_borrows' => BorrowRecord::where('user_id', $studentId)
                ->where('borrowed_date', '>=', now()->subDays(30))
                ->count(),
            'books_due_soon' => BorrowRecord::where('user_id', $studentId)
                ->where('status', 'borrowed')
                ->where('due_date', '>=', now())
                ->where('due_date', '<=', now()->addDays(3))
                ->count()
        ];

        return $stats;
    }

    /**
     * Export students to CSV
     */
    public function exportStudentsToCsv($students)
    {
        $csvData = [];
        $csvData[] = [
            'Student ID', 'First Name', 'Last Name', 'Email', 'Course',
            'Year', 'Status', 'Total Borrows', 'Current Borrows', 'Overdue Books',
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
                $student->created_at->format('Y-m-d H:i:s')
            ];
        }

        return $this->generateCsvResponse($csvData, 'students-' . date('Y-m-d') . '.csv');
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
