<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Models\Book;
use App\Models\Category;
use App\Models\Author;
use App\Models\Publisher;
use App\Models\User;
use App\Models\BorrowRecord;

class BookService
{
    /**
     * Get books with filters and pagination
     */
    public function getBooks($filters = [], $perPage = 15)
    {
        $query = Book::query();

        // Apply filters
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

        // Apply sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    /**
     * Create a new book
     */
    public function createBook($data, $files = [])
    {
        DB::beginTransaction();

        try {
            // Handle cover photo upload
            $coverPhotoPath = null;
            if (isset($files['cover_photo'])) {
                $coverPhotoPath = $files['cover_photo']->store('covers', 'public');
            }

            // Handle ebook file upload
            $ebookFilePath = null;
            if (isset($files['ebook_file'])) {
                $ebookFilePath = $files['ebook_file']->store('ebooks', 'public');
            }

            // Create the book
            $book = Book::create([
                'title' => $data['title'],
                'author' => $data['author'],
                'publisher' => $data['publisher'] ?? null,
                'published_year' => $data['published_year'] ?? null,
                'isbn' => $data['isbn'] ?? null,
                'language' => $data['language'] ?? 'English',
                'category' => $data['category'] ?? null,
                'course' => $data['course'] ?? null,
                'year_level' => $data['year_level'] ?? null,
                'description' => $data['description'] ?? null,
                'cover_photo' => $coverPhotoPath,
                'ebook_file' => $ebookFilePath,
                'availability_status' => 'available',
                'added_by' => auth()->id(),
            ]);

            // Handle category association
            if (isset($data['category']) && $data['category']) {
                $category = Category::firstOrCreate(['name' => $data['category']]);
                $book->categories()->attach($category->id);
            }

            DB::commit();
            return $book;

        } catch (\Exception $e) {
            DB::rollBack();

            // Clean up uploaded files if book creation failed
            if (isset($coverPhotoPath) && Storage::disk('public')->exists($coverPhotoPath)) {
                Storage::disk('public')->delete($coverPhotoPath);
            }
            if (isset($ebookFilePath) && Storage::disk('public')->exists($ebookFilePath)) {
                Storage::disk('public')->delete($ebookFilePath);
            }

            throw $e;
        }
    }

    /**
     * Update a book
     */
    public function updateBook(Book $book, $data, $files = [])
    {
        DB::beginTransaction();

        try {
            // Handle cover photo upload
            if (isset($files['cover_photo'])) {
                // Delete old cover photo
                if ($book->cover_photo && Storage::disk('public')->exists($book->cover_photo)) {
                    Storage::disk('public')->delete($book->cover_photo);
                }
                $coverPhotoPath = $files['cover_photo']->store('covers', 'public');
                $book->cover_photo = $coverPhotoPath;
            }

            // Handle ebook file upload
            if (isset($files['ebook_file'])) {
                // Delete old ebook file
                if ($book->ebook_file && Storage::disk('public')->exists($book->ebook_file)) {
                    Storage::disk('public')->delete($book->ebook_file);
                }
                $ebookFilePath = $files['ebook_file']->store('ebooks', 'public');
                $book->ebook_file = $ebookFilePath;
            }

            // Update book data
            $book->update([
                'title' => $data['title'],
                'author' => $data['author'],
                'publisher' => $data['publisher'] ?? null,
                'published_year' => $data['published_year'] ?? null,
                'isbn' => $data['isbn'] ?? null,
                'language' => $data['language'] ?? 'English',
                'category' => $data['category'] ?? null,
                'course' => $data['course'] ?? null,
                'year_level' => $data['year_level'] ?? null,
                'description' => $data['description'] ?? null,
            ]);

            // Update category association
            if (isset($data['category']) && $data['category']) {
                $category = Category::firstOrCreate(['name' => $data['category']]);
                $book->categories()->sync([$category->id]);
            } else {
                $book->categories()->detach();
            }

            DB::commit();
            return $book;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete a book
     */
    public function deleteBook(Book $book)
    {
        // Check if book is currently borrowed
        $activeBorrows = BorrowRecord::where('book_id', $book->id)
            ->where('status', 'borrowed')
            ->count();

        if ($activeBorrows > 0) {
            throw new \Exception('Cannot delete book that is currently borrowed by students.');
        }

        // Delete associated files
        if ($book->cover_photo && Storage::disk('public')->exists($book->cover_photo)) {
            Storage::disk('public')->delete($book->cover_photo);
        }
        if ($book->ebook_file && Storage::disk('public')->exists($book->ebook_file)) {
            Storage::disk('public')->delete($book->ebook_file);
        }

        return $book->delete();
    }

    /**
     * Bulk delete books
     */
    public function bulkDeleteBooks($bookIds)
    {
        $deletedCount = 0;
        $failedBooks = [];

        foreach ($bookIds as $bookId) {
            $book = Book::find($bookId);
            if (!$book) continue;

            try {
                $this->deleteBook($book);
                $deletedCount++;
            } catch (\Exception $e) {
                $failedBooks[] = $book->title;
            }
        }

        return [
            'deleted_count' => $deletedCount,
            'failed_books' => $failedBooks
        ];
    }

    /**
     * Update book status
     */
    public function updateBookStatus(Book $book, $status)
    {
        $validStatuses = ['available', 'unavailable', 'maintenance', 'lost'];
        if (!in_array($status, $validStatuses)) {
            throw new \Exception('Invalid status provided.');
        }

        $book->update(['availability_status' => $status]);
        return $book;
    }

    /**
     * Get book details with related data
     */
    public function getBookDetails(Book $book, $userId = null)
    {
        $book->load(['categories', 'borrowRecords' => function($query) {
            $query->with('user')->orderBy('borrowed_date', 'desc')->limit(5);
        }]);

        // Add computed fields
        $book->borrow_count = BorrowRecord::where('book_id', $book->id)->count();

        if ($userId) {
            $book->is_borrowed_by_user = BorrowRecord::where('user_id', $userId)
                ->where('book_id', $book->id)
                ->where('status', 'borrowed')
                ->exists();
        }

        return $book;
    }

    /**
     * Search books
     */
    public function searchBooks($query, $limit = 10)
    {
        if (empty($query)) {
            return collect();
        }

        return Book::where('availability_status', 'available')
            ->where(function ($q) use ($query) {
                $q->where('title', 'LIKE', "%{$query}%")
                  ->orWhere('author', 'LIKE', "%{$query}%")
                  ->orWhere('isbn', 'LIKE', "%{$query}%");
            })
            ->limit($limit)
            ->get(['id', 'title', 'author', 'cover_photo']);
    }

    /**
     * Get recommended books for user
     */
    public function getRecommendedBooks($userId, $limit = 6)
    {
        return Book::where('availability_status', 'available')
            ->whereDoesntHave('borrowRecords', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->withCount('borrowRecords')
            ->orderByDesc('borrow_records_count')
            ->limit($limit)
            ->get();
    }

    /**
     * Get similar books
     */
    public function getSimilarBooks(Book $book, $limit = 4)
    {
        return Book::where('id', '!=', $book->id)
            ->where('availability_status', 'available')
            ->whereHas('categories', function ($query) use ($book) {
                $query->whereIn('name', $book->categories->pluck('name'));
            })
            ->limit($limit)
            ->get();
    }

    /**
     * Export books to CSV
     */
    public function exportBooksToCsv($books)
    {
        $csvData = [];
        $csvData[] = [
            'Title', 'Author', 'Publisher', 'ISBN', 'Category',
            'Course', 'Year Level', 'Language', 'Status', 'Created At'
        ];

        foreach ($books as $book) {
            $csvData[] = [
                $book->title,
                $book->author,
                $book->publisher,
                $book->isbn,
                $book->categories->pluck('name')->join(', '),
                $book->course,
                $book->year_level,
                $book->language,
                $book->availability_status,
                $book->created_at->format('Y-m-d H:i:s')
            ];
        }

        return $this->generateCsvResponse($csvData, 'books-' . date('Y-m-d') . '.csv');
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
