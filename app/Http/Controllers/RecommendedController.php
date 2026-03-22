<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Book;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class RecommendedController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth:student');
        $this->middleware(function ($request, $next) {
            if (!Auth::guard('student')->check()) {
                if ($request->expectsJson()) {
                    return response()->json(['error' => 'Authentication required'], 401);
                }
                return redirect()->route('login');
            }

            // Check if user is a student
            if (!Auth::guard('student')->user()->isStudent()) {
                if ($request->expectsJson()) {
                    return response()->json(['error' => 'Student access required'], 403);
                }
                return redirect()->route('login');
            }

            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $user = Auth::guard('student')->user();

        // Base query: available books not yet borrowed by this user
        $baseQuery = Book::where('availability_status', 'available')
            ->whereDoesntHave('borrowRecords', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            });

        // Resolve user course (name and code), then build matching variants
        $courseVariants = collect();
        if ($user->course_id) {
            $course = \Illuminate\Support\Facades\DB::table('courses')->find($user->course_id);
            if ($course) {
                $courseVariants = collect([
                    $course->name ?? null,
                    $course->code ?? null,
                ])->filter()->map(fn ($v) => trim((string) $v))->unique()->values();
            }
        }

        // Course-related picks first
        $courseRelatedBooks = collect();
        if ($courseVariants->isNotEmpty()) {
            $courseRelatedBooks = (clone $baseQuery)
                ->where(function ($query) use ($courseVariants) {
                    foreach ($courseVariants as $variant) {
                        $query->orWhere('course', $variant)
                              ->orWhere('course', 'LIKE', '%' . $variant . '%');
                    }
                })
                ->withCount('borrowRecords')
                ->orderByDesc('borrow_records_count')
                ->orderByDesc('created_at')
                ->limit(3)
                ->get();
        }

        // Popular picks next, excluding already selected course-related books
        $popularBooks = (clone $baseQuery)
            ->whereNotIn('id', $courseRelatedBooks->pluck('id'))
            ->withCount('borrowRecords')
            ->orderByDesc('borrow_records_count')
            ->orderByDesc('created_at')
            ->limit(3)
            ->get();

        $recommendedBooks = $courseRelatedBooks->concat($popularBooks)->take(6);

        return response()->json([
            'courseRelated' => $courseRelatedBooks->map(fn ($book) => $this->transformBook($book))->values(),
            'popular' => $popularBooks->map(fn ($book) => $this->transformBook($book))->values(),
            'recommended' => $recommendedBooks->map(fn ($book) => $this->transformBook($book))->values(),
        ]);
    }

    private function transformBook(Book $book): array
    {
        return [
            'id' => $book->id,
            'title' => $book->title,
            'author' => $book->author,
            'category' => $book->category,
            'cover_photo' => $book->display_cover_url,
            'borrow_records_count' => $book->borrow_records_count ?? 0,
        ];
    }
}
