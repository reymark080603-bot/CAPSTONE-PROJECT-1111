<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class ResetLibrarianPassword extends Command
{
    protected $signature = 'librarian:reset-password {email} {password?}';
    protected $description = 'Reset the password for a librarian account';

    public function handle()
    {
        $email = $this->argument('email');
        $password = $this->argument('password') ?? 'librarian123';

        // Find the user by email
        $user = User::where('email', $email)
            ->whereHas('role', function ($query) {
                $query->whereIn('name', ['admin', 'librarian']);
            })
            ->first();

        if (!$user) {
            $this->error("No staff account found with email: {$email}");
            
            // Check if we should create a new librarian account
            if ($this->confirm('Would you like to create a new librarian account with these credentials?', true)) {
                $role = Role::firstOrCreate(
                    ['name' => 'librarian'],
                    [
                        'display_name' => 'Librarian',
                        'description' => 'Library staff with admin privileges',
                    ]
                );

                $user = User::create([
                    'name' => 'Librarian',
                    'email' => $email,
                    'password' => Hash::make($password),
                    'role_id' => $role->id,
                    'email_verified_at' => now(),
                ]);
                $this->info("Created new librarian account for {$email} with password: {$password}");
                return 0;
            }
            
            return 1;
        }

        // Update the password
        $user->password = Hash::make($password);
        $user->save();

        $this->info("Password has been reset for {$email}");
        $this->line("New password: {$password}");

        return 0;
    }
}
