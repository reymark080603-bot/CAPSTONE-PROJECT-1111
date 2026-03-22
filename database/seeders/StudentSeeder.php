<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class StudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get student role
        $studentRole = DB::table('roles')->where('name', 'student')->first();
        if (!$studentRole) {
            $this->command->error('Student role not found! Please run migrations first.');
            return;
        }

        // Create sample student accounts
        $students = [
            [
                'name' => 'John Doe',
                'firstname' => 'John',
                'lastname' => 'Doe',
                'email' => 'john.doe@university.edu',
                'library_id' => 'LIB2023001',
                'password' => Hash::make('Student123'),
                'role_id' => $studentRole->id,
                'email_verified_at' => now(),
                'course_id' => 1, // Assuming course_id 1 exists
                'year_level_id' => 3, // Assuming year_level_id 3 exists
                'gender_id' => 1, // Assuming gender_id 1 exists
            ],
            [
                'name' => 'Jane Smith',
                'firstname' => 'Jane',
                'lastname' => 'Smith',
                'email' => 'jane.smith@university.edu',
                'library_id' => 'LIB2023002',
                'password' => Hash::make('Student123'),
                'role_id' => $studentRole->id,
                'email_verified_at' => now(),
                'course_id' => 2, // Assuming course_id 2 exists
                'year_level_id' => 2, // Assuming year_level_id 2 exists
                'gender_id' => 2, // Assuming gender_id 2 exists
            ],
            [
                'name' => 'Mike Johnson',
                'firstname' => 'Mike',
                'lastname' => 'Johnson',
                'email' => 'mike.johnson@university.edu',
                'library_id' => 'LIB2023003',
                'password' => Hash::make('Student123'),
                'role_id' => $studentRole->id,
                'email_verified_at' => now(),
                'course_id' => 3, // Assuming course_id 3 exists
                'year_level_id' => 4, // Assuming year_level_id 4 exists
                'gender_id' => 1, // Assuming gender_id 1 exists
            ],
        ];

        foreach ($students as $student) {
            User::updateOrCreate(
                ['email' => $student['email']],
                $student
            );
        }

        $this->command->info('Student accounts created successfully!');
        $this->command->info('Created 3 student accounts with Library ID and password "Student123"');
        $this->command->info('Library IDs: LIB2023001, LIB2023002, LIB2023003');
    }
}
