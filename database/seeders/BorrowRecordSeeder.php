<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\BorrowRecord;
use App\Models\User;
use App\Models\Book;
use Carbon\Carbon;

class BorrowRecordSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get first user (assuming it exists)
        $user = User::whereHas('role', function($q) {
            $q->where('name', 'student');
        })->first();
        if (!$user) {
            $this->command->info('No student user found. Please create a student user first.');
            return;
        }

        // Get some books
        $books = Book::take(10)->get();
        if ($books->isEmpty()) {
            $this->command->info('No books found. Please run BookSeeder first.');
            return;
        }

        $numBooks = $books->count();
        $borrowRecords = [];

        // Currently borrowed books (up to 3)
        for ($i = 0; $i < min(3, $numBooks); $i++) {
            $borrowRecords[] = [
                'user_id' => $user->id,
                'book_id' => $books[$i]->id,
                'borrowed_date' => Carbon::now()->subDays(5 + $i * 5),
                'due_date' => Carbon::now()->addDays(14 - $i * 2),
                'status' => 'borrowed',
                'renewal_count' => $i % 2
            ];
        }

        // Returned books (up to 3 more)
        for ($i = 3; $i < min(6, $numBooks); $i++) {
            $borrowRecords[] = [
                'user_id' => $user->id,
                'book_id' => $books[$i]->id,
                'borrowed_date' => Carbon::now()->subDays(30 + ($i - 3) * 15),
                'due_date' => Carbon::now()->subDays(16 + ($i - 3) * 15),
                'returned_date' => Carbon::now()->subDays(18 + ($i - 3) * 15),
                'status' => 'returned',
                'renewal_count' => $i % 2
            ];
        }

        foreach ($borrowRecords as $record) {
            BorrowRecord::create($record);
        }

        // Update book statuses for borrowed books (first 3 or fewer)
        for ($i = 0; $i < min(3, $numBooks); $i++) {
            $books[$i]->update(['availability_status' => 'borrowed']);
        }

        $this->command->info('Sample borrow records created successfully!');
    }
}
