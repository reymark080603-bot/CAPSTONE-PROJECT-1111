<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Book;
use App\Models\Category;
use App\Models\BorrowRecord;

class BookController extends Controller
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
     * Display books listing page
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Get filter parameters
        $search = $request->get('search');
        $category = $request->get('category');
        $course = $request->get('course');
        $yearLevel = $request->get('year_level');
        $availability = $request->get('availability', 'all');
        $sortBy = $request->get('sort_by', 'title');
        $sortOrder = $request->get('sort_order', 'asc');

        // Build query for books
        $query = Book::with('categories');

        // Apply search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%")
                  ->orWhere('author', 'LIKE', "%{$search}%")
                  ->orWhere('isbn', 'LIKE', "%{$search}%");
            });
        }

        // Apply category filter
        if ($category) {
            $query->whereHas('categories', function ($q) use ($category) {
                $q->where('name', $category);
            });
        }

        // Apply course filter
        if ($course) {
            $query->where('course', $course);
        }

        // Apply year level filter
        if ($yearLevel) {
            $query->where('year_level', $yearLevel);
        }

        // Apply availability filter
        if ($availability === 'available') {
            $query->where('availability_status', 'available');
        } elseif ($availability === 'borrowed') {
            $query->where('availability_status', 'borrowed');
        }

        // Apply sorting
        $query->orderBy($sortBy, $sortOrder);

        // Get all books (no pagination for main page)
        $books = $query->get();

        // Add computed fields for each book
        $books->transform(function ($book) use ($user) {
            $book->is_borrowed_by_user = BorrowRecord::where('user_id', $user->id)
                ->where('book_id', $book->id)
                ->where('status', 'borrowed')
                ->exists();

            $book->borrow_count = BorrowRecord::where('book_id', $book->id)->count();
            
            return $book;
        });

        // Get recommended books based on user's course and year level
        $recommendedBooks = collect([]);
        if ($user && $user->course && $user->year) {
            $recommendedBooks = Book::with('categories')
                ->where('course', $user->course)
                ->where('year_level', $user->year)
                ->where('availability_status', 'available')
                ->orderBy('title', 'asc')
                ->take(6) // Limit to 6 books for the recommended section
                ->get();
            
            // Add computed fields to recommended books
            $recommendedBooks->transform(function ($book) use ($user) {
                $book->is_borrowed_by_user = BorrowRecord::where('user_id', $user->id)
                    ->where('book_id', $book->id)
                    ->where('status', 'borrowed')
                    ->exists();

                $book->borrow_count = BorrowRecord::where('book_id', $book->id)->count();
                
                return $book;
            });
        }

        return view('dashboard.books', compact(
            'user', 'books', 'recommendedBooks', 'search', 'category', 'course', 'yearLevel',
            'availability', 'sortBy', 'sortOrder'
        ));
    }

    /**
     * Get books data via API
     */
    public function api(Request $request)
    {
        $user = Auth::user();
        $perPage = $request->get('per_page', 12);

        // Get filter parameters
        $search = $request->get('search');
        $category = $request->get('category');
        $course = $request->get('course');
        $yearLevel = $request->get('year_level');
        $availability = $request->get('availability', 'all');
        $sortBy = $request->get('sort_by', 'title');
        $sortOrder = $request->get('sort_order', 'asc');

        // Build query
        $query = Book::with('categories');

        // Apply search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%")
                  ->orWhere('author', 'LIKE', "%{$search}%")
                  ->orWhere('isbn', 'LIKE', "%{$search}%");
            });
        }

        // Apply category filter
        if ($category) {
            $query->whereHas('categories', function ($q) use ($category) {
                $q->where('name', $category);
            });
        }

        // Apply course filter
        if ($course) {
            $query->where('course', $course);
        }

        // Apply year level filter
        if ($yearLevel) {
            $query->where('year_level', $yearLevel);
        }

        // Apply availability filter
        if ($availability === 'available') {
            $query->where('availability_status', 'available');
        } elseif ($availability === 'borrowed') {
            $query->where('availability_status', 'borrowed');
        }

        // Apply sorting
        $query->orderBy($sortBy, $sortOrder);

        // Paginate results
        $books = $query->paginate($perPage);

        // Add computed fields for each book
        $books->getCollection()->transform(function ($book) use ($user) {
            $book->is_borrowed_by_user = BorrowRecord::where('user_id', $user->id)
                ->where('book_id', $book->id)
                ->where('status', 'borrowed')
                ->exists();

            $book->borrow_count = BorrowRecord::where('book_id', $book->id)->count();

            return $book;
        });

        return response()->json([
            'books' => $books
        ]);
    }

    /**
     * Show book details
     */
    public function show(Book $book)
    {
        $user = Auth::user();

        // Load relationships
        $book->load(['categories', 'borrowRecords' => function($query) {
            $query->with('user')->orderBy('borrowed_date', 'desc')->limit(5);
        }]);

        // Check if user has borrowed this book
        $isBorrowedByUser = BorrowRecord::where('user_id', $user->id)
            ->where('book_id', $book->id)
            ->where('status', 'borrowed')
            ->exists();

        // Get borrow count
        $borrowCount = BorrowRecord::where('book_id', $book->id)->count();

        // Get similar books (same category)
        $similarBooks = Book::where('id', '!=', $book->id)
            ->where('availability_status', 'available')
            ->whereHas('categories', function ($query) use ($book) {
                $query->whereIn('name', $book->categories->pluck('name'));
            })
            ->limit(4)
            ->get();

        return view('dashboard.book-details', compact(
            'user', 'book', 'isBorrowedByUser',
            'borrowCount', 'similarBooks'
        ));
    }

    /**
     * Get book details via API
     */
    public function getDetails(Book $book)
    {
        $user = Auth::user();

        $book->load(['categories', 'borrowRecords' => function($query) {
            $query->with('user')->orderBy('borrowed_date', 'desc')->limit(5);
        }]);

        // Add computed fields
        $book->is_borrowed_by_user = BorrowRecord::where('user_id', $user->id)
            ->where('book_id', $book->id)
            ->where('status', 'borrowed')
            ->exists();

        $book->borrow_count = BorrowRecord::where('book_id', $book->id)->count();

        return response()->json([
            'book' => $book
        ]);
    }

    /**
     * Read book (show ebook content)
     */
    public function read(Book $book)
    {
        $user = Auth::guard('student')->user() ?: Auth::user();

        if (!$user) {
            return redirect()->route('login')->with('error', 'Please login to read books.');
        }

        // Check if book has any readable digital content
        if (!$book->hasReadableContent()) {
            return redirect()->back()->with('error', 'This book does not have a digital version available.');
        }

        // Check if user has permission to read this book
        $hasPermission = BorrowRecord::where('user_id', $user->id)
            ->where('book_id', $book->id)
            ->where(function ($query) {
                $query->where('status', 'borrowed')
                      ->orWhere('status', 'returned');
            })
            ->exists();

        if (!$hasPermission && $book->availability_status !== 'available') {
            return redirect()->back()->with('error', 'You need to borrow this book first to read it.');
        }

        return view('dashboard.read-book', compact('user', 'book'));
    }

    /**
     * Search books
     */
    public function search(Request $request)
    {
        $query = $request->get('q', '');
        $limit = $request->get('limit', 10);

        if (empty($query)) {
            return response()->json(['books' => []]);
        }

        $books = Book::where('availability_status', 'available')
            ->where(function ($q) use ($query) {
                $q->where('title', 'LIKE', "%{$query}%")
                  ->orWhere('author', 'LIKE', "%{$query}%")
                  ->orWhere('isbn', 'LIKE', "%{$query}%");
            })
            ->limit($limit)
            ->get(['id', 'title', 'author', 'cover_photo']);

        return response()->json([
            'books' => $books
        ]);
    }

    /**
     * Get categories for filter
     */
    public function getCategories()
    {
        $categories = Category::orderBy('name')->get(['id', 'name']);

        return response()->json([
            'categories' => $categories
        ]);
    }

    /**
     * Get recommended books
     */
    public function getRecommendedBooks()
    {
        $books = Book::where('availability_status', 'available')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get(['id', 'title', 'author', 'cover_photo']);
        
        return response()->json($books);
    }

    /**
     * Get recent books
     */
    public function getRecentBooks()
    {
        $books = Book::where('availability_status', 'available')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get(['id', 'title', 'author', 'cover_photo']);
        
        return response()->json($books);
    }
}
