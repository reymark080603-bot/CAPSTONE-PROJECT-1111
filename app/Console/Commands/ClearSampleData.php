<?php

namespace App\Console\Commands;

use App\Models\Book;
use App\Models\BorrowRecord;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ClearSampleData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'library:clear-sample-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all sample/dummy data from the library system';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (!$this->confirm('This will remove all books, borrow records, and fines. Are you sure?')) {
            $this->info('Operation cancelled.');

            return self::SUCCESS;
        }

        $this->info('Clearing sample data...');

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        if (Schema::hasTable('fines')) {
            DB::table('fines')->truncate();
            $this->info('Cleared fines');
        }

        BorrowRecord::truncate();
        $this->info('Cleared borrow records');

        Book::truncate();
        $this->info('Cleared books');

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->info('All sample data has been cleared successfully!');
        $this->comment('The system is now ready for real book data to be uploaded by librarians.');

        return self::SUCCESS;
    }
}
