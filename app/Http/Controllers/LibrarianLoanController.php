<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\BorrowRecord;
use App\Models\Book;
use App\Models\User;
use App\Models\Fine;
use Carbon\Carbon;

class LibrarianLoanController extends Controller
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
     * Display loans management page
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Get filter parameters
        $status = $request->get('status', 'all');
        $search = $request->get('search');
        $sortBy = $request->get('sort_by', 'borrowed_date');
        $sortOrder = $request->get('sort_order', 'desc');

        return view('librarian.loans.index', compact(
            'user', 'status', 'search', 'sortBy', 'sortOrder'
        ));
    }

    /**
     * Get loans data via API
     */
    public function api(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $status = $request->get('status', 'all');
        $search = $request->get('search');
        $sortBy = $request->get('sort_by', 'borrowed_date');
        $sortOrder = $request->get('sort_order', 'desc');

        // Build query
        $query = BorrowRecord::with(['user', 'book']);

        // Apply status filter
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        // Apply search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('firstname', 'LIKE', "%{$search}%")
                             ->orWhere('lastname', 'LIKE', "%{$search}%")
                             ->orWhere('email', 'LIKE', "%{$search}%");
                })
                ->orWhereHas('book', function ($bookQuery) use ($search) {
                    $bookQuery->where('title', 'LIKE', "%{$search}%")
                             ->orWhere('author', 'LIKE', "%{$search}%")
                             ->orWhere('isbn', 'LIKE', "%{$search}%");
                });
            });
        }

        // Apply sorting
        $query->orderBy($sortBy, $sortOrder);

        // Paginate results
        $loans = $query->paginate($perPage);

        // Add computed fields
        $loans->getCollection()->transform(function ($loan) {
            $loan->is_overdue = $loan->status === 'borrowed' && $loan->due_date < now();
            $loan->days_overdue = $loan->is_overdue ? now()->diffInDays($loan->due_date) : 0;
            $loan->days_remaining = !$loan->is_overdue && $loan->status === 'borrowed'
                ? max(0, now()->diffInDays($loan->due_date, false))
                : 0;
            return $loan;
        });

        return response()->json([
            'loans' => $loans
        ]);
    }

    /**
     * Get loan details
     */
    public function show(BorrowRecord $borrowRecord)
    {
        $user = Auth::user();

        // Load relationships
        $borrowRecord->load(['user', 'book']);

        // Calculate additional data
        $borrowRecord->is_overdue = $borrowRecord->status === 'borrowed' && $borrowRecord->due_date < now();
        $borrowRecord->days_overdue = $borrowRecord->is_overdue ? now()->diffInDays($borrowRecord->due_date) : 0;
        $borrowRecord->days_remaining = !$borrowRecord->is_overdue && $borrowRecord->status === 'borrowed'
            ? max(0, now()->diffInDays($borrowRecord->due_date, false))
            : 0;

        return view('librarian.loans.show', compact('user', 'borrowRecord'));
    }

    /**
     * Update loan status
     */
    public function updateStatus(Request $request, BorrowRecord $borrowRecord)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:borrowed,returned,lost,damaged',
            'notes' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $oldStatus = $borrowRecord->status;
            $newStatus = $request->status;

            // Update the borrow record
            $updateData = ['status' => $newStatus];

            if ($newStatus === 'returned' && !$borrowRecord->returned_date) {
                $updateData['returned_date'] = now();
            }

            if ($request->notes) {
                $updateData['notes'] = $request->notes;
            }

            $borrowRecord->update($updateData);

            // Handle book availability for open access system
            // Books remain available regardless of loan status

            // Create fine if book is returned overdue or damaged/lost
            if (($newStatus === 'returned' && $borrowRecord->due_date < now()) ||
                in_array($newStatus, ['lost', 'damaged'])) {

                $fineAmount = 0;
                $fineReason = '';

                if ($newStatus === 'returned' && $borrowRecord->due_date < now()) {
                    $daysOverdue = now()->diffInDays($borrowRecord->due_date);
                    $fineAmount = $daysOverdue * 5; // 5 pesos per day
                    $fineReason = "Overdue return ({$daysOverdue} days)";
                } elseif ($newStatus === 'lost') {
                    $fineAmount = 500; // Fixed amount for lost books
                    $fineReason = 'Lost book';
                } elseif ($newStatus === 'damaged') {
                    $fineAmount = 200; // Fixed amount for damaged books
                    $fineReason = 'Damaged book';
                }

                if ($fineAmount > 0) {
                    Fine::create([
                        'user_id' => $borrowRecord->user_id,
                        'borrow_record_id' => $borrowRecord->id,
                        'amount' => $fineAmount,
                        'reason' => $fineReason,
                        'status' => 'pending',
                        'issued_date' => now()
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Loan status updated successfully!',
                'borrow_record' => $borrowRecord->load(['user', 'book'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update loan status.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Return a book (mark loan as returned)
     */
    public function returnBook(Request $request, BorrowRecord $borrowRecord)
    {
        // Check if loan belongs to authenticated user (librarian)
        if ($borrowRecord->status !== 'borrowed') {
            return response()->json([
                'success' => false,
                'message' => 'This book is not currently borrowed.'
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Update borrow record
            $borrowRecord->update([
                'returned_date' => now(),
                'status' => 'returned'
            ]);

            // Open access system - book remains available

            // Check for overdue and create fine if necessary
            if ($borrowRecord->due_date < now()) {
                $daysOverdue = now()->diffInDays($borrowRecord->due_date);
                $fineAmount = $daysOverdue * 5; // 5 pesos per day

                Fine::create([
                    'user_id' => $borrowRecord->user_id,
                    'borrow_record_id' => $borrowRecord->id,
                    'amount' => $fineAmount,
                    'reason' => "Overdue return ({$daysOverdue} days)",
                    'status' => 'pending',
                    'issued_date' => now()
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Book returned successfully!',
                'borrow_record' => $borrowRecord->load(['user', 'book'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to return book.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Renew a loan
     */
    public function renew(Request $request, BorrowRecord $borrowRecord)
    {
        // Check if loan is eligible for renewal
        if ($borrowRecord->status !== 'borrowed') {
            return response()->json([
                'success' => false,
                'message' => 'This book is not currently borrowed.'
            ], 400);
        }

        if ($borrowRecord->due_date < now()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot renew overdue loans. Please return the book first.'
            ], 400);
        }

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
                'message' => 'Loan renewed successfully!',
                'new_due_date' => $borrowRecord->due_date->format('Y-m-d'),
                'borrow_record' => $borrowRecord->load(['user', 'book'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to renew loan.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Bulk return books
     */
    public function bulkReturn(Request $request)
    {
        $loanIds = $request->get('loan_ids', []);

        if (empty($loanIds)) {
            return response()->json([
                'success' => false,
                'message' => 'No loans selected for return.'
            ], 400);
        }

        try {
            DB::beginTransaction();

            $returnedCount = 0;
            $failedLoans = [];

            foreach ($loanIds as $loanId) {
                $borrowRecord = BorrowRecord::find($loanId);
                if (!$borrowRecord) continue;

                if ($borrowRecord->status !== 'borrowed') {
                    $failedLoans[] = $borrowRecord->book->title ?? 'Unknown Book';
                    continue;
                }

                // Update borrow record
                $borrowRecord->update([
                    'returned_date' => now(),
                    'status' => 'returned'
                ]);

                // Check for overdue and create fine if necessary
                if ($borrowRecord->due_date < now()) {
                    $daysOverdue = now()->diffInDays($borrowRecord->due_date);
                    $fineAmount = $daysOverdue * 5;

                    Fine::create([
                        'user_id' => $borrowRecord->user_id,
                        'borrow_record_id' => $borrowRecord->id,
                        'amount' => $fineAmount,
                        'reason' => "Overdue return ({$daysOverdue} days)",
                        'status' => 'pending',
                        'issued_date' => now()
                    ]);
                }

                $returnedCount++;
            }

            DB::commit();

            $message = "Successfully returned {$returnedCount} book(s).";
            if (!empty($failedLoans)) {
                $message .= " Could not return: " . implode(', ', $failedLoans) . " (not currently borrowed).";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'returned_count' => $returnedCount,
                'failed_loans' => $failedLoans
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to return books.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Show overdue loans page
     */
    public function overdue(Request $request)
    {
        $user = Auth::user();

        return view('librarian.loans.overdue', compact('user'));
    }

    /**
     * Get overdue loans API
     */
    public function overdueApi(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $search = $request->get('search');
        $sortBy = $request->get('sort_by', 'due_date');
        $sortOrder = $request->get('sort_order', 'asc');

        // Build query for overdue loans
        $query = BorrowRecord::with(['user', 'book'])
            ->where('status', 'borrowed')
            ->where('due_date', '<', now());

        // Apply search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('firstname', 'LIKE', "%{$search}%")
                             ->orWhere('lastname', 'LIKE', "%{$search}%")
                             ->orWhere('email', 'LIKE', "%{$search}%");
                })
                ->orWhereHas('book', function ($bookQuery) use ($search) {
                    $bookQuery->where('title', 'LIKE', "%{$search}%")
                             ->orWhere('author', 'LIKE', "%{$search}%");
                });
            });
        }

        // Apply sorting
        $query->orderBy($sortBy, $sortOrder);

        // Paginate results
        $overdueLoans = $query->paginate($perPage);

        // Add computed fields
        $overdueLoans->getCollection()->transform(function ($loan) {
            $loan->days_overdue = now()->diffInDays($loan->due_date);
            $loan->fine_amount = $loan->days_overdue * 5; // 5 pesos per day
            return $loan;
        });

        return response()->json([
            'overdue_loans' => $overdueLoans
        ]);
    }

    /**
     * Send overdue reminders
     */
    public function sendOverdueReminders(Request $request)
    {
        $loanIds = $request->get('loan_ids', []);

        if (empty($loanIds)) {
            return response()->json([
                'success' => false,
                'message' => 'No loans selected for reminders.'
            ], 400);
        }

        try {
            $sentCount = 0;
            $failedLoans = [];

            foreach ($loanIds as $loanId) {
                $borrowRecord = BorrowRecord::with(['user', 'book'])->find($loanId);
                if (!$borrowRecord) continue;

                if ($borrowRecord->status !== 'borrowed' || $borrowRecord->due_date >= now()) {
                    $failedLoans[] = $borrowRecord->book->title ?? 'Unknown Book';
                    continue;
                }

                // In a real application, you would send an email/SMS here
                // For now, we'll just mark as reminder sent
                $borrowRecord->update([
                    'reminder_sent_at' => now()
                ]);

                $sentCount++;
            }

            $message = "Successfully sent reminders for {$sentCount} overdue loan(s).";
            if (!empty($failedLoans)) {
                $message .= " Could not send reminders for: " . implode(', ', $failedLoans) . " (not overdue or not borrowed).";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'sent_count' => $sentCount,
                'failed_loans' => $failedLoans
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send reminders.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
