<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     * Only seeds the librarian/admin account.
     * Sample books, students, and notifications are NOT seeded to keep the database clean.
     */
    public function run(): void
    {
        $this->call([
            LibrarianSeeder::class,
        ]);
    }
}
