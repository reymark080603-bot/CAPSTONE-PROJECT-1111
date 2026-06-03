<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Book;
use App\Models\BorrowRecord;
use App\Models\Author;
use App\Models\Category;
use App\Models\Publisher;
use App\Models\Role;
use App\Models\Course;
use App\Models\YearLevel;
use App\Models\Gender;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StressTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting stress test database seeding...');
        $startTime = microtime(true);

        // Fetch lookup table values
        $studentRoleId = DB::table('roles')->where('name', 'student')->value('id');
        if (!$studentRoleId) {
            $this->command->error('Student role not found. Please run php artisan migrate first.');
            return;
        }

        $courseIds = DB::table('courses')->pluck('id')->toArray();
        $yearLevelIds = DB::table('year_levels')->pluck('id')->toArray();
        $genderIds = DB::table('genders')->pluck('id')->toArray();

        if (empty($courseIds) || empty($yearLevelIds) || empty($genderIds)) {
            $this->command->error('Required lookup tables (courses, year_levels, genders) are empty. Run migrations first.');
            return;
        }

        // Clean up previous stress test data in a safe order
        $this->command->info('Cleaning up previous stress test records...');
        DB::transaction(function () {
            // Delete borrow records of stress students or stress books
            DB::table('borrow_records')->whereRaw("notes LIKE '%[STRESS-TEST]%'")->delete();
            
            // Delete stress students
            DB::table('users')->where('email', 'LIKE', 'stress.student.%@example.com')->delete();
            
            // Delete stress books relationships first
            $stressBookIds = DB::table('books')->where('isbn', 'LIKE', 'STRESS-%')->pluck('id')->toArray();
            if (!empty($stressBookIds)) {
                DB::table('author_book')->whereIn('book_id', $stressBookIds)->delete();
                DB::table('book_category')->whereIn('book_id', $stressBookIds)->delete();
                DB::table('books')->whereIn('id', $stressBookIds)->delete();
            }

            // Delete stress categories, authors, publishers
            DB::table('categories')->where('slug', 'LIKE', 'stress-%')->delete();
            DB::table('authors')->where('name', 'LIKE', '%(Stress-Test Author)%')->delete();
            DB::table('publishers')->where('name', 'LIKE', '%(Stress-Test Publisher)%')->delete();
        });
        $this->command->info('Cleanup completed.');

        // Wrap data generation in a transaction to make SQLite run in < 2 seconds
        DB::transaction(function () use ($studentRoleId, $courseIds, $yearLevelIds, $genderIds) {
            
            // 1. Generate Categories
            $this->command->info('Generating 30 categories...');
            $categories = [];
            for ($i = 1; $i <= 30; $i++) {
                $name = "Category Stress " . $i;
                $slug = "stress-category-" . $i;
                $categories[] = Category::create([
                    'name' => $name,
                    'slug' => $slug,
                ]);
            }

            // 2. Generate Authors
            $this->command->info('Generating 50 authors...');
            $authors = [];
            $firstNames = ['John', 'Jane', 'Michael', 'Emily', 'David', 'Sarah', 'James', 'Jessica', 'Robert', 'Karen'];
            $lastNames = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Miller', 'Davis', 'Garcia', 'Rodriguez', 'Wilson'];
            for ($i = 1; $i <= 50; $i++) {
                $name = $firstNames[array_rand($firstNames)] . ' ' . $lastNames[array_rand($lastNames)] . ' ' . $i . ' (Stress-Test Author)';
                $authors[] = Author::create(['name' => $name]);
            }

            // 3. Generate Publishers
            $this->command->info('Generating 20 publishers...');
            $publishers = [];
            $publisherTypes = ['Press', 'Books', 'Publishing Group', 'Media', 'House', 'Publications'];
            for ($i = 1; $i <= 20; $i++) {
                $name = $lastNames[array_rand($lastNames)] . ' ' . $publisherTypes[array_rand($publisherTypes)] . ' ' . $i . ' (Stress-Test Publisher)';
                $publishers[] = Publisher::create(['name' => $name]);
            }

            // 4. Generate Books
            $this->command->info('Generating 2,000 books...');
            $courses = ['CS', 'IT', 'ENG', 'BA', 'ACC', 'ED', 'NUR'];
            $yearLevels = ['1st Year', '2nd Year', '3rd Year', '4th Year'];
            $languages = ['English', 'Filipino', 'Spanish'];
            
            $booksData = [];
            $bookIds = [];
            
            // Pre-calculate randomized books
            for ($i = 1; $i <= 2000; $i++) {
                $publisher = $publishers[array_rand($publishers)];
                $authorObj = $authors[array_rand($authors)];
                $categoryObj = $categories[array_rand($categories)];
                
                $bookTitle = "Stress Test Book " . $i;
                $isbn = "STRESS-" . str_pad($i, 7, '0', STR_PAD_LEFT);
                
                $book = Book::create([
                    'title' => $bookTitle,
                    'isbn' => $isbn,
                    'author' => $authorObj->name, // fill text col
                    'category' => $categoryObj->name, // fill text col
                    'publisher_id' => $publisher->id,
                    'publisher' => $publisher->name, // fill text col
                    'description' => "This is a stress test book number " . $i . ". It is seeded automatically to test system load and query performance under stress.",
                    'published_year' => rand(2010, 2026),
                    'availability_status' => 'available',
                    'course' => $courses[array_rand($courses)],
                    'year_level' => $yearLevels[array_rand($yearLevels)],
                    'rating' => number_format(rand(30, 50) / 10, 1),
                    'copies_total' => 5,
                    'copies_available' => 5,
                    'language' => $languages[array_rand($languages)],
                    'resource_type' => 'Book',
                ]);

                // Sync pivot tables
                $book->authors()->attach($authorObj->id);
                // Attach a second optional author
                if (rand(0, 1) === 1) {
                    $secondAuthor = $authors[array_rand($authors)];
                    if ($secondAuthor->id !== $authorObj->id) {
                        $book->authors()->attach($secondAuthor->id);
                    }
                }

                $book->categories()->attach($categoryObj->id);
                // Attach a second optional category
                if (rand(0, 1) === 1) {
                    $secondCategory = $categories[array_rand($categories)];
                    if ($secondCategory->id !== $categoryObj->id) {
                        $book->categories()->attach($secondCategory->id);
                    }
                }

                $bookIds[] = $book->id;
            }

            // 5. Generate Students
            $this->command->info('Generating 500 students...');
            $studentIds = [];
            $hashedPassword = Hash::make('Student123'); // pre-hash to avoid performance hit in loop

            for ($i = 1; $i <= 500; $i++) {
                $firstName = $firstNames[array_rand($firstNames)];
                $lastName = $lastNames[array_rand($lastNames)];
                $libraryId = "STRESS-LIB-" . str_pad($i, 4, '0', STR_PAD_LEFT);
                $email = "stress.student." . $i . "@example.com";
                
                $user = User::create([
                    'name' => $firstName . ' ' . $lastName,
                    'firstname' => $firstName,
                    'lastname' => $lastName,
                    'email' => $email,
                    'library_id' => $libraryId,
                    'password' => $hashedPassword,
                    'role_id' => $studentRoleId,
                    'course_id' => $courseIds[array_rand($courseIds)],
                    'year_level_id' => $yearLevelIds[array_rand($yearLevelIds)],
                    'gender_id' => $genderIds[array_rand($genderIds)],
                    'email_verified_at' => now(),
                ]);

                $studentIds[] = $user->id;
            }

            // 6. Generate Borrow Records
            $this->command->info('Generating 5,000 borrow records...');
            $statuses = ['returned', 'borrowed'];
            
            for ($i = 1; $i <= 5000; $i++) {
                $userId = $studentIds[array_rand($studentIds)];
                $bookId = $bookIds[array_rand($bookIds)];
                $status = $statuses[rand(0, 4) === 0 ? 1 : 0]; // 80% returned, 20% active borrow

                $borrowedDate = Carbon::now()->subDays(rand(1, 180));
                $dueDate = (clone $borrowedDate)->addDays(14);
                
                $returnedDate = null;
                if ($status === 'returned') {
                    // returned within -3 to +5 days of due date
                    $returnedDate = (clone $dueDate)->addDays(rand(-10, 5));
                    if ($returnedDate->isAfter(Carbon::now())) {
                        $returnedDate = Carbon::now();
                    }
                }

                BorrowRecord::create([
                    'user_id' => $userId,
                    'book_id' => $bookId,
                    'borrowed_date' => $borrowedDate,
                    'due_date' => $dueDate,
                    'returned_date' => $returnedDate,
                    'status' => $status,
                    'notes' => '[STRESS-TEST] Automated stress test record #' . $i,
                    'renewal_count' => rand(0, 2),
                ]);

                // If currently borrowed, decrement book copies
                if ($status === 'borrowed') {
                    $book = Book::find($bookId);
                    if ($book && $book->copies_available > 0) {
                        $book->decrement('copies_available');
                        if ($book->copies_available <= 0) {
                            $book->update(['availability_status' => 'borrowed']);
                        }
                    }
                }
            }
        });

        $duration = number_format(microtime(true) - $startTime, 2);
        $this->command->info("Seeding completed successfully in {$duration} seconds!");
        $this->command->info("Added: 30 categories, 50 authors, 20 publishers, 2,000 books, 500 students, and 5,000 borrow records.");
    }
}
