<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class LibrarianSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get librarian role
        $librarianRole = DB::table('roles')->where('name', 'librarian')->first();
        if (!$librarianRole) {
            $this->command->error('Librarian role not found! Please run migrations first.');
            return;
        }

        // Delete all existing librarian accounts to ensure clean setup
        User::where('role_id', $librarianRole->id)->delete();

        // Create the new dedicated librarian account
        User::updateOrCreate(
            ['email' => 'mark123@gmail.com'],
            [
                'name' => 'Mark Talawan',
                'firstname' => 'Mark',
                'lastname' => 'Talawan',
                'password' => Hash::make('Mark87654321'),
                'role_id' => $librarianRole->id,
                'email_verified_at' => now(), // Auto-verify librarian account
            ]
        );

        $this->command->info('Librarian account created successfully!');
        $this->command->info('Name: Mark Talawan');
        $this->command->info('Email: mark123@gmail.com');
        $this->command->info('Password: Mark87654321');
        $this->command->info('All previous librarian accounts have been removed.');
    }
}