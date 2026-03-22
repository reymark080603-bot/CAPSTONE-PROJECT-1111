<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class FixLibrarianAccount extends Command
{
    protected $signature = 'librarian:fix {email}';
    protected $description = 'Fix librarian account by email';

    public function handle()
    {
        $email = $this->argument('email');

        // Find the user by email
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("No user found with email: {$email}");
            return 1;
        }

        // Update the user to be a staff member
        $user->role = 'staff';
        $user->email_verified_at = now();
        $user->save();

        $this->info("Updated user {$email} to staff role");
        
        // Now reset the password
        $password = 'librarian123';
        $user->password = Hash::make($password);
        $user->save();

        $this->info("Password has been reset to: {$password}");
        $this->info("You can now login with email: {$email} and password: {$password}");

        return 0;
    }
}
