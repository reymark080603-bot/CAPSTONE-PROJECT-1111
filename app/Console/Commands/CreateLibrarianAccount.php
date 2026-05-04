<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateLibrarianAccount extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'librarian:create {--name=} {--email=} {--password=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new librarian account with staff privileges';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== Knowly Library - Create Librarian Account ===');
        $this->newLine();

        // Get librarian details
        $name = $this->option('name') ?: $this->ask('Full name of the librarian');
        $email = $this->option('email') ?: $this->ask('Email address');
        $password = $this->option('password') ?: $this->secret('Password (minimum 8 characters)');

        // Validate input
        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ], [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            $this->error('Validation failed:');
            foreach ($validator->errors()->all() as $error) {
                $this->line("  • {$error}");
            }
            return 1;
        }

        // Split name into first and last name
        $nameParts = explode(' ', trim($name), 2);
        $firstname = $nameParts[0];
        $lastname = isset($nameParts[1]) ? $nameParts[1] : '';
        $role = Role::firstOrCreate(
            ['name' => 'librarian'],
            [
                'display_name' => 'Librarian',
                'description' => 'Library staff with admin privileges',
            ]
        );

        // Create librarian account
        try {
            $librarian = User::create([
                'name' => $name,
                'firstname' => $firstname,
                'lastname' => $lastname,
                'email' => $email,
                'password' => Hash::make($password),
                'role_id' => $role->id,
                'email_verified_at' => now(),
            ]);

            $this->newLine();
            $this->info('✅ Librarian account created successfully!');
            $this->newLine();
            $this->table(
                ['Field', 'Value'],
                [
                    ['ID', $librarian->id],
                    ['Name', $librarian->name],
                    ['Email', $librarian->email],
                    ['Role', 'Staff (Librarian)'],
                    ['Created', $librarian->created_at->format('Y-m-d H:i:s')],
                ]
            );
            
            $this->newLine();
            $this->info('The librarian can now login at:');
            $this->line(config('app.url') . '/login');
            $this->newLine();
            $this->info('Login credentials:');
            $this->line('📧 Email: ' . $email);
            $this->line('🔒 Password: [as provided]');
            
            return 0;
        } catch (\Exception $e) {
            $this->error('Failed to create librarian account:');
            $this->line($e->getMessage());
            return 1;
        }
    }
}
