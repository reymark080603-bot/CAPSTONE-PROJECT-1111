<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BorrowRecord;
use App\Models\Book;
use Carbon\Carbon;

class AutoReturnBooks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'books:auto-return {--dry-run : Show what would be returned without actually returning}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically return books that have reached their 5-day borrowing limit';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        
        $this->info('Checking for books due for automatic return...');
        
        // Find all borrow records that are overdue (past their due date)
        $overdueRecords = BorrowRecord::with(['book', 'user'])
            ->where('status', 'borrowed')
            ->where('due_date', '<=', now())
            ->get();
            
        if ($overdueRecords->isEmpty()) {
            $this->info('No books found that are due for return.');
            return 0;
        }
        
        $this->info("Found {$overdueRecords->count()} book(s) due for automatic return.");
        
        $returnedCount = 0;
        $errors = [];
        
        foreach ($overdueRecords as $record) {
            try {
                $book = $record->book;
                $user = $record->user;
                $daysOverdue = now()->diffInDays(Carbon::parse($record->due_date));
                
                $this->line("\n📚 Processing: '{$book->title}' borrowed by {$user->firstname} {$user->lastname}");
                $this->line("   📅 Due: {$record->due_date->format('M j, Y')} ({$daysOverdue} days ago)");
                
                if (!$isDryRun) {
                    // Update borrow record to returned with auto-return note
                    $record->update([
                        'returned_date' => now(),
                        'status' => 'returned',
                        'notes' => ($record->notes ? $record->notes . ' | ' : '') . 'Auto-returned after 5-day limit'
                    ]);

                    // Open access system - books remain available for others
                    // No need to update availability status or check reservations

                    $this->info("   ✅ Book automatically returned successfully");
                } else {
                    $this->line("   🔄 [DRY RUN] Would return this book");
                }
                
                $returnedCount++;
                
            } catch (\Exception $e) {
                $error = "Failed to return '{$record->book->title}': {$e->getMessage()}";
                $errors[] = $error;
                $this->error("   ❌ {$error}");
            }
        }
        
        // Summary
        $this->newLine();
        if ($isDryRun) {
            $this->info("📋 DRY RUN SUMMARY:");
            $this->info("   Books that would be returned: {$returnedCount}");
        } else {
            $this->info("📋 RETURN SUMMARY:");
            $this->info("   Books successfully returned: {$returnedCount}");
        }
        
        if (!empty($errors)) {
            $this->error("   Errors encountered: " . count($errors));
            foreach ($errors as $error) {
                $this->line("   - {$error}");
            }
        }
        
        $this->newLine();
        $this->info('✨ Auto-return process completed!');
        
        return 0;
    }
}
