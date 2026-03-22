<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class ViewLibrarianInfo extends Command
{
    protected $signature = 'librarian:info {email?} {--list : List all staff accounts}';
    protected $description = 'View or update librarian information';

    public function handle()
    {
        if ($this->option('list')) {
            $this->listStaffAccounts();
            return 0;
        }

        $email = $this->argument('email') ?? 'mark123@gmail.com';
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("No user found with email: {$email}");
            $this->listStaffAccounts();
            return 1;
        }

        $this->info("Librarian Information:");
        $this->line("Name: " . ($user->name ?? 'Not set'));
        $this->line("Email: " . $user->email);
        $this->line("Role: " . $user->role);
        $this->line("Account Status: " . ($user->email_verified_at ? 'Verified' : 'Not Verified'));
        
        // Display additional fields if they exist
        $fields = ['first_name', 'last_name', 'phone', 'address'];
        foreach ($fields as $field) {
            if (isset($user->$field)) {
                $this->line(ucfirst(str_replace('_', ' ', $field)) . ": " . $user->$field);
            }
        }
        
        $this->line("Last Login: " . ($user->last_login_at ?? 'Never'));
        $this->line("Created: " . $user->created_at);
        $this->line("Updated: " . $user->updated_at);

        // Check if we should update the information
        if ($this->confirm('Would you like to update this librarian\'s information?', false)) {
            $this->updateLibrarianInfo($user);
        }

        return 0;
    }

    protected function listStaffAccounts()
    {
        $staff = User::where('role', 'staff')->get(['id', 'name', 'email', 'created_at']);
        
        if ($staff->isEmpty()) {
            $this->info('No staff accounts found.');
            return;
        }

        $this->info('Staff Accounts:');
        $this->table(
            ['ID', 'Name', 'Email', 'Created At'],
            $staff->toArray()
        );
    }

    protected function updateLibrarianInfo($user)
    {
        $this->info("\nUpdate Librarian Information (press Enter to keep current value)");
        
        $name = $this->ask('Full Name', $user->name ?? '');
        $firstName = $this->ask('First Name', $user->first_name ?? '');
        $lastName = $this->ask('Last Name', $user->last_name ?? '');
        $phone = $this->ask('Phone Number', $user->phone ?? '');
        $address = $this->ask('Address', $user->address ?? '');
        
        // Update fields if they have values
        if ($name) $user->name = $name;
        if ($firstName) $user->first_name = $firstName;
        if ($lastName) $user->last_name = $lastName;
        if ($phone) $user->phone = $phone;
        if ($address) $user->address = $address;
        
        $user->save();
        
        $this->info("\nLibrarian information updated successfully!");
        $this->call('librarian:info', ['email' => $user->email]);
    }
}
