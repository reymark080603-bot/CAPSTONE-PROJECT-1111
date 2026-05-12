<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Services\PdfCoverService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

/**
 * Controller for handling recently added books display and uploads.
 * Provides functionality for displaying books in grid and handling file uploads.
 */
class RecentBooksController extends Controller
{
    /**
     * PDF Cover Service instance
     */
    protected PdfCoverService $pdfCoverService;

    /**
     * Constructor
     */
    public function __construct(PdfCoverService $pdfCoverService)
    {
        $this->pdfCoverService = $pdfCoverService;
    }

    /**
     * Display recently added books in a responsive grid.
     * Accessible by students and librarians.
     *
     * @param Request $request
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // Get sorting option
        $sortBy = $request->get('sort', 'newest');
        
        // Build query for recent books
        $query = Book::query()
            ->with(['categories', 'borrowRecords'])
            ->where('availability_status', 'available');

        // Apply sorting
        switch ($sortBy) {
            case 'title':
                $query->orderBy('title', 'asc');
                break;
            case 'author':
                $query->orderBy('author', 'asc');
                break;
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'newest':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        // Get books with pagination
        $books = $query->paginate(12);

        // Get user's borrowed books (for borrow button state)
        $borrowedBookIds = [];
        if (Auth::check()) {
            $borrowedBookIds = DB::table('borrow_records')
                ->where('user_id', Auth::id())
                ->where('status', 'borrowed')
                ->pluck('book_id')
                ->toArray();
        }

        // Return view with data
        if ($request->expectsJson()) {
            return response()->json([
                'books' => $books,
                'borrowed_book_ids' => $borrowedBookIds
            ]);
        }

        return view('dashboard.recent-books', compact('books', 'borrowedBookIds', 'sortBy'));
    }

    /**
     * Get recent books as JSON for AJAX loading.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRecentBooks(Request $request)
    {
        $perPage = $request->get('per_page', 12);
        $sortBy = $request->get('sort', 'newest');

        $query = Book::query()
            ->with(['categories'])
            ->where('availability_status', 'available');

        // Apply sorting
        switch ($sortBy) {
            case 'title':
                $query->orderBy('title', 'asc');
                break;
            case 'author':
                $query->orderBy('author', 'asc');
                break;
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'newest':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        $books = $query->paginate($perPage);

        // Get borrowed book IDs for current user
        $borrowedBookIds = [];
        if (Auth::check()) {
            $borrowedBookIds = DB::table('borrow_records')
                ->where('user_id', Auth::id())
                ->where('status', 'borrowed')
                ->pluck('book_id')
                ->toArray();
        }

        return response()->json([
            'success' => true,
            'books' => $books->items(),
            'current_page' => $books->currentPage(),
            'last_page' => $books->lastPage(),
            'total' => $books->total(),
            'borrowed_book_ids' => $borrowedBookIds
        ]);
    }

    /**
     * Handle single book upload with PDF and cover image.
     * Librarian only.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function upload(Request $request)
    {
        // Validate request
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'published_year' => 'nullable|integer|min:1900|max:' . date('Y'),
            'program' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'pdf_file' => 'required|file|mimes:pdf|max:51200', // 50MB max
            'cover_image' => 'nullable|image|mimes:jpeg,png,gif,webp|max:2048', // 2MB max
        ], [
            'title.required' => 'Book title is required.',
            'author.required' => 'Author name is required.',
            'pdf_file.required' => 'PDF file is required.',
            'pdf_file.mimes' => 'Only PDF files are allowed.',
            'pdf_file.max' => 'PDF file must not exceed 50MB.',
            'cover_image.image' => 'Cover image must be a valid image file.',
            'cover_image.max' => 'Cover image must not exceed 2MB.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            $pdfFile = $request->file('pdf_file');
            $coverImage = $request->file('cover_image');

            // Generate unique filename for PDF
            $pdfFilename = time() . '_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $request->title) . '.pdf';
            $pdfPath = $pdfFile->storeAs('ebooks', $pdfFilename, 'public');

            // Handle cover image - use uploaded or generate from PDF
            $coverPath = null;

            if ($coverImage) {
                // Use uploaded cover image
                $coverFilename = time() . '_cover_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $request->title) . '.' . $coverImage->getClientOriginalExtension();
                if (env('CLOUDINARY_URL')) {
                    $result = Cloudinary::upload($coverImage->getRealPath(), [
                        'folder' => 'knowly/covers'
                    ]);
                    $coverPath = $result->getSecurePath();
                } else {
                    $coverPath = $coverImage->storeAs('covers', $coverFilename, 'public');
                }
            } else {
                // Try to generate cover from PDF first page
                $fullPdfPath = Storage::disk('public')->path($pdfPath);
                $generatedCover = $this->pdfCoverService->generateCover($fullPdfPath, $request->title);
                
                if ($generatedCover) {
                    $coverPath = $generatedCover;
                }
            }

            // If no cover available, use default
            $finalCoverPath = $coverPath ?? 'covers/default-book.png';

            // Create book record
            $book = Book::create([
                'title' => $request->title,
                'author' => $request->author,
                'published_year' => $request->published_year,
                'program' => $request->program,
                'description' => $request->description,
                'file_path' => $pdfPath,
                'cover_image' => $finalCoverPath,
                'cover_photo' => $finalCoverPath, // For compatibility
                'availability_status' => 'available',
                'language' => 'English',
            ]);

            DB::commit();

            Log::info('Book uploaded successfully', [
                'book_id' => $book->id,
                'title' => $book->title,
                'has_cover' => !empty($coverPath) && $coverPath !== 'covers/default-book.png'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Book uploaded successfully!',
                'book' => [
                    'id' => $book->id,
                    'title' => $book->title,
                    'author' => $book->author,
                    'cover_image' => $book->cover_image,
                    'file_path' => $book->file_path
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Book upload failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to upload book. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Generate thumbnail from PDF for an existing book.
     * 
     * @param Book $book
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateThumbnail(Book $book)
    {
        try {
            // Check if book has a PDF file
            if (empty($book->file_path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Book has no PDF file to generate thumbnail from.'
                ], 400);
            }

            // Get full PDF path
            $normalizedPath = ltrim((string) $book->file_path, '/');
            if (str_starts_with($normalizedPath, 'storage/')) {
                $normalizedPath = substr($normalizedPath, 8);
            }
            $pdfPath = Storage::disk('public')->path($normalizedPath);

            if (!file_exists($pdfPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'PDF file not found.'
                ], 404);
            }

            // Generate cover thumbnail
            $coverPath = $this->pdfCoverService->generateCover($pdfPath, $book->title);

            if ($coverPath) {
                // Update book record
                $book->update([
                    'cover_image' => $coverPath,
                    'cover_photo' => $coverPath
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Cover thumbnail generated successfully!',
                    'cover_image' => $coverPath
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate cover thumbnail. pdftoppm may not be available.'
            ], 500);

        } catch (\Exception $e) {
            Log::error('Thumbnail generation failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate thumbnail: ' . $e->getMessage()
            ], 500);
        }
    }
}

