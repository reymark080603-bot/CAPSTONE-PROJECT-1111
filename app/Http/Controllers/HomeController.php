<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Book;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use App\Models\BorrowRecord;
use App\Services\LibrarianNotificationService;


class HomeController extends Controller
{
    protected LibrarianNotificationService $librarianNotificationService;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(LibrarianNotificationService $librarianNotificationService)
    {
        $this->librarianNotificationService = $librarianNotificationService;
        $this->middleware('auth:student');
        // Block deactivated student sessions across all dashboard routes
        $this->middleware(function ($request, $next) {
            $user = \Illuminate\Support\Facades\Auth::guard('student')->user();
            if ($user && $user->isStudent() && empty($user->email_verified_at)) {
                \Illuminate\Support\Facades\Auth::guard('student')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect('/login')->withErrors(['account' => 'Your account has been deactivated. Please contact the librarian.']);
            }
            return $next($request);
        });
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $user = Auth::guard('student')->user();
        
        // Redirect based on user roles
        if ($user->role === 'librarian') {
            // Librarians get administrative dashboard
            return redirect()->route('librarian.dashboard');
        }
        
        // Students get student-specific dashboard
        return redirect()->route('student.dashboard');
    }
    
    /**
     * Show the profile page
     */
    public function profile()
    {
        $user = Auth::guard('student')->user();
        return view('dashboard.profile', compact('user'));
    }
    
    /**
     * Update personal information
     */
    public function updatePersonal(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'mi' => 'nullable|string|max:1',
            'email' => 'required|string|email|max:255|unique:users,email,' . Auth::id(),
            'year' => 'required|string',
            'course' => 'required|string',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        try {
            $user = Auth::guard('student')->user();
            $user->update([
                'firstname' => $request->firstname,
                'lastname' => $request->lastname,
                'mi' => $request->mi,
                'email' => $request->email,
                'year' => $request->year,
                'course' => $request->course,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Personal information updated successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update personal information.'
            ], 500);
        }
    }
    
    /**
     * Change password
     */
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'new_password' => 'required|string|min:8|confirmed',
            'new_password_confirmation' => 'required',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        $user = Auth::guard('student')->user();
        
        // Check if current password is correct
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'errors' => ['current_password' => ['The current password is incorrect.']]
            ], 422);
        }
        
        // Check if new password is different from current
        if (Hash::check($request->new_password, $user->password)) {
            return response()->json([
                'success' => false,
                'errors' => ['new_password' => ['New password must be different from current password.']]
            ], 422);
        }
        
        try {
            $user->update([
                'password' => Hash::make($request->new_password)
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Password changed successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to change password.'
            ], 500);
        }
    }
    
    /**
     * Update preferences
     */
    public function updatePreferences(Request $request)
    {
        try {
            $user = Auth::guard('student')->user();

            $preferences = [
                'categories' => $request->input('categories', []),
                'reading_format' => $request->input('reading_format', 'ebook'),
                'notifications' => $request->input('notifications', []),
                'privacy' => $request->input('privacy', []),
            ];

            // Update user preferences in the database
            $user->update(['preferences' => json_encode($preferences)]);

            return response()->json([
                'success' => true,
                'message' => 'Preferences saved successfully!',
                'data' => $preferences
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save preferences.'
            ], 500);
        }
    }
    
    /**
     * Show books page
     */
    public function books()
    {
        $user = Auth::guard('student')->user();
        $search = request('search');
        $resourceType = request('resource_type');
        $scope = request('scope');
        
        if (!$user) {
            return redirect()->route('login')->with('error', 'Please login to access books.');
        }
        
        // Get program-specific resources for the student and arrange them by type
        try {
            $borrowedBooks = BorrowRecord::query()
                ->where('user_id', $user->id)
                ->where('status', 'borrowed')
                ->pluck('book_id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $baseQuery = Book::query()
                ->where('availability_status', 'available')
                ->with(['categories', 'authors', 'publisher']);
            
            // Handle search from student dashboard search bar
            if ($search) {
                $searchTerm = '%' . trim($search) . '%';
                $baseQuery->where(function ($q) use ($searchTerm) {
                    $q->where('title', 'LIKE', $searchTerm)
                      ->orWhere('author', 'LIKE', $searchTerm)
                      ->orWhere('category', 'LIKE', $searchTerm)
                      ->orWhere('course', 'LIKE', $searchTerm)
                      ->orWhere('program', 'LIKE', $searchTerm)
                      ->orWhere('resource_type', 'LIKE', $searchTerm);
                });
            }

            $books = (clone $baseQuery)
                ->where(function ($query) {
                    $query->whereNull('resource_type')
                        ->orWhere('resource_type', '')
                        ->orWhere('resource_type', 'book');
                })
                ->orderBy('title')
                ->get();

            $recommendedBooks = $this->buildCourseResourceQuery($user, true)
                ->with(['categories', 'authors', 'publisher'])
                ->orderByDesc('created_at')
                ->limit(6)
                ->get();

            $selectedResources = null;
            if ($scope === 'recommended') {
                $selectedResources = $this->buildCourseResourceQuery($user, true)
                    ->with(['categories', 'authors', 'publisher'])
                    ->orderBy('title')
                    ->paginate(20)
                    ->withQueryString();
            } elseif ($scope === 'recent') {
                $selectedResources = (clone $baseQuery)
                    ->orderByDesc('created_at')
                    ->paginate(20)
                    ->withQueryString();
            } elseif (in_array($resourceType, ['book', 'e_journal', 'thesis'], true)) {
                $selectedQuery = clone $baseQuery;

                if ($resourceType === 'book') {
                    $selectedQuery->where(function ($query) {
                        $query->whereNull('resource_type')
                            ->orWhere('resource_type', '')
                            ->orWhere('resource_type', 'book');
                    });
                } else {
                    $selectedQuery->where('resource_type', $resourceType);
                }

                $selectedResources = $selectedQuery
                    ->orderBy('title')
                    ->paginate(20)
                    ->withQueryString();
            }

            $eJournalResources = (clone $baseQuery)
                ->where('resource_type', 'e_journal')
                ->orderBy('title')
                ->get();

            $thesisResources = (clone $baseQuery)
                ->where('resource_type', 'thesis')
                ->orderBy('title')
                ->get();
            
            return view('dashboard.books', compact(
                'books',
                'search',
                'user',
                'borrowedBooks',
                'resourceType',
                'scope',
                'selectedResources',
                'recommendedBooks',
                'eJournalResources',
                'thesisResources'
            ));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to load books.');
        }
    }
    
    /**
     * Export history to CSV
     */
    public function exportHistory(Request $request)
    {
        $user = Auth::guard('student')->user();
        
        // Get all records (without pagination for export)
        $request->merge(['page' => 1]);
        $data = $this->historyApi($request);
        $jsonData = json_decode($data->getContent(), true);
        
        // Get all records without pagination
        $borrowRecords = BorrowRecord::with('book')->where('user_id', $user->id)->get();
        
        $allRecords = $borrowRecords->sortByDesc('created_at');
        
        // Create CSV content
        $csvData = [];
        $csvData[] = ['Book Title', 'Author', 'Date', 'Due Date', 'Status', 'Returned Date'];
        
        foreach ($allRecords as $record) {
            $date = $record->borrowed_date;
            $dueDate = $record->due_date ?? 'N/A';
            $status = $this->getRecordStatusForExport($record);
            $returnedDate = $record->returned_date ?? 'N/A';
            
            $csvData[] = [
                $record->book->title ?? 'Unknown',
                $record->book->author ?? 'Unknown',
                $date,
                $dueDate,
                $status,
                $returnedDate
            ];
        }
        
        // Generate CSV file
        $filename = 'borrowing-history-' . date('Y-m-d') . '.csv';
        $handle = fopen('php://temp', 'r+');
        
        foreach ($csvData as $row) {
            fputcsv($handle, $row);
        }
        
        rewind($handle);
        $csvContent = stream_get_contents($handle);
        fclose($handle);
        
        return response($csvContent)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }
    
    public function clearHistory(Request $request)
    {
        $user = Auth::guard('student')->user();
        if (!$user) return response()->json(['success'=>false,'message'=>'Unauthorized'],401);
        // Only clear non-active history to be safe
        $deletedBorrows = BorrowRecord::where('user_id',$user->id)
            ->where('status','returned')
            ->delete();
        $total = $deletedBorrows;
        return response()->json([
            'success'=>true,
            'deleted'=>$total,
            'message'=> $total>0 ? 'Old history cleared successfully.' : 'No old history to clear.'
        ]);
    }
    
    private function getRecordStatusForExport($record)
    {
        // Borrow record
        if ($record->returned_date) {
            return 'Returned';
        }
        if ($record->due_date && $record->due_date < now()) {
            return 'Overdue';
        }
        return 'Borrowed';
    }
    
    /**
     * Renew a borrowed book
     */
    public function renewBook(Request $request, BorrowRecord $borrowRecord)
    {
        $user = Auth::guard('student')->user();
        
        // Check if user owns this borrow record
        if ($borrowRecord->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to this record.'
            ], 403);
        }
        
        // Check if book is currently borrowed
        if ($borrowRecord->status !== 'borrowed') {
            return response()->json([
                'success' => false,
                'message' => 'This book is not currently borrowed.'
            ], 400);
        }
        
        // Check renewal limit (max 2 renewals)
        if (($borrowRecord->renewal_count ?? 0) >= 2) {
            return response()->json([
                'success' => false,
                'message' => 'Maximum renewal limit reached for this book.'
            ], 400);
        }
        
        // Check if book is overdue
        if ($borrowRecord->due_date < now()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot renew overdue books. Please return the book and pay any fines.'
            ], 400);
        }
        
        try {
            // Extend due date by 1 day
            $borrowRecord->update([
                'due_date' => now()->addDays(1),
                'renewal_count' => ($borrowRecord->renewal_count ?? 0) + 1
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Book renewed successfully!',
                'new_due_date' => $borrowRecord->due_date->format('Y-m-d')
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to renew book. Please try again.'
            ], 500);
        }
    }
    
    /**
     * Return a borrowed book
     */
    public function returnBook(Request $request, BorrowRecord $borrowRecord)
    {
        $user = Auth::guard('student')->user();
        
        // Check if user owns this borrow record
        if ($borrowRecord->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to this record.'
            ], 403);
        }
        
        // Check if book is currently borrowed
        if ($borrowRecord->status !== 'borrowed') {
            return response()->json([
                'success' => false,
                'message' => 'This book is not currently borrowed.'
            ], 400);
        }
        
        try {
            // Update borrow record
            $borrowRecord->update([
                'returned_date' => now(),
                'status' => 'returned'
            ]);
            
            // Open access system - books remain available, no reservation logic needed
            $message = 'Book returned successfully!';
            
            return response()->json([
                'success' => true,
                'message' => $message
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to return book. Please try again.'
            ], 500);
        }
    }
    
    /**
     * Show loans page
     */
    public function loans()
    {
        $user = Auth::guard('student')->user();
        return view('dashboard.loans', compact('user'));
    }
    
    /**
     * Get current loans API data
     */
    public function loansApi(Request $request)
    {
        $user = Auth::guard('student')->user();
        
        $loans = BorrowRecord::with(['book'])
            ->where('user_id', $user->id)
            ->where('status', 'borrowed')
            ->orderBy('borrowed_date', 'desc')
            ->get();
            
        // Transform cover URLs for books
        $loans->transform(function ($loan) {
            if ($loan->book) {
                $loan->book->cover_photo = $loan->book->display_cover_url;
            }
            return $loan;
        });
            
        return response()->json([
            'loans' => $loans
        ]);
    }
    
    /**
     * Get loans statistics
     */
    public function loansStatistics(Request $request)
    {
        $user = Auth::guard('student')->user();
        
        $currentLoans = BorrowRecord::where('user_id', $user->id)
            ->where('status', 'borrowed')
            ->count();
            
        $dueSoon = BorrowRecord::where('user_id', $user->id)
            ->where('status', 'borrowed')
            ->where('due_date', '<=', now()->addDays(3))
            ->where('due_date', '>=', now())
            ->count();
            
        $overdue = BorrowRecord::where('user_id', $user->id)
            ->where('status', 'borrowed')
            ->where('due_date', '<', now())
            ->count();
            
        return response()->json([
            'current_loans' => $currentLoans,
            'due_soon' => $dueSoon,
            'overdue_loans' => $overdue
        ]);
    }
    
    /**
     * Renew all eligible loans
     */
    public function renewAllLoans(Request $request)
    {
        $user = Auth::guard('student')->user();
        
        $eligibleLoans = BorrowRecord::where('user_id', $user->id)
            ->where('status', 'borrowed')
            ->where('due_date', '>=', now()) // Not overdue
            ->where(function($query) {
                $query->whereNull('renewal_count')
                      ->orWhere('renewal_count', '<', 2);
            })
            ->get();
            
        $renewedCount = 0;
        
        foreach ($eligibleLoans as $loan) {
            try {
                $loan->update([
                    'due_date' => now()->addDays(1),
                    'renewal_count' => ($loan->renewal_count ?? 0) + 1
                ]);
                $renewedCount++;
            } catch (\Exception $e) {
                // Continue with other loans if one fails
                continue;
            }
        }
        
        if ($renewedCount > 0) {
            return response()->json([
                'success' => true,
                'message' => "Successfully renewed {$renewedCount} book(s).",
                'renewed_count' => $renewedCount
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'No books were eligible for renewal.',
                'renewed_count' => 0
            ], 400);
        }
    }
    
    /**
     * Get recommended books for dashboard
     */
    public function getRecommendedBooks(Request $request)
    {
        $user = Auth::guard('student')->user();
        
        // Get course-specific books first
        $courseSpecificBooks = collect([]);
        $userCourse = $user->course_name ?? $user->course;
        
        if (!empty($userCourse)) {
            $userCourse = trim($userCourse);
            
            // Course mapping for common abbreviations
            $courseMappings = [
                'BSIT' => 'Information Technology',
                'BSN' => 'Nursing',
                'BSNURSING' => 'Nursing',
                'NURSING' => 'Nursing',
                'BSHM' => 'Hospitality Management',
                'HM' => 'Hospitality Management',
                'BSED' => 'Education',
                'BS EDUC' => 'Education',
                'EDUCATION' => 'Education',
                'BSEntrep' => 'Entrepreneurship',
                'BS ENTREP' => 'Entrepreneurship',
                'ENTREP' => 'Entrepreneurship',
                'BS ENTREPRENEURSHIP' => 'Entrepreneurship'
            ];
            
            // Normalize user course
            $normalizedUserCourse = strtoupper($userCourse);
            $mappedCourse = $courseMappings[$normalizedUserCourse] ?? $userCourse;
            
            $courseSpecificBooks = Book::where(function($query) use ($userCourse, $mappedCourse) {
                    // Try exact match with original user course
                    $query->where('course', $userCourse);
                    
                    // Try exact match with mapped course
                    if ($mappedCourse !== $userCourse) {
                        $query->orWhere('course', $mappedCourse);
                    }
                    
                    // Try partial matches with all variations
                    foreach ([$userCourse, $mappedCourse] as $course) {
                        $query->orWhere('course', 'LIKE', '%' . $course . '%');
                        $query->orWhereRaw('LOWER(course) LIKE ?', ['%' . strtolower($course) . '%']);
                    }
                })
                ->where('availability_status', 'available')
                ->with(['categories', 'authors', 'publisher'])
                ->orderBy('created_at', 'desc')
                ->limit(6) // Limit course-specific books
                ->get();
        }
        
        // Get general available books to fill remaining slots
        $generalBooks = Book::where('availability_status', 'available')
            ->with(['categories', 'authors', 'publisher'])
            ->orderBy('created_at', 'desc')
            ->limit(12)
            ->get();
        
        // For BSN students, only show Nursing books (no general books)
        if (strtoupper($userCourse) === 'BSN') {
            // BSN students get ONLY Nursing books
            $recommendedBooks = $courseSpecificBooks;
        } else {
            // Other students get course-specific + general books
            $recommendedBooks = $courseSpecificBooks;
            $generalBooks = $generalBooks->reject(function($generalBook) use ($courseSpecificBooks) {
                return $courseSpecificBooks->contains('id', $generalBook->id);
            });
            
            // Fill remaining slots with general books
            $remainingSlots = 12 - $courseSpecificBooks->count();
            if ($remainingSlots > 0) {
                $recommendedBooks = $recommendedBooks->merge($generalBooks->take($remainingSlots));
            }
        }
            
        $recommendedBooks->transform(function ($book) {
            $book->cover_photo = $book->display_cover_url;
            return $book;
        });

        return response()->json([
            'user_course' => $userCourse,
            'recommended' => $recommendedBooks
        ]);
    }
    
    /**
     * Get recent books
     */
    public function getRecentBooks(Request $request)
    {
        $user = Auth::guard('student')->user();

        // Get recently added books (most recent 12 books, ordered by creation date)
        // Changed from 30-day filter to get the most recent books regardless of age
        $recentBooks = Book::orderBy('created_at', 'desc')
            ->with(['categories', 'authors', 'publisher'])
            ->limit(12) // Get more books for carousel
            ->get();

        $recentBooks->transform(function ($book) {
            $book->cover_photo = $book->display_cover_url;
            return $book;
        });

        return response()->json([
            'recent' => $recentBooks
        ]);
    }
    
    /**
     * Get dashboard statistics
     */
    public function getDashboardStats(Request $request)
    {
        $user = Auth::guard('student')->user();
        
        // Calculate statistics
        $totalBooks = Book::count();
        
        $booksRead = BorrowRecord::where('user_id', $user->id)
            ->where('status', 'returned')
            ->count();
            
        $currentLoans = BorrowRecord::where('user_id', $user->id)
            ->where('status', 'borrowed')
            ->count();
        
        return response()->json([
            'books_read' => $booksRead,
            'total_books' => $totalBooks,
            'current_loans' => $currentLoans
        ]);
    }
    
    /**
     * Show book details page
     */
    public function showBookDetailsPage($id)
    {
        try {
            $user = Auth::guard('student')->user();
            if (!$user) {
                // Try default auth guard as fallback
                $user = Auth::user();
                if (!$user) {
                    return redirect()->route('login')->with('error', 'Please login to view book details.');
                }
            }
            
            // Remove relationships to avoid 500 error
            $book = Book::findOrFail($id);
            
            // Check if user has already borrowed this book
            $borrowRecord = BorrowRecord::where('user_id', $user->id)
                ->where('book_id', $book->id)
                ->where('status', 'borrowed')
                ->first();
            
            // Add properties to book object for view
            $book->user_has_borrowed = !is_null($borrowRecord);
            $book->borrow_record = $borrowRecord;

            $currentUrl = route('student.books.show', $book->id);
            $previousUrl = url()->previous();
            $fallbackUrl = route('student.books');

            $backUrl = $fallbackUrl;
            if ($previousUrl
                && $previousUrl !== $currentUrl
                && !str_contains($previousUrl, '/student/books/' . $book->id . '/read')
                && !str_contains($previousUrl, '/student/borrow/' . $book->id)) {
                $backUrl = $previousUrl;
            }
                
            return view('dashboard.book-details', compact('book', 'borrowRecord', 'user', 'backUrl'));
            
        } catch (\Exception $e) {
            Log::error('Show Book Details Page Error', [

                'book_id' => $id,
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()->with('error', 'Failed to load book details. Please try again.');
        }
    }
    
    /**
     * Get book details API
     */
    public function bookDetails($id)
    {
        try {
            Log::info('Book Details Request', [
                'book_id' => $id,
                'user_authenticated' => Auth::check(),
                'user_id' => Auth::check() ? Auth::guard('student')->user()->id : null
            ]);
            
            // Check if book exists first
            $bookExists = Book::where('id', $id)->exists();
            Log::info('Book Existence Check', [
                'book_id' => $id,
                'book_exists' => $bookExists
            ]);
            
            if (!$bookExists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Book not found with ID: ' . $id
                ], 404);
            }
            
            // Try without relationships first to isolate the issue
            $book = Book::findOrFail($id);
            
            Log::info('Book Found', [
                'book_id' => $book->id,
                'book_title' => $book->title,
                'book_author' => $book->author,
                'book_course' => $book->course,
                'book_exists' => true
            ]);
            
            return response()->json([
                'book' => $book
            ]);
            
        } catch (\Exception $e) {
            Log::error('Book Details Error', [
                'book_id' => $id,
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load book details: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Borrow a book
     */
    public function borrowBook(Request $request, $id)
    {
        if (!Auth::guard('student')->check()) {
            return response()->json(['success' => false, 'message' => 'Please login to borrow books.'], 401);
        }
        
        $user = Auth::guard('student')->user();
        if (!$user || !$user->id) {
            return response()->json(['success' => false, 'message' => 'User authentication failed. Please login again.'], 401);
        }
        
        Log::info('Borrow Book Attempt', [
            'user_authenticated' => Auth::guard('student')->check(),
            'user_id' => $user->id,
            'user_email' => $user->email ?? 'no email',
            'book_id' => $id
        ]);
        
        $book = Book::findOrFail($id);
        
        // Check if book is available
        if ($book->availability_status !== 'available') {
            return response()->json(['success' => false, 'message' => 'Book is not available for borrowing.'], 400);
        }
        
        // Check if user has already borrowed this book
        $existingBorrow = BorrowRecord::where('user_id', $user->id)
            ->where('book_id', $book->id)
            ->where('status', 'borrowed')
            ->first();
            
        if ($existingBorrow) {
            return response()->json(['success' => false, 'message' => 'You have already borrowed this book.'], 400);
        }
        
        try {
            // Create borrow record
            $borrowRecord = BorrowRecord::create([
                'user_id' => $user->id,
                'book_id' => $book->id,
                'borrowed_date' => now(),
                'due_date' => now()->addDays(1), // 1 day borrowing period
                'status' => 'borrowed'
            ]);

            $this->librarianNotificationService->notifyBookBorrowed($user, $book, $borrowRecord);
            
            return response()->json([
                'success' => true,
                'message' => 'Book borrowed successfully!',
                'borrow_record' => $borrowRecord
            ]);
            
        } catch (\Exception $e) {
            // Log detailed error for debugging
            Log::error('Borrow Book Error', [
                'user_id' => $user->id,
                'book_id' => $book->id,
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString(),
                'book_availability' => $book->availability_status
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to borrow book: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * View/read a book
     */
    public function viewBook($id)
    {
        $user = Auth::guard('student')->user();
        if (!$user) {
            return redirect()->route('login')->with('error', 'Please login to read books.');
        }
        
        $book = Book::findOrFail($id);
        
        // Check if book has any readable digital content
        if (!$book->hasReadableContent()) {
            return redirect()->back()->with('error', 'This book does not have a digital version available.');
        }
        
        // Check if user has borrowed this book or it's available for reading
        $borrowRecord = BorrowRecord::where('user_id', $user->id)
            ->where('book_id', $book->id)
            ->where('status', 'borrowed')
            ->first();
            
        if (!$borrowRecord && $book->availability_status !== 'available') {
            return redirect()->back()->with('error', 'You need to borrow this book first to read it.');
        }
        
        return view('dashboard.read-book', compact('book', 'borrowRecord'));
    }
    
    /**
     * Get books API data
     */
    public function booksApi(Request $request)
    {
        try {
            $query = Book::with(['categories', 'authors', 'publisher']);
            
            // Handle search
            if ($search = $request->get('search')) {
                $searchTerm = '%' . trim($search) . '%';
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('title', 'LIKE', $searchTerm)
                      ->orWhere('author', 'LIKE', $searchTerm)
                      ->orWhere('category', 'LIKE', $searchTerm);
                });
            }
            
            $books = $query->orderBy('title')->paginate(12);
            
            return response()->json([
                'books' => $books->items(),
                'pagination' => [
                    'current_page' => $books->currentPage(),
                    'last_page' => $books->lastPage(),
                    'per_page' => $books->perPage(),
                    'total' => $books->total()
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load books.'
            ], 500);
        }
    }

    /**
     * Quick search API for dashboard header search (books/e-resources).
     */
    public function quickSearch(Request $request)
    {
        $term = trim((string) $request->get('q', ''));

        if ($term === '') {
            return response()->json([
                'query' => '',
                'results' => []
            ]);
        }

        $searchTerm = '%' . $term . '%';
        $searchableColumns = collect([
            'title',
            'author',
            'category',
            'course',
            'resource_type',
            'file_type',
        ])->filter(fn ($column) => Schema::hasColumn('books', $column))->values();

        if ($searchableColumns->isEmpty()) {
            return response()->json([
                'query' => $term,
                'results' => []
            ]);
        }

        $books = Book::where('availability_status', 'available')
            ->where(function ($q) use ($searchTerm, $searchableColumns) {
                foreach ($searchableColumns as $index => $column) {
                    if ($index === 0) {
                        $q->where($column, 'LIKE', $searchTerm);
                    } else {
                        $q->orWhere($column, 'LIKE', $searchTerm);
                    }
                }
            })
            ->orderBy('title')
            ->limit(8)
            ->get();

        $results = $books->map(function (Book $book) {
            $type = $book->file_type ?: ($book->resource_type ?: 'book');
            $readable = $book->hasReadableContent();

            return [
                'id' => $book->id,
                'title' => $book->title,
                'author' => $book->author,
                'type' => strtoupper((string) $type),
                'cover_url' => $book->display_cover_url,
                'view_url' => route('student.books.show', $book->id),
                'read_url' => $readable ? route('student.books.read', $book->id) : null,
                'is_readable' => $readable,
            ];
        })->values();

        return response()->json([
            'query' => $term,
            'results' => $results
        ]);
    }
    
    /**
     * Show history page
     */
    public function history()
    {
        $user = Auth::guard('student')->user();
        return view('dashboard.history', compact('user'));
    }
    
    /**
     * Get history API data
     */
    public function historyApi(Request $request)
    {
        $user = Auth::guard('student')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 10);
        $search = trim((string) $request->get('search', ''));
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        $status = $request->get('status');
        $resourceType = $request->get('resource_type');

        $borrowRecords = BorrowRecord::with('book')
            ->where('user_id', $user->id)
            ->when($search !== '', function ($query) use ($search) {
                $searchTerm = '%' . $search . '%';
                $query->whereHas('book', function ($bookQuery) use ($searchTerm) {
                    $bookQuery->where('title', 'LIKE', $searchTerm)
                        ->orWhere('author', 'LIKE', $searchTerm);
                });
            })
            ->when($dateFrom, function ($query) use ($dateFrom) {
                $query->whereDate('borrowed_date', '>=', $dateFrom);
            })
            ->when($dateTo, function ($query) use ($dateTo) {
                $query->whereDate('borrowed_date', '<=', $dateTo);
            })
            ->when(in_array($status, ['borrowed', 'returned'], true), function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when(in_array($resourceType, ['book', 'e_journal', 'thesis'], true), function ($query) use ($resourceType) {
                $query->whereHas('book', function ($bookQuery) use ($resourceType) {
                    if ($resourceType === 'book') {
                        $bookQuery->where(function ($typeQuery) {
                            $typeQuery->whereNull('resource_type')
                                ->orWhere('resource_type', '')
                                ->orWhere('resource_type', 'book');
                        });
                    } else {
                        $bookQuery->where('resource_type', $resourceType);
                    }
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate($limit, ['*'], 'page', $page);
            
        $borrowRecords->getCollection()->transform(function ($record) {
            if ($record->book) {
                $record->book->cover_url = $record->book->display_cover_url;
            }
            return $record;
        });
            
        return response()->json([
            'records' => [
                'data' => $borrowRecords->items(),
                'current_page' => $borrowRecords->currentPage(),
                'last_page' => $borrowRecords->lastPage(),
                'per_page' => $borrowRecords->perPage(),
                'total' => $borrowRecords->total()
            ]
        ]);
    }
    
    /**
     * Get history statistics
     */
    public function historyStatistics(Request $request)
    {
        $user = Auth::guard('student')->user();
        
        $totalBorrowed = BorrowRecord::where('user_id', $user->id)->count();
        $returnedBooks = BorrowRecord::where('user_id', $user->id)
            ->where('status', 'returned')
            ->count();
        $currentBorrows = BorrowRecord::where('user_id', $user->id)
            ->where('status', 'borrowed')
            ->count();
        $overdueBooks = BorrowRecord::where('user_id', $user->id)
            ->where('status', 'borrowed')
            ->where('due_date', '<', now())
            ->count();
            
        return response()->json([
            'total_borrowed' => $totalBorrowed,
            'returned_books' => $returnedBooks,
            'current_borrows' => $currentBorrows,
            'overdue_books' => $overdueBooks
        ]);
    }
    
    /**
     * Show borrow page
     */
    public function borrowPage($id = null)
    {
        $user = Auth::guard('student')->user();
        if (!$user) {
            return redirect()->route('login')->with('error', 'Please login to borrow books.');
        }
        
        if ($id) {
            $book = Book::findOrFail($id);
            return view('dashboard.borrow-book', compact('book', 'user'));
        }
        
        return view('dashboard.borrow-book', compact('user'));
    }

    private function buildCourseResourceQuery(User $user, bool $excludeBorrowedByUser = false)
    {
        $query = Book::query()->where('availability_status', 'available');

        if ($excludeBorrowedByUser) {
            $query->whereDoesntHave('borrowRecords', function ($borrowQuery) use ($user) {
                $borrowQuery->where('user_id', $user->id)
                    ->where('status', 'borrowed');
            });
        }

        $courseVariants = $this->getUserCourseVariants($user);
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

    private function getUserCourseVariants(User $user): array
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
}
