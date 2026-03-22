<?php

namespace App\Repositories;

use App\Models\BorrowRecord;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;

class BorrowRecordRepository
{
    protected $model;

    public function __construct(BorrowRecord $model)
    {
        $this->model = $model;
    }

    /**
     * Get all borrow records with optional filters
     */
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        $this->applyFilters($query, $filters);

        return $query->paginate($perPage);
    }

    /**
     * Find borrow record by ID
     */
    public function findById(int $id): ?BorrowRecord
    {
        return $this->model->find($id);
    }

    /**
     * Find borrow record by ID with relationships
     */
    public function findByIdWithRelations(int $id, array $relations = []): ?BorrowRecord
    {
        return $this->model->with($relations)->find($id);
    }

    /**
     * Create new borrow record
     */
    public function create(array $data): BorrowRecord
    {
        return $this->model->create($data);
    }

    /**
     * Update borrow record
     */
    public function update(BorrowRecord $borrowRecord, array $data): bool
    {
        return $borrowRecord->update($data);
    }

    /**
     * Delete borrow record
     */
    public function delete(BorrowRecord $borrowRecord): bool
    {
        return $borrowRecord->delete();
    }

    /**
     * Get borrow records by status
     */
    public function getByStatus(string $status): Collection
    {
        return $this->model->where('status', $status)->get();
    }

    /**
     * Get active borrow records (currently borrowed)
     */
    public function getActiveBorrows(): Collection
    {
        return $this->model->where('status', 'borrowed')->get();
    }

    /**
     * Get returned borrow records
     */
    public function getReturnedBorrows(): Collection
    {
        return $this->model->where('status', 'returned')->get();
    }

    /**
     * Get overdue borrow records
     */
    public function getOverdueBorrows(): Collection
    {
        return $this->model->where('status', 'borrowed')
            ->where('due_date', '<', now())
            ->get();
    }

    /**
     * Get borrow records by user
     */
    public function getByUser(int $userId, array $filters = []): Collection
    {
        $query = $this->model->where('user_id', $userId);

        $this->applyFilters($query, $filters);

        return $query->get();
    }

    /**
     * Get borrow records by book
     */
    public function getByBook(int $bookId, array $filters = []): Collection
    {
        $query = $this->model->where('book_id', $bookId);

        $this->applyFilters($query, $filters);

        return $query->get();
    }

    /**
     * Get borrow records within date range
     */
    public function getByDateRange(Carbon $startDate, Carbon $endDate): Collection
    {
        return $this->model->whereBetween('borrowed_date', [$startDate, $endDate])->get();
    }

    /**
     * Get borrow records due soon (within next 3 days)
     */
    public function getDueSoon(int $days = 3): Collection
    {
        return $this->model->where('status', 'borrowed')
            ->where('due_date', '>=', now())
            ->where('due_date', '<=', now()->addDays($days))
            ->get();
    }

    /**
     * Get borrow records by user with pagination
     */
    public function getByUserPaginated(int $userId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->where('user_id', $userId);

        $this->applyFilters($query, $filters);

        return $query->paginate($perPage);
    }

    /**
     * Check if user has active borrow for a book
     */
    public function hasActiveBorrow(int $userId, int $bookId): bool
    {
        return $this->model->where('user_id', $userId)
            ->where('book_id', $bookId)
            ->where('status', 'borrowed')
            ->exists();
    }

    /**
     * Get user's current active borrows count
     */
    public function getUserActiveBorrowsCount(int $userId): int
    {
        return $this->model->where('user_id', $userId)
            ->where('status', 'borrowed')
            ->count();
    }

    /**
     * Get user's overdue borrows count
     */
    public function getUserOverdueBorrowsCount(int $userId): int
    {
        return $this->model->where('user_id', $userId)
            ->where('status', 'borrowed')
            ->where('due_date', '<', now())
            ->count();
    }

    /**
     * Get borrow statistics
     */
    public function getBorrowStats(array $filters = []): array
    {
        $query = $this->model->newQuery();

        $this->applyFilters($query, $filters);

        $stats = [
            'total_borrows' => (clone $query)->count(),
            'active_borrows' => (clone $query)->where('status', 'borrowed')->count(),
            'returned_borrows' => (clone $query)->where('status', 'returned')->count(),
            'overdue_borrows' => (clone $query)->where('status', 'borrowed')
                ->where('due_date', '<', now())->count(),
        ];

        return $stats;
    }

    /**
     * Get borrow records with fines
     */
    public function getWithFines(array $filters = []): Collection
    {
        $query = $this->model->with('fines');

        $this->applyFilters($query, $filters);

        return $query->get();
    }

    /**
     * Get borrow records for export
     */
    public function getForExport(array $filters = []): Collection
    {
        $query = $this->model->with(['user', 'book']);

        $this->applyFilters($query, $filters);

        return $query->get();
    }

    /**
     * Get monthly borrowing trends
     */
    public function getMonthlyTrends(int $months = 12): array
    {
        $trends = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $count = $this->model->whereYear('borrowed_date', $month->year)
                ->whereMonth('borrowed_date', $month->month)
                ->count();

            $trends[] = [
                'month' => $month->format('M Y'),
                'count' => $count
            ];
        }

        return $trends;
    }

    /**
     * Get borrowing trends by period
     */
    public function getBorrowingTrends(string $period = 'month', int $limit = 12): array
    {
        $driver = \DB::getDriverName();
        $query = $this->model->query();

        // Determine date format based on period
        switch ($period) {
            case 'day':
                if ($driver === 'sqlite') {
                    $query->selectRaw("strftime('%Y-%m-%d', borrowed_date) as date");
                } elseif ($driver === 'pgsql') {
                    $query->selectRaw("DATE(borrowed_date) as date");
                } else {
                    $query->selectRaw('DATE(borrowed_date) as date');
                }
                break;
            case 'week':
                if ($driver === 'sqlite') {
                    $query->selectRaw("strftime('%Y-%W', borrowed_date) as date");
                } elseif ($driver === 'pgsql') {
                    $query->selectRaw("EXTRACT(YEAR FROM borrowed_date) || '-' || EXTRACT(WEEK FROM borrowed_date) as date");
                } else {
                    $query->selectRaw('YEARWEEK(borrowed_date) as date');
                }
                break;
            case 'month':
                if ($driver === 'sqlite') {
                    $query->selectRaw("strftime('%Y-%m', borrowed_date) as date");
                } elseif ($driver === 'pgsql') {
                    $query->selectRaw("EXTRACT(YEAR FROM borrowed_date) || '-' || LPAD(EXTRACT(MONTH FROM borrowed_date)::text, 2, '0') as date");
                } else {
                    $query->selectRaw("DATE_FORMAT(borrowed_date, '%Y-%m') as date");
                }
                break;
            case 'year':
                if ($driver === 'sqlite') {
                    $query->selectRaw("strftime('%Y', borrowed_date) as date");
                } elseif ($driver === 'pgsql') {
                    $query->selectRaw("EXTRACT(YEAR FROM borrowed_date)::text as date");
                } else {
                    $query->selectRaw('YEAR(borrowed_date) as date');
                }
                break;
        }

        $trends = $query->selectRaw('COUNT(*) as count')
            ->where('borrowed_date', '>=', now()->subMonths($limit))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'count' => (int)$item->count
                ];
            });

        return $trends->toArray();
    }

    /**
     * Get most borrowed books
     */
    public function getMostBorrowedBooks(int $limit = 10): Collection
    {
        return $this->model->select('book_id', \DB::raw('COUNT(*) as borrow_count'))
            ->with('book')
            ->groupBy('book_id')
            ->orderByDesc('borrow_count')
            ->limit($limit)
            ->get();
    }

    /**
     * Get most active borrowers
     */
    public function getMostActiveBorrowers(int $limit = 10): Collection
    {
        return $this->model->select('user_id', \DB::raw('COUNT(*) as borrow_count'))
            ->with('user')
            ->groupBy('user_id')
            ->orderByDesc('borrow_count')
            ->limit($limit)
            ->get();
    }

    /**
     * Check if borrow record exists
     */
    public function exists(int $id): bool
    {
        return $this->model->where('id', $id)->exists();
    }

    /**
     * Apply filters to query
     */
    protected function applyFilters($query, array $filters): void
    {
        if (isset($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['user_id']) && $filters['user_id']) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['book_id']) && $filters['book_id']) {
            $query->where('book_id', $filters['book_id']);
        }

        if (isset($filters['date_from']) && $filters['date_from']) {
            $query->where('borrowed_date', '>=', Carbon::parse($filters['date_from']));
        }

        if (isset($filters['date_to']) && $filters['date_to']) {
            $query->where('borrowed_date', '<=', Carbon::parse($filters['date_to']));
        }

        if (isset($filters['overdue_only']) && $filters['overdue_only']) {
            $query->where('status', 'borrowed')
                  ->where('due_date', '<', now());
        }

        if (isset($filters['due_soon']) && $filters['due_soon']) {
            $query->where('status', 'borrowed')
                  ->where('due_date', '>=', now())
                  ->where('due_date', '<=', now()->addDays(3));
        }

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

        if (isset($filters['sort_by']) && $filters['sort_by']) {
            $sortOrder = $filters['sort_order'] ?? 'asc';
            $query->orderBy($filters['sort_by'], $sortOrder);
        }
    }
}
