<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class OldUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $oldUsers = [
            [
                'name' => 'Jhon T Alaw',
                'firstname' => 'Jhon',
                'mi' => 'T',
                'gender_id' => 1,
                'gender' => 'male',
                'lastname' => 'Ark',
                'library_id' => '133721',
                'course_id' => 7, // mapped from 9
                'year_level_id' => 2,
                'role_id' => 1,
                'email' => 'Jhon123@gmail.com',
                'email_verified_at' => '2025-11-20 04:20:09',
                'password' => '$2y$12$Cc92hzJzdOlLDKtGkkKoSetMklNns2wtjaqY4x4XbbI.ov1BIl2rG',
                'created_at' => '2025-11-20 04:20:09',
            ],
            [
                'name' => 'Mark R Rad',
                'firstname' => 'Mark',
                'mi' => 'R',
                'gender_id' => 1,
                'gender' => 'male',
                'lastname' => 'Rad',
                'library_id' => 'LIB003',
                'course_id' => 4, // mapped from 10
                'year_level_id' => 3,
                'role_id' => 1,
                'email' => 'radores8623@gmail.com',
                'email_verified_at' => '2025-11-20 23:51:35',
                'password' => '$2y$12$.lgfNTRf0KEAP2J12qZCruSRFpj8cGGs/tsDOjlS3kM8iP3scxAeK',
                'created_at' => '2025-11-20 23:51:35',
            ],
            [
                'name' => 'Sab K Rian',
                'firstname' => 'Sab',
                'mi' => 'K',
                'gender_id' => 2,
                'gender' => 'female',
                'lastname' => 'Rian',
                'library_id' => '70870',
                'course_id' => 7, // mapped from 9
                'year_level_id' => 1,
                'role_id' => 1,
                'email' => 'Sab15@gmail.com',
                'email_verified_at' => '2025-11-26 01:05:57',
                'password' => '$2y$12$pBcjC.v8nvD/MO/a2FW0e.vhD.cYiC3Hlpay.nqnZkO7JEJyV0IDm',
                'created_at' => '2025-11-25 04:45:57',
            ],
            [
                'name' => 'Carlito Dequito',
                'firstname' => 'Carlito',
                'mi' => null,
                'gender_id' => 2,
                'gender' => 'female',
                'lastname' => 'Dequito',
                'library_id' => '395942',
                'course_id' => 2, // mapped from 8
                'year_level_id' => 4,
                'role_id' => 1,
                'email' => 'scatter123@gmail.com',
                'email_verified_at' => '2025-11-30 19:01:35',
                'password' => '$2y$12$SyvLMkjpFEBseHB78/jhLOGECBvnFEOLLAnOHQeQ05tCQKHQM5hEe',
                'created_at' => '2025-11-30 19:01:35',
            ],
            [
                'name' => 'Rey R Rado',
                'firstname' => 'Rey',
                'mi' => 'R',
                'gender_id' => 1,
                'gender' => 'male',
                'lastname' => 'Rado',
                'library_id' => '809013',
                'course_id' => 7, // mapped from 9
                'year_level_id' => 2,
                'role_id' => 1,
                'email' => 'markrt08@gmail.com',
                'email_verified_at' => '2025-11-30 19:04:50',
                'password' => '$2y$12$vLsOIevtn1mw0wLARVBxN.EvJBq6unFoS7klvBgGb7R3knOPdBIgq',
                'created_at' => '2025-11-30 19:04:50',
            ],
            [
                'name' => 'sadasdsa asdsadsad',
                'firstname' => 'sadasdsa',
                'mi' => null,
                'gender_id' => 1,
                'gender' => 'male',
                'lastname' => 'asdsadsad',
                'library_id' => '12321',
                'course_id' => 2, // mapped from 8
                'year_level_id' => 2,
                'role_id' => 1,
                'email' => 'scatter@gmail.com',
                'email_verified_at' => '2026-01-07 00:25:22',
                'password' => '$2y$12$xkIw.ki8Ufkp1S2JWvEW4etwezM4.ZIgVksrvd.tJMI9td9wlF4le',
                'created_at' => '2025-11-30 21:16:43',
            ]
        ];

        foreach ($oldUsers as $userData) {
            User::firstOrCreate(
                ['email' => $userData['email']],
                $userData
            );
        }

        $this->command->info('Old users from the SQL dump have been imported successfully!');
    }
}
