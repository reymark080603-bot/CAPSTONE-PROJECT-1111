<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Models\Book;
use App\Models\Category;
use App\Models\Author;
use App\Models\Publisher;
use App\Models\User;
use App\Models\BorrowRecord;
use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;

class LibrarianBookController extends Controller
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
     * Display a listing of books
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Get filter parameters
        $search = $request->get('search');
        $category = $request->get('category');
        $status = $request->get('status');
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        // Build query
        $query = Book::query();

        // Apply filters
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%")
                  ->orWhere('author', 'LIKE', "%{$search}%")
                  ->orWhere('isbn', 'LIKE', "%{$search}%");
            });
        }

        if ($category) {
            $query->whereHas('categories', function ($q) use ($category) {
                $q->where('name', $category);
            });
        }

        if ($status) {
            $query->where('availability_status', $status);
        }

        // Apply sorting
        $query->orderBy($sortBy, $sortOrder);

        // Paginate results
        $books = $query->paginate(15);

        // Get filter options
        $categories = Category::orderBy('name')->get();
        $statuses = Book::select('availability_status')
            ->distinct()
            ->pluck('availability_status')
            ->filter()
            ->values();

        return view('librarian.books.index', compact(
            'user', 'books', 'categories', 'statuses',
            'search', 'category', 'status', 'sortBy', 'sortOrder'
        ));
    }

    /**
     * Show the form for creating a new book
     */
    public function create()
    {
        $user = Auth::user();

        // Get categories for dropdown
        $categories = Category::orderBy('name')->get();

        return view('librarian.books.create', compact('user', 'categories'));
    }

    /**
     * Store a newly created book
     */
    public function store(StoreBookRequest $request)
    {
        try {
            DB::beginTransaction();

            // Handle cover photo upload
            $coverPhotoPath = null;
            if ($request->hasFile('cover_photo')) {
                $coverPhotoPath = $request->file('cover_photo')->store('covers', 'public');
            }

            // Handle ebook file upload
            $ebookFilePath = null;
            if ($request->hasFile('ebook_file')) {
                $ebookFilePath = $request->file('ebook_file')->store('ebooks', 'public');
            }

            // Create the book
            $book = Book::create([
                'title' => $request->title,
                'author' => $request->author,
                'publisher' => $request->publisher,
                'published_year' => $request->published_year,
                'isbn' => $request->isbn,
                'language' => $request->language ?: 'English',
                'category' => $request->category,
                'subcategory' => $request->subcategory,
                'resource_type' => $request->resource_type ?: 'book',
                'course' => $request->course,
                'year_level' => $request->year_level,
                'description' => $request->description,
                'cover_photo' => $coverPhotoPath,
                'ebook_file' => $ebookFilePath,
                'availability_status' => 'available',
                'added_by' => Auth::id(),
            ]);

            // Handle category association
            if ($request->category) {
                $category = Category::firstOrCreate(['name' => $request->category]);
                $book->categories()->attach($category->id);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Book created successfully!',
                'book' => $book
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            // Clean up uploaded files if book creation failed
            if (isset($coverPhotoPath) && Storage::disk('public')->exists($coverPhotoPath)) {
                Storage::disk('public')->delete($coverPhotoPath);
            }
            if (isset($ebookFilePath) && Storage::disk('public')->exists($ebookFilePath)) {
                Storage::disk('public')->delete($ebookFilePath);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to create book. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Display the specified book
     */
    public function show(Book $book)
    {
        $user = Auth::user();

        // Load relationships
        $book->load(['categories', 'borrowRecords.user']);

        // Get borrowing history
        $borrowHistory = $book->borrowRecords()
            ->with('user')
            ->orderBy('borrowed_date', 'desc')
            ->get();

        return view('librarian.books.show', compact('user', 'book', 'borrowHistory'));
    }

    /**
     * Show the form for editing the specified book
     */
    public function edit(Book $book)
    {
        $user = Auth::user();
        $categories = Category::orderBy('name')->get();

        return view('librarian.books.edit', compact('user', 'book', 'categories'));
    }

    /**
     * Update the specified book
     */
    public function update(UpdateBookRequest $request, Book $book)
    {
        try {
            DB::beginTransaction();

            // Prepare update data
            $updateData = [
                'title' => $request->title,
                'author' => $request->author,
                'publisher' => $request->publisher,
                'published_year' => $request->published_year,
                'isbn' => $request->isbn,
                'language' => $request->language ?: 'English',
                'category' => $request->category,
                'subcategory' => $request->subcategory,
                'resource_type' => $request->resource_type ?: ($book->resource_type ?: 'book'),
                'course' => $request->course,
                'year_level' => $request->year_level,
                'description' => $request->description,
            ];

            // Handle cover photo upload
            if ($request->hasFile('cover_photo')) {
                // Delete old cover photo
                if ($book->cover_photo && Storage::disk('public')->exists($book->cover_photo)) {
                    Storage::disk('public')->delete($book->cover_photo);
                }
                $updateData['cover_photo'] = $request->file('cover_photo')->store('covers', 'public');
            }

            // Handle ebook file upload
            if ($request->hasFile('ebook_file')) {
                // Delete old ebook file
                if ($book->ebook_file && Storage::disk('public')->exists($book->ebook_file)) {
                    Storage::disk('public')->delete($book->ebook_file);
                }
                $updateData['ebook_file'] = $request->file('ebook_file')->store('ebooks', 'public');
            }

            // Update book data
            $book->update($updateData);

            // Update category association
            if ($request->category) {
                $category = Category::firstOrCreate(['name' => $request->category]);
                $book->categories()->sync([$category->id]);
            } else {
                $book->categories()->detach();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Book updated successfully!',
                'book' => $book
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update book. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Remove the specified book
     */
    public function destroy(Book $book)
    {
        try {
            // Check if book is currently borrowed
            $activeBorrows = BorrowRecord::where('book_id', $book->id)
                ->where('status', 'borrowed')
                ->count();

            if ($activeBorrows > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete book that is currently borrowed by students.'
                ], 400);
            }

            // Delete associated files
            if ($book->cover_photo && Storage::disk('public')->exists($book->cover_photo)) {
                Storage::disk('public')->delete($book->cover_photo);
            }
            if ($book->ebook_file && Storage::disk('public')->exists($book->ebook_file)) {
                Storage::disk('public')->delete($book->ebook_file);
            }

            // Delete the book
            $book->delete();

            return response()->json([
                'success' => true,
                'message' => 'Book deleted successfully!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete book. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Bulk delete books
     */
    public function bulkDelete(Request $request)
    {
        $bookIds = $request->get('book_ids', []);

        if (empty($bookIds)) {
            return response()->json([
                'success' => false,
                'message' => 'No books selected for deletion.'
            ], 400);
        }

        try {
            DB::beginTransaction();

            $deletedCount = 0;
            $failedBooks = [];

            foreach ($bookIds as $bookId) {
                $book = Book::find($bookId);
                if (!$book) continue;

                // Check if book is currently borrowed
                $activeBorrows = BorrowRecord::where('book_id', $book->id)
                    ->where('status', 'borrowed')
                    ->count();

                if ($activeBorrows > 0) {
                    $failedBooks[] = $book->title;
                    continue;
                }

                // Delete associated files
                if ($book->cover_photo && Storage::disk('public')->exists($book->cover_photo)) {
                    Storage::disk('public')->delete($book->cover_photo);
                }
                if ($book->ebook_file && Storage::disk('public')->exists($book->ebook_file)) {
                    Storage::disk('public')->delete($book->ebook_file);
                }

                $book->delete();
                $deletedCount++;
            }

            DB::commit();

            $message = "Successfully deleted {$deletedCount} book(s).";
            if (!empty($failedBooks)) {
                $message .= " Could not delete: " . implode(', ', $failedBooks) . " (currently borrowed).";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'deleted_count' => $deletedCount,
                'failed_books' => $failedBooks
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete books. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Export books data
     */
    public function export(Request $request)
    {
        $format = $request->get('format', 'csv');

        // Get filtered books
        $query = Book::with('categories');

        // Apply same filters as index
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'LIKE', "%{$request->search}%")
                  ->orWhere('author', 'LIKE', "%{$request->search}%")
                  ->orWhere('isbn', 'LIKE', "%{$request->search}%");
            });
        }

        if ($request->category) {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->where('name', $request->category);
            });
        }

        if ($request->status) {
            $query->where('availability_status', $request->status);
        }

        $books = $query->get();

        if ($format === 'csv') {
            return $this->exportBooksCsv($books);
        }

        return response()->json(['error' => 'Unsupported export format'], 400);
    }

    /**
     * Get books data via API
     */
    public function api(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $search = $request->get('search');
        $category = $request->get('category');
        $status = $request->get('status');
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        // Build query
        $query = Book::with('categories');

        // Apply filters
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%")
                  ->orWhere('author', 'LIKE', "%{$search}%")
                  ->orWhere('isbn', 'LIKE', "%{$search}%");
            });
        }

        if ($category) {
            $query->whereHas('categories', function ($q) use ($category) {
                $q->where('name', $category);
            });
        }

        if ($status) {
            $query->where('availability_status', $status);
        }

        // Apply sorting
        $query->orderBy($sortBy, $sortOrder);

        // Paginate results
        $books = $query->paginate($perPage);

        return response()->json([
            'books' => $books
        ]);
    }

    /**
     * Get book details via API
     */
    public function getDetails(Book $book)
    {
        $book->load(['categories', 'borrowRecords' => function($query) {
            $query->with('user')->orderBy('borrowed_date', 'desc')->limit(10);
        }]);

        return response()->json([
            'book' => $book
        ]);
    }

    /**
     * Update book status
     */
    public function updateStatus(Request $request, Book $book)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:available,unavailable,maintenance,lost'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $book->update([
                'availability_status' => $request->status
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Book status updated successfully!',
                'book' => $book
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update book status.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Export books to CSV
     */
    private function exportBooksCsv($books)
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

        $handle = fopen('php://temp', 'r+');

        foreach ($csvData as $row) {
            fputcsv($handle, $row);
        }

        rewind($handle);
        $csvContent = stream_get_contents($handle);
        fclose($handle);

        return response($csvContent)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="books-' . date('Y-m-d') . '.csv"');
    }
}
