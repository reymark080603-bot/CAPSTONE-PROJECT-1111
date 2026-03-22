<?php

namespace App\Repositories;

use App\Models\Book;
use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class BookRepository
{
    protected $model;

    public function __construct(Book $model)
    {
        $this->model = $model;
    }

    /**
     * Get all books with optional filters
     */
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        $this->applyFilters($query, $filters);

        return $query->paginate($perPage);
    }

    /**
     * Find book by ID
     */
    public function findById(int $id): ?Book
    {
        return $this->model->find($id);
    }

    /**
     * Find book by ID with relationships
     */
    public function findByIdWithRelations(int $id, array $relations = []): ?Book
    {
        return $this->model->with($relations)->find($id);
    }

    /**
     * Create new book
     */
    public function create(array $data): Book
    {
        return $this->model->create($data);
    }

    /**
     * Update book
     */
    public function update(Book $book, array $data): bool
    {
        return $book->update($data);
    }

    /**
     * Delete book
     */
    public function delete(Book $book): bool
    {
        return $book->delete();
    }

    /**
     * Get books by status
     */
    public function getByStatus(string $status): Collection
    {
        return $this->model->where('availability_status', $status)->get();
    }

    /**
     * Get available books
     */
    public function getAvailableBooks(): Collection
    {
        return $this->model->where('availability_status', 'available')->get();
    }

    /**
     * Get books by category
     */
    public function getByCategory(string $categoryName): Collection
    {
        return $this->model->whereHas('categories', function ($query) use ($categoryName) {
            $query->where('name', $categoryName);
        })->get();
    }

    /**
     * Search books
     */
    public function search(string $query, array $filters = []): Collection
    {
        $searchQuery = $this->model->newQuery();

        $searchQuery->where(function ($q) use ($query) {
            $q->where('title', 'LIKE', "%{$query}%")
              ->orWhere('description', 'LIKE', "%{$query}%")
              ->orWhere('author', 'LIKE', "%{$query}%")
              ->orWhere('isbn', 'LIKE', "%{$query}%");
        });

        $this->applyFilters($searchQuery, $filters);

        return $searchQuery->get();
    }

    /**
     * Get books with borrow count
     */
    public function getBooksWithBorrowCount(array $filters = []): Collection
    {
        $query = $this->model->withCount('borrowRecords');

        $this->applyFilters($query, $filters);

        return $query->get();
    }

    /**
     * Get most borrowed books
     */
    public function getMostBorrowed(int $limit = 10): Collection
    {
        return $this->model->withCount('borrowRecords')
            ->orderByDesc('borrow_records_count')
            ->limit($limit)
            ->get();
    }

    /**
     * Get never borrowed books
     */
    public function getNeverBorrowed(): Collection
    {
        return $this->model->whereDoesntHave('borrowRecords')->get();
    }

    /**
     * Get books by course
     */
    public function getByCourse(string $course): Collection
    {
        return $this->model->where('course', $course)->get();
    }

    /**
     * Get books by year level
     */
    public function getByYearLevel(string $yearLevel): Collection
    {
        return $this->model->where('year_level', $yearLevel)->get();
    }

    /**
     * Get books with ebook files
     */
    public function getBooksWithEbooks(): Collection
    {
        return $this->model->whereNotNull('ebook_file')->get();
    }

    /**
     * Count books by status
     */
    public function countByStatus(): array
    {
        return $this->model->selectRaw('availability_status, COUNT(*) as count')
            ->groupBy('availability_status')
            ->pluck('count', 'availability_status')
            ->toArray();
    }

    /**
     * Get total books count
     */
    public function count(): int
    {
        return $this->model->count();
    }

    /**
     * Check if book exists
     */
    public function exists(int $id): bool
    {
        return $this->model->where('id', $id)->exists();
    }

    /**
     * Get books for export
     */
    public function getForExport(array $filters = []): Collection
    {
        $query = $this->model->newQuery();

        $this->applyFilters($query, $filters);

        return $query->get();
    }

    /**
     * Apply filters to query
     */
    protected function applyFilters($query, array $filters): void
    {
        if (isset($filters['search']) && $filters['search']) {
            $query->where(function ($q) use ($filters) {
                $q->where('title', 'LIKE', "%{$filters['search']}%")
                  ->orWhere('description', 'LIKE', "%{$filters['search']}%")
                  ->orWhere('author', 'LIKE', "%{$filters['search']}%")
                  ->orWhere('isbn', 'LIKE', "%{$filters['search']}%");
            });
        }

        if (isset($filters['category']) && $filters['category']) {
            $query->whereHas('categories', function ($q) use ($filters) {
                $q->where('name', $filters['category']);
            });
        }

        if (isset($filters['status']) && $filters['status']) {
            $query->where('availability_status', $filters['status']);
        }

        if (isset($filters['course']) && $filters['course']) {
            $query->where('course', $filters['course']);
        }

        if (isset($filters['year_level']) && $filters['year_level']) {
            $query->where('year_level', $filters['year_level']);
        }

        if (isset($filters['sort_by']) && $filters['sort_by']) {
            $sortOrder = $filters['sort_order'] ?? 'asc';
            $query->orderBy($filters['sort_by'], $sortOrder);
        }
    }
}
