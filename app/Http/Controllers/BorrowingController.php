<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\Book;
use App\Models\BorrowRecord;
use Carbon\Carbon;

class BorrowingController extends Controller
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
     * Show borrowing page
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        return view('dashboard.loans', compact('user'));
    }

    /**
     * Show borrow book form
     */
    public function create(Book $book)
    {
        $user = Auth::user();

        // Check if book is available
        if ($book->availability_status !== 'available') {
            return redirect()->back()->with('error', 'This book is not available for borrowing.');
        }

        // Check if user already has this book borrowed
        $existingBorrow = BorrowRecord::where('user_id', $user->id)
            ->where('book_id', $book->id)
            ->where('status', 'borrowed')
            ->first();

        if ($existingBorrow) {
            return redirect()->back()->with('error', 'You already have this book borrowed.');
        }

        // Check user's current borrow limit (max 3 books)
        $currentBorrows = BorrowRecord::where('user_id', $user->id)
            ->where('status', 'borrowed')
            ->count();

        if ($currentBorrows >= 3) {
            return redirect()->back()->with('error', 'You have reached the maximum borrow limit (3 books).');
        }


    /**
     * Borrow a book
     */
    }
    /**
     * Borrow a book
     */
    public function store(Request $request, Book $book)
    {
        $user = Auth::user();

        // Validate the request
        $validator = Validator::make($request->all(), [
            'borrow_days' => 'required|integer|min:1|max:14'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if book is available
        if ($book->availability_status !== 'available') {
            return response()->json([
                'success' => false,
                'message' => 'This book is not available for borrowing.'
            ], 400);
        }

        // Check if user already has this book borrowed
        $existingBorrow = BorrowRecord::where('user_id', $user->id)
            ->where('book_id', $book->id)
            ->where('status', 'borrowed')
            ->first();

        if ($existingBorrow) {
            return response()->json([
                'success' => false,
                'message' => 'You already have this book borrowed.'
            ], 400);
        }

        // Auto-return any overdue books for this user to free up borrow limit
        $overdueRecords = BorrowRecord::where('user_id', $user->id)
            ->where('status', 'borrowed')
            ->where('due_date', '<', now())
            ->get();

        foreach ($overdueRecords as $overdueRecord) {
            $overdueRecord->update([
                'returned_date' => now(),
                'status' => 'returned',
                'notes' => ($overdueRecord->notes ? $overdueRecord->notes . ' | ' : '') . 'Auto-returned due to new borrow attempt'
            ]);
        }

        // Check user's current borrow limit (max 3 books) after auto-returning overdue books
        $currentBorrows = BorrowRecord::where('user_id', $user->id)
            ->where('status', 'borrowed')
            ->count();

        if ($currentBorrows >= 3) {
            return response()->json([
                'success' => false,
                'message' => 'You have reached the maximum borrow limit (3 books).'
            ], 400);
        }


        try {
            DB::beginTransaction();

            // Create borrow record
            $borrowRecord = BorrowRecord::create([
                'user_id' => $user->id,
                'book_id' => $book->id,
                'borrowed_date' => now(),
                'due_date' => now()->addDays($request->borrow_days),
                'status' => 'borrowed'
            ]);

            // Open access system - book remains available for others

            DB::commit();

            $message = 'Book borrowed successfully!';
            if ($overdueRecords->count() > 0) {
                $message .= ' ' . $overdueRecords->count() . ' overdue book(s) were automatically returned.';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'borrow_record' => $borrowRecord->load(['book'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to borrow book. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Return a book
     */
    public function returnBook(Request $request, BorrowRecord $borrowRecord)
    {
        $user = Auth::user();

        // Check if the borrow record belongs to the user
        if ($borrowRecord->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You can only return books you have borrowed.'
            ], 403);
        }

        // Check if book is already returned
        if ($borrowRecord->status !== 'borrowed') {
            return response()->json([
                'success' => false,
                'message' => 'This book has already been returned.'
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Update borrow record
            $borrowRecord->update([
                'returned_date' => now(),
                'status' => 'returned'
            ]);

            // Skip overdue check since system uses auto-return
            // Books are automatically returned after due date

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Book returned successfully!',
                'borrow_record' => $borrowRecord->load(['book'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to return book. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Renew a book loan
     */
    public function renewBook(Request $request, BorrowRecord $borrowRecord)
    {
        $user = Auth::user();

        // Check if the borrow record belongs to the user
        if ($borrowRecord->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You can only renew books you have borrowed.'
            ], 403);
        }

        // Check if book is currently borrowed
        if ($borrowRecord->status !== 'borrowed') {
            return response()->json([
                'success' => false,
                'message' => 'This book is not currently borrowed.'
            ], 400);
        }

        // Check if book is overdue
        if ($borrowRecord->due_date < now()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot renew overdue books. Please return the book first.'
            ], 400);
        }

        // Check renewal limit (max 2 renewals)
        $currentRenewals = $borrowRecord->renewal_count ?? 0;
        if ($currentRenewals >= 2) {
            return response()->json([
                'success' => false,
                'message' => 'Maximum renewal limit reached for this loan.'
            ], 400);
        }

        try {
            // Extend due date by 14 days
            $borrowRecord->update([
                'due_date' => now()->addDays(14),
                'renewal_count' => $currentRenewals + 1
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Book loan renewed successfully!',
                'new_due_date' => $borrowRecord->due_date->format('Y-m-d'),
                'borrow_record' => $borrowRecord->load(['book'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to renew book loan. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get user's current loans
     */
    public function getCurrentLoans(Request $request)
    {
        $user = Auth::user();

        $loans = BorrowRecord::with('book')
            ->where('user_id', $user->id)
            ->where('status', 'borrowed')
            ->orderBy('due_date', 'asc')
            ->get()
            ->map(function ($loan) {
                return [
                    'id' => $loan->id,
                    'book' => $loan->book,
                    'borrowed_date' => $loan->borrowed_date,
                    'due_date' => $loan->due_date,
                    'is_overdue' => $loan->due_date < now(),
                    'days_remaining' => $loan->due_date >= now() ? now()->diffInDays($loan->due_date) : 0,
                    'days_overdue' => $loan->due_date < now() ? now()->diffInDays($loan->due_date) : 0,
                    'renewal_count' => $loan->renewal_count ?? 0
                ];
            });

        return response()->json([
            'loans' => $loans
        ]);
    }

    /**
     * Check if user can borrow a book
     */
    public function checkBorrowEligibility(Book $book)
    {
        $user = Auth::user();
        $errors = [];

        // Check if book is available
        if ($book->availability_status !== 'available') {
            $errors[] = 'This book is not available for borrowing.';
        }

        // Check if user already has this book borrowed
        $existingBorrow = BorrowRecord::where('user_id', $user->id)
            ->where('book_id', $book->id)
            ->where('status', 'borrowed')
            ->first();

        if ($existingBorrow) {
            $errors[] = 'You already have this book borrowed.';
        }

        // Auto-return any overdue books for this user to free up borrow limit before checking
        $overdueRecords = BorrowRecord::where('user_id', $user->id)
            ->where('status', 'borrowed')
            ->where('due_date', '<', now())
            ->get();

        foreach ($overdueRecords as $overdueRecord) {
            $overdueRecord->update([
                'returned_date' => now(),
                'status' => 'returned',
                'notes' => ($overdueRecord->notes ? $overdueRecord->notes . ' | ' : '') . 'Auto-returned due to eligibility check'
            ]);
        }

        // Check user's current borrow limit (max 3 books) after auto-returning overdue books
        $currentBorrows = BorrowRecord::where('user_id', $user->id)
            ->where('status', 'borrowed')
            ->count();

        if ($currentBorrows >= 3) {
            $errors[] = 'You have reached the maximum borrow limit (3 books).';
        }


        return response()->json([
            'can_borrow' => empty($errors),
            'errors' => $errors
        ]);
    }

    /**
     * Get borrow statistics for user
     */
    public function getBorrowStats()
    {
        $user = Auth::user();

        $stats = [
            'current_borrows' => BorrowRecord::where('user_id', $user->id)
                ->where('status', 'borrowed')
                ->count(),
            'total_borrows' => BorrowRecord::where('user_id', $user->id)->count(),
        ];

        return response()->json([
            'stats' => $stats
        ]);
    }

    /**
     * Create a new loan
     */
    public function createLoan(Request $request)
    {
        $request->validate(['book_id' => 'required|exists:books,id']);

        $loan = BorrowRecord::create([
            'user_id' => Auth::id(),
            'book_id' => $request->book_id,
            'due_date' => now()->addDays(5)
        ]);

        return response()->json(['success' => true, 'message' => 'Book borrowed successfully']);
    }
}
