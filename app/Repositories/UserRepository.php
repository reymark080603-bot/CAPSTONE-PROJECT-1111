<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;

class UserRepository
{
    protected $model;

    public function __construct(User $model)
    {
        $this->model = $model;
    }

    /**
     * Get all users with optional filters
     */
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        $this->applyFilters($query, $filters);

        return $query->paginate($perPage);
    }

    /**
     * Get students only
     */
    public function getStudents(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->whereHas('role', function($q) {
            $q->where('name', 'student');
        });

        $this->applyFilters($query, $filters);

        return $query->paginate($perPage);
    }

    /**
     * Get librarians only
     */
    public function getLibrarians(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->whereHas('role', function($q) {
            $q->where('name', 'librarian');
        });

        $this->applyFilters($query, $filters);

        return $query->paginate($perPage);
    }

    /**
     * Find user by ID
     */
    public function findById(int $id): ?User
    {
        return $this->model->find($id);
    }

    /**
     * Find user by ID with relationships
     */
    public function findByIdWithRelations(int $id, array $relations = []): ?User
    {
        return $this->model->with($relations)->find($id);
    }

    /**
     * Find user by email
     */
    public function findByEmail(string $email): ?User
    {
        return $this->model->where('email', $email)->first();
    }

    /**
     * Find user by student ID
     */
    public function findByStudentId(string $studentId): ?User
    {
        return $this->model->where('student_id', $studentId)->first();
    }

    /**
     * Create new user
     */
    public function create(array $data): User
    {
        // Hash password if provided
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        return $this->model->create($data);
    }

    /**
     * Update user
     */
    public function update(User $user, array $data): bool
    {
        // Hash password if provided
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        return $user->update($data);
    }

    /**
     * Delete user
     */
    public function delete(User $user): bool
    {
        return $user->delete();
    }

    /**
     * Get users by role
     */
    public function getByRole(string $role): Collection
    {
        return $this->model->where('role', $role)->get();
    }

    /**
     * Get active students (verified email)
     */
    public function getActiveStudents(): Collection
    {
        return $this->model->whereHas('role', function($q) {
            $q->where('name', 'student');
        })
            ->whereNotNull('email_verified_at')
            ->get();
    }

    /**
     * Get inactive students
     */
    public function getInactiveStudents(): Collection
    {
        return $this->model->whereHas('role', function($q) {
            $q->where('name', 'student');
        })
            ->whereNull('email_verified_at')
            ->get();
    }

    /**
     * Get students by course
     */
    public function getStudentsByCourse(string $course): Collection
    {
        return $this->model->whereHas('role', function($q) {
            $q->where('name', 'student');
        })
            ->where('course', $course)
            ->get();
    }

    /**
     * Get students by year level
     */
    public function getStudentsByYearLevel(string $yearLevel): Collection
    {
        return $this->model->whereHas('role', function($q) {
            $q->where('name', 'student');
        })
            ->where('year', $yearLevel)
            ->get();
    }

    /**
     * Search users
     */
    public function search(string $query, array $filters = []): Collection
    {
        $searchQuery = $this->model->newQuery();

        $searchQuery->where(function ($q) use ($query) {
            $q->where('firstname', 'LIKE', "%{$query}%")
              ->orWhere('lastname', 'LIKE', "%{$query}%")
              ->orWhere('email', 'LIKE', "%{$query}%")
              ->orWhere('student_id', 'LIKE', "%{$query}%");
        });

        $this->applyFilters($searchQuery, $filters);

        return $searchQuery->get();
    }

    /**
     * Get users with borrow statistics
     */
    public function getUsersWithBorrowStats(array $filters = []): Collection
    {
        $query = $this->model->withCount([
            'borrowRecords',
            'borrowRecords as active_borrows' => function ($query) {
                $query->where('status', 'borrowed');
            },
            'borrowRecords as overdue_books' => function ($query) {
                $query->where('status', 'borrowed')
                      ->where('due_date', '<', now());
            }
        ]);

        $this->applyFilters($query, $filters);

        return $query->get();
    }

    /**
     * Get users with fine statistics
     */
    public function getUsersWithFineStats(array $filters = []): Collection
    {
        $query = $this->model->with([
            'fines' => function ($query) {
                $query->selectRaw('user_id, status, SUM(amount) as total_amount')
                      ->groupBy('user_id', 'status');
            }
        ]);

        $this->applyFilters($query, $filters);

        return $query->get();
    }

    /**
     * Count users by role
     */
    public function countByRole(): array
    {
        return $this->model->selectRaw('role, COUNT(*) as count')
            ->groupBy('role')
            ->pluck('count', 'role')
            ->toArray();
    }

    /**
     * Count students by course
     */
    public function countStudentsByCourse(): array
    {
        return $this->model->whereHas('role', function($q) {
            $q->where('name', 'student');
        })
            ->selectRaw('course, COUNT(*) as count')
            ->whereNotNull('course')
            ->groupBy('course')
            ->pluck('count', 'course')
            ->toArray();
    }

    /**
     * Count students by year level
     */
    public function countStudentsByYearLevel(): array
    {
        return $this->model->whereHas('role', function($q) {
            $q->where('name', 'student');
        })
            ->selectRaw('year, COUNT(*) as count')
            ->whereNotNull('year')
            ->groupBy('year')
            ->pluck('count', 'year')
            ->toArray();
    }

    /**
     * Get total users count
     */
    public function count(): int
    {
        return $this->model->count();
    }

    /**
     * Get students count
     */
    public function countStudents(): int
    {
        return $this->model->whereHas('role', function($q) {
            $q->where('name', 'student');
        })->count();
    }

    /**
     * Get librarians count
     */
    public function countLibrarians(): int
    {
        return $this->model->whereHas('role', function($q) {
            $q->where('name', 'librarian');
        })->count();
    }

    /**
     * Check if user exists
     */
    public function exists(int $id): bool
    {
        return $this->model->where('id', $id)->exists();
    }

    /**
     * Check if email exists
     */
    public function emailExists(string $email, int $excludeId = null): bool
    {
        $query = $this->model->where('email', $email);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Check if student ID exists
     */
    public function studentIdExists(string $studentId, int $excludeId = null): bool
    {
        $query = $this->model->where('student_id', $studentId);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Get users for export
     */
    public function getForExport(array $filters = []): Collection
    {
        $query = $this->model->newQuery();

        $this->applyFilters($query, $filters);

        return $query->get();
    }

    /**
     * Verify user password
     */
    public function verifyPassword(User $user, string $password): bool
    {
        return Hash::check($password, $user->password);
    }

    /**
     * Update user password
     */
    public function updatePassword(User $user, string $password): bool
    {
        return $user->update(['password' => Hash::make($password)]);
    }

    /**
     * Activate user (verify email)
     */
    public function activateUser(User $user): bool
    {
        return $user->update(['email_verified_at' => now()]);
    }

    /**
     * Deactivate user
     */
    public function deactivateUser(User $user): bool
    {
        return $user->update(['email_verified_at' => null]);
    }

    /**
     * Apply filters to query
     */
    protected function applyFilters($query, array $filters): void
    {
        if (isset($filters['role']) && $filters['role']) {
            $query->where('role', $filters['role']);
        }

        if (isset($filters['status']) && $filters['status'] !== 'all') {
            if ($filters['status'] === 'active') {
                $query->whereNotNull('email_verified_at');
            } elseif ($filters['status'] === 'inactive') {
                $query->whereNull('email_verified_at');
            }
        }

        if (isset($filters['course']) && $filters['course']) {
            $query->where('course', $filters['course']);
        }

        if (isset($filters['year']) && $filters['year']) {
            $query->where('year', $filters['year']);
        }

        if (isset($filters['search']) && $filters['search']) {
            $query->where(function ($q) use ($filters) {
                $q->where('firstname', 'LIKE', "%{$filters['search']}%")
                  ->orWhere('lastname', 'LIKE', "%{$filters['search']}%")
                  ->orWhere('email', 'LIKE', "%{$filters['search']}%")
                  ->orWhere('student_id', 'LIKE', "%{$filters['search']}%");
            });
        }

        if (isset($filters['sort_by']) && $filters['sort_by']) {
            $sortOrder = $filters['sort_order'] ?? 'asc';
            $query->orderBy($filters['sort_by'], $sortOrder);
        }
    }
}
