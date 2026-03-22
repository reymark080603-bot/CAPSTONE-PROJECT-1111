<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CreateStudentAccount extends Command
{
    protected $signature = 'student:create {library_id} {email} {password} {fullname} {course?} {year?}';
    protected $description = 'Create or update a student account';

    public function handle()
    {
        $libraryId = $this->argument('library_id');
        $email = $this->argument('email');
        $password = $this->argument('password');
        $fullname = $this->argument('fullname');
        $course = $this->argument('course') ?? null;
        $year = $this->argument('year') ?? null;

        // Basic checks
        if (empty($libraryId) || empty($email) || empty($password) || empty($fullname)) {
            $this->error('library_id, email, password and fullname are required.');
            return 1;
        }

        // Try to find existing user by email or library_id
        $user = User::where('email', $email)->orWhere('library_id', $libraryId)->first();

        if ($user) {
            $this->info("Found existing user (id: {$user->id}, email: {$user->email}, library_id: {$user->library_id})");

            if ($this->confirm('Do you want to update this user with the provided data and set the new password?', true)) {
                $this->applyDataToUser($user, $libraryId, $email, $password, $fullname, $course, $year);
                $this->info('User updated successfully.');
                return 0;
            }

            $this->info('No changes made.');
            return 0;
        }

        // Create new user
        $user = new User();
        $this->applyDataToUser($user, $libraryId, $email, $password, $fullname, $course, $year);
        $user->save();

        $this->info("Student account created: id={$user->id}, email={$user->email}, library_id={$user->library_id}");
        $this->line("Temporary password: {$password}");

        return 0;
    }

    protected function applyDataToUser(User $user, $libraryId, $email, $password, $fullname, $course = null, $year = null)
    {
        // Parse fullname into firstname, mi, lastname if possible
        $parts = preg_split('/\s+/', trim($fullname));
        $firstname = $parts[0] ?? $fullname;
        $lastname = count($parts) > 1 ? array_pop($parts) : '';
        $mi = '';

        if (count($parts) > 1) {
            // remaining middle parts
            $middleParts = array_slice($parts, 1);
            $mi = substr($middleParts[0], 0, 1);
        } elseif (preg_match('/\b([A-Z])\b/i', $fullname, $m)) {
            $mi = $m[1];
        }

        $user->name = $fullname;
        $user->firstname = $firstname;
        $user->mi = $mi ?: null;
        $user->lastname = $lastname ?: null;
        $user->library_id = $libraryId;
        $user->email = $email;
        $user->password = Hash::make($password);
        if ($course) $user->course = $course;
        if ($year) $user->year = $year;
        $user->role = 'student';
        $user->email_verified_at = now();
    }
}
