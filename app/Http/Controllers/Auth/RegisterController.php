<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Gender;
use App\Models\Role;
use App\Models\User;
use App\Models\YearLevel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class RegisterController extends Controller
{
    /**
     * Show the student registration form.
     */
    public function showRegistrationForm(): View
    {
        return view('Student.register');
    }

    /**
     * Handle the student registration request.
     */
    public function register(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'gender' => ['required', 'string', 'max:255'],
            'course' => ['required', 'string', 'max:255'],
            'year_level' => ['required', 'string', 'max:255'],
            'campus' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput($request->except('password', 'password_confirmation'));
        }

        try {
            $user = DB::transaction(function () use ($request) {
                $role = Role::firstOrCreate(
                    ['name' => 'student'],
                    [
                        'display_name' => 'Student',
                        'description' => 'Regular student user',
                    ]
                );

                $gender = Gender::firstOrCreate(
                    ['name' => $request->gender],
                    [
                        'abbreviation' => $this->genderAbbreviation($request->gender),
                    ]
                );

                $yearLevel = YearLevel::firstOrCreate(
                    ['level' => $request->year_level],
                    [
                        'numeric_level' => $this->extractNumericYearLevel($request->year_level),
                    ]
                );

                $normalizedCourse = trim($request->course);
                $course = Course::query()
                    ->where('name', $normalizedCourse)
                    ->orWhere('code', strtoupper($normalizedCourse))
                    ->first();

                if (!$course) {
                    $course = Course::create([
                        'name' => $normalizedCourse,
                        'code' => strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $normalizedCourse), 0, 10)) ?: null,
                    ]);
                }

                $nameParts = $this->splitFullName($request->name);
                $libraryId = $this->generateLibraryId();

                return User::create([
                    'name' => trim($request->name),
                    'firstname' => $nameParts['firstname'],
                    'mi' => $nameParts['mi'],
                    'lastname' => $nameParts['lastname'],
                    'gender' => $request->gender,
                    'gender_id' => $gender->id,
                    'library_id' => $libraryId,
                    'campus' => trim($request->campus),
                    'year_level_id' => $yearLevel->id,
                    'course_id' => $course->id,
                    'email' => trim($request->email),
                    'password' => Hash::make($request->password),
                    'role_id' => $role->id,
                    // The current student login flow blocks accounts with a null value here.
                    'email_verified_at' => now(),
                ]);
            });

            return redirect()->route('login')->with('status', "Account created successfully. Your Library ID is {$user->library_id}.");
        } catch (\Throwable $e) {
            Log::error('Student registration failed', [
                'email' => $request->email,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->withErrors(['email' => 'Registration failed. Please try again.'])
                ->withInput($request->except('password', 'password_confirmation'));
        }
    }

    private function splitFullName(string $fullName): array
    {
        $fullName = preg_replace('/\s+/', ' ', trim($fullName));
        $parts = array_values(array_filter(explode(' ', $fullName)));

        if (count($parts) === 1) {
            return [
                'firstname' => $parts[0],
                'mi' => null,
                'lastname' => $parts[0],
            ];
        }

        if (count($parts) === 2) {
            return [
                'firstname' => $parts[0],
                'mi' => null,
                'lastname' => $parts[1],
            ];
        }

        $middleToken = $parts[count($parts) - 2];
        $middleInitial = preg_replace('/[^A-Za-z]/', '', $middleToken);

        return [
            'firstname' => implode(' ', array_slice($parts, 0, -2)),
            'mi' => $middleInitial !== '' ? strtoupper(substr($middleInitial, 0, 1)) : null,
            'lastname' => $parts[count($parts) - 1],
        ];
    }

    private function generateLibraryId(): string
    {
        do {
            $libraryId = 'LIB' . now()->format('Y') . random_int(1000, 9999);
        } while (User::where('library_id', $libraryId)->exists());

        return $libraryId;
    }

    private function extractNumericYearLevel(string $yearLevel): int
    {
        if (preg_match('/(\d+)/', $yearLevel, $matches)) {
            return (int) $matches[1];
        }

        return 1;
    }

    private function genderAbbreviation(string $gender): ?string
    {
        return match (strtolower(trim($gender))) {
            'male' => 'M',
            'female' => 'F',
            default => null,
        };
    }
}
