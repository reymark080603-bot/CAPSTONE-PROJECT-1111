<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Book;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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

        $recommendedResources = $this->buildCourseResourceQuery($user)
            ->whereDoesntHave('borrowRecords', function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->where('status', 'borrowed');
            })
            ->withCount('borrowRecords')
            ->orderByDesc('borrow_records_count')
            ->orderByDesc('created_at')
            ->limit(6)
            ->get();

        return response()->json([
            'courseRelated' => $recommendedResources->map(fn ($book) => $this->transformBook($book))->values(),
            'popular' => collect(),
            'recommended' => $recommendedResources->map(fn ($book) => $this->transformBook($book))->values(),
        ]);
    }

    private function buildCourseResourceQuery($user)
    {
        $courseVariants = $this->getUserCourseVariants($user);

        $query = Book::where('availability_status', 'available');

        if (empty($courseVariants)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function ($courseQuery) use ($courseVariants) {
            foreach ($courseVariants as $variant) {
                $courseQuery->orWhere('course', $variant)
                    ->orWhere('program', $variant)
                    ->orWhere('course', 'LIKE', '%' . $variant . '%')
                    ->orWhere('program', 'LIKE', '%' . $variant . '%');
            }
        });
    }

    private function getUserCourseVariants($user): array
    {
        $variants = collect([
            $user->course_name ?? null,
            $user->course ?? null,
        ]);

        if ($user->course_id) {
            $course = DB::table('courses')->find($user->course_id);
            if ($course) {
                $variants = $variants->merge([
                    $course->name ?? null,
                    $course->code ?? null,
                ]);
            }
        }

        $courseMappings = [
            'BSIT' => ['Information Technology', 'BS Information Technology'],
            'BSN' => ['Nursing', 'BS Nursing'],
            'BSNURSING' => ['Nursing', 'BS Nursing'],
            'NURSING' => ['BSN', 'BS Nursing'],
            'BSHM' => ['Hospitality Management', 'BS Hospitality Management'],
            'HM' => ['BSHM', 'Hospitality Management'],
            'BSED' => ['Education', 'BS Education'],
            'EDUCATION' => ['BSED', 'BS Education'],
            'BSE' => ['Entrepreneurship', 'BS Entrepreneurship'],
            'BSENTREP' => ['Entrepreneurship', 'BS Entrepreneurship'],
            'ENTREP' => ['BS Entrepreneurship', 'Entrepreneurship'],
            'BSBM' => ['Business Management', 'BS Business Management'],
            'BUSINESS MANAGEMENT' => ['BSBM', 'BS Business Management'],
            'BSTOURISM' => ['Tourism', 'BS Tourism'],
            'TOURISM' => ['BSTourism', 'BS Tourism'],
        ];

        $variants = $variants->flatMap(function ($value) use ($courseMappings) {
            $value = trim((string) $value);
            if ($value === '') {
                return [];
            }

            $normalized = strtoupper(str_replace(['.', '-', '_'], '', $value));

            return array_merge([$value], $courseMappings[$normalized] ?? []);
        });

        return $variants
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique(fn ($value) => strtolower($value))
            ->values()
            ->all();
    }

    private function transformBook(Book $book): array
    {
        return [
            'id' => $book->id,
            'title' => $book->title,
            'author' => $book->author,
            'category' => $book->category,
            'cover_photo' => $book->display_cover_url,
            'resource_type' => $book->resource_type ?: 'book',
            'course' => $book->course ?: $book->program,
            'borrow_days' => $book->borrow_days ?? 5,
            'borrow_records_count' => $book->borrow_records_count ?? 0,
        ];
    }
}
