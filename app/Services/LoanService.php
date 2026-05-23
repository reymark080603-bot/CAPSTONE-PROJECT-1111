<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\Book;
use App\Models\BorrowRecord;
use App\Models\User;
use Carbon\Carbon;

class LoanService
{
    /**
     * Get loans with filters and pagination
     */
    public function getLoans($filters = [], $perPage = 15)
    {
        $query = BorrowRecord::with(['user', 'book']);

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
            $query->where(function ($q) use ($filters) {
                $q->whereHas('user', function ($userQuery) use ($filters) {
                    $userQuery->where('firstname', 'LIKE', "%{$filters['search']}%")
                             ->orWhere('lastname', 'LIKE', "%{$filters['search']}%")
                             ->orWhere('email', 'LIKE', "%{$filters['search']}%");
                })
                ->orWhereHas('book', function ($bookQuery) use ($filters) {
                    $bookQuery->where('title', 'LIKE', "%{$filters['search']}%")
                             ->orWhere('author', 'LIKE', "%{$filters['search']}%")
                             ->orWhere('isbn', 'LIKE', "%{$filters['search']}%");
                });
            });
        }

        // Apply sorting
        $sortBy = $filters['sort_by'] ?? 'borrowed_date';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    /**
     * Create a new loan (borrow a book)
     */
    public function createLoan($userId, $bookId, $borrowDays = 1)
    {
        DB::beginTransaction();

        try {
            // Check if book is available
            $book = Book::findOrFail($bookId);
            if ($book->availability_status !== 'available') {
                throw new \Exception('This book is not available for borrowing.');
            }

            // Check if user already has this book borrowed
            $existingBorrow = BorrowRecord::where('user_id', $userId)
                ->where('book_id', $bookId)
                ->where('status', 'borrowed')
                ->first();

            if ($existingBorrow) {
                throw new \Exception('You already have this book borrowed.');
            }

            // Check user's current borrow limit (max 3 books)
            $currentBorrows = BorrowRecord::where('user_id', $userId)
                ->where('status', 'borrowed')
                ->count();

            if ($currentBorrows >= 3) {
                throw new \Exception('You have reached the maximum borrow limit (3 books).');
            }


            // Create borrow record
            $borrowRecord = BorrowRecord::create([
                'user_id' => $userId,
                'book_id' => $bookId,
                'borrowed_date' => now(),
                'due_date' => now()->addDays($borrowDays),
                'status' => 'borrowed'
            ]);

            DB::commit();
            return $borrowRecord->load(['user', 'book']);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Return a book
     */
    public function returnBook(BorrowRecord $borrowRecord)
    {
        if ($borrowRecord->status !== 'borrowed') {
            throw new \Exception('This book has already been returned.');
        }

        DB::beginTransaction();

        try {
            // Update borrow record
            $borrowRecord->update([
                'returned_date' => now(),
                'status' => 'returned'
            ]);

            DB::commit();
            return $borrowRecord->load(['user', 'book']);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Renew a loan
     */
    public function renewLoan(BorrowRecord $borrowRecord)
    {
        if ($borrowRecord->status !== 'borrowed') {
            throw new \Exception('This book is not currently borrowed.');
        }

        if ($borrowRecord->due_date < now()) {
            throw new \Exception('Cannot renew overdue loans. Please return the book first.');
        }

        $currentRenewals = $borrowRecord->renewal_count ?? 0;
        if ($currentRenewals >= 2) {
            throw new \Exception('Maximum renewal limit reached for this loan.');
        }

        $borrowRecord->update([
            'due_date' => now()->addDays(1),
            'renewal_count' => $currentRenewals + 1
        ]);

        return $borrowRecord->load(['user', 'book']);
    }

    /**
     * Update loan status
     */
    public function updateLoanStatus(BorrowRecord $borrowRecord, $status, $notes = null)
    {
        $validStatuses = ['borrowed', 'returned', 'lost', 'damaged'];
        if (!in_array($status, $validStatuses)) {
            throw new \Exception('Invalid status provided.');
        }

        DB::beginTransaction();

        try {
            $oldStatus = $borrowRecord->status;
            $updateData = ['status' => $status];

            if ($status === 'returned' && !$borrowRecord->returned_date) {
                $updateData['returned_date'] = now();
            }

            if ($notes) {
                $updateData['notes'] = $notes;
            }

            $borrowRecord->update($updateData);

            DB::commit();
            return $borrowRecord->load(['user', 'book']);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Bulk return books
     */
    public function bulkReturnBooks($loanIds)
    {
        $returnedCount = 0;
        $failedLoans = [];

        foreach ($loanIds as $loanId) {
            try {
                $borrowRecord = BorrowRecord::find($loanId);
                if (!$borrowRecord) continue;

                $this->returnBook($borrowRecord);
                $returnedCount++;
            } catch (\Exception $e) {
                $failedLoans[] = $borrowRecord->book->title ?? 'Unknown Book';
            }
        }

        return [
            'returned_count' => $returnedCount,
            'failed_loans' => $failedLoans
        ];
    }

    /**
     * Get overdue loans
     */
    public function getOverdueLoans($filters = [], $perPage = 15)
    {
        $query = BorrowRecord::with(['user', 'book'])
            ->where('status', 'borrowed')
            ->where('due_date', '<', now());

        // Apply search filter
        if (isset($filters['search']) && $filters['search']) {
            $query->where(function ($q) use ($filters) {
                $q->whereHas('user', function ($userQuery) use ($filters) {
                    $userQuery->where('firstname', 'LIKE', "%{$filters['search']}%")
                             ->orWhere('lastname', 'LIKE', "%{$filters['search']}%")
                             ->orWhere('email', 'LIKE', "%{$filters['search']}%");
                })
                ->orWhereHas('book', function ($bookQuery) use ($filters) {
                    $bookQuery->where('title', 'LIKE', "%{$filters['search']}%")
                             ->orWhere('author', 'LIKE', "%{$filters['search']}%");
                });
            });
        }

        // Apply sorting
        $sortBy = $filters['sort_by'] ?? 'due_date';
        $sortOrder = $filters['sort_order'] ?? 'asc';
        $query->orderBy($sortBy, $sortOrder);

        $loans = $query->paginate($perPage);

        // Add computed fields
        $loans->getCollection()->transform(function ($loan) {
            $loan->days_overdue = now()->diffInDays($loan->due_date);
            return $loan;
        });

        return $loans;
    }

    /**
     * Send overdue reminders
     */
    public function sendOverdueReminders($loanIds)
    {
        $sentCount = 0;
        $failedLoans = [];

        foreach ($loanIds as $loanId) {
            try {
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
            } catch (\Exception $e) {
                $failedLoans[] = $borrowRecord->book->title ?? 'Unknown Book';
            }
        }

        return [
            'sent_count' => $sentCount,
            'failed_loans' => $failedLoans
        ];
    }

    /**
     * Get user's current loans
     */
    public function getUserCurrentLoans($userId)
    {
        $loans = BorrowRecord::with('book')
            ->where('user_id', $userId)
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
                    'days_remaining' => $loan->days_remaining,
                    'days_overdue' => $loan->days_past_due,
                    'renewal_count' => $loan->renewal_count ?? 0
                ];
            });

        return $loans;
    }

    /**
     * Get user's borrowing history
     */
    public function getUserBorrowingHistory($userId, $filters = [], $perPage = 15)
    {
        $query = BorrowRecord::with('book')
            ->where('user_id', $userId);

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
     * Check if user can borrow a book
     */
    public function checkBorrowEligibility($userId, $bookId)
    {
        $errors = [];

        // Check if book is available
        $book = Book::find($bookId);
        if (!$book || $book->availability_status !== 'available') {
            $errors[] = 'This book is not available for borrowing.';
        }

        // Check if user already has this book borrowed
        $existingBorrow = BorrowRecord::where('user_id', $userId)
            ->where('book_id', $bookId)
            ->where('status', 'borrowed')
            ->first();

        if ($existingBorrow) {
            $errors[] = 'You already have this book borrowed.';
        }

        // Check user's current borrow limit (max 3 books)
        $currentBorrows = BorrowRecord::where('user_id', $userId)
            ->where('status', 'borrowed')
            ->count();

        if ($currentBorrows >= 3) {
            $errors[] = 'You have reached the maximum borrow limit (3 books).';
        }


        return [
            'can_borrow' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Get loan statistics
     */
    public function getLoanStats($userId = null, $dateFrom = null, $dateTo = null)
    {
        $query = BorrowRecord::query();

        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($dateFrom) {
            $query->where('borrowed_date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->where('borrowed_date', '<=', $dateTo);
        }

        $stats = [
            'total_borrows' => (clone $query)->count(),
            'active_borrows' => (clone $query)->where('status', 'borrowed')->count(),
            'returned_books' => (clone $query)->where('status', 'returned')->count(),
            'overdue_books' => (clone $query)->where('status', 'borrowed')
                ->where('due_date', '<', now())->count(),
        ];

        return $stats;
    }
}
