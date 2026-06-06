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
        $adminEmail = env('ADMIN_EMAIL', 'JHCSCLibrarian@gmail.com');
        $adminPassword = env('ADMIN_PASSWORD', 'JHCSCLib2026');
        $adminName = env('ADMIN_NAME', 'Shienalie S. Lubon');

        // The deployed admin account uses the librarian role because admin and librarian are the same user type.
        $librarianRole = DB::table('roles')->where('name', 'librarian')->first();
        if (!$librarianRole) {
            $this->command->error('Librarian role not found! Please run migrations first.');
            return;
        }

        $nameParts = explode(' ', trim($adminName), 2);

        // Create or update using DB to bypass Eloquent 'hashed' cast double-hashing
        $existing = DB::table('users')->where('email', $adminEmail)->first();
        if ($existing) {
            DB::table('users')->where('email', $adminEmail)->update([
                'name' => $adminName,
                'firstname' => $nameParts[0] ?? $adminName,
                'lastname' => $nameParts[1] ?? '',
                'password' => Hash::make($adminPassword),
                'role_id' => $librarianRole->id,
                'email_verified_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            DB::table('users')->insert([
                'name' => $adminName,
                'firstname' => $nameParts[0] ?? $adminName,
                'lastname' => $nameParts[1] ?? '',
                'email' => $adminEmail,
                'password' => Hash::make($adminPassword),
                'role_id' => $librarianRole->id,
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command->info('Admin/librarian account created successfully!');
        $this->command->info('Name: ' . $adminName);
        $this->command->info('Email: ' . $adminEmail);
        $this->command->info('Password: ' . $adminPassword);
        $this->command->info('Login URL: ' . config('app.url') . '/login');
    }
}
