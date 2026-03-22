<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class CheckLibrarianAccount extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'librarian:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check if librarian account exists and display details';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $librarian = User::where('role', 'staff')->first();

        if ($librarian) {
            $this->info('✅ Librarian account found!');
            $this->info('Name: ' . $librarian->name);
            $this->info('Email: ' . $librarian->email);
            $this->info('Role: ' . $librarian->role);
            $this->info('Created: ' . $librarian->created_at);
        } else {
            $this->error('❌ Librarian account not found!');
        }

        return 0;
    }
}
