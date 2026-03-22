<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Book;

class BookSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Sample books for testing the dashboard
        $books = [
            [
                'title' => 'Introduction to Programming',
                'author' => 'John Smith',
                'category' => 'Programming',
                'description' => 'A comprehensive guide to programming fundamentals for beginners.',
                'course' => 'BSIT',
                'year_level' => '1st Year',
                'published_year' => 2023,
                'availability_status' => 'available',
                'publisher' => 'Tech Books Publishing'
            ],
            [
                'title' => 'Data Structures and Algorithms',
                'author' => 'Jane Doe',
                'category' => 'Programming',
                'description' => 'Essential concepts in data structures and algorithms.',
                'course' => 'BSIT',
                'year_level' => '2nd Year',
                'published_year' => 2022,
                'availability_status' => 'available',
                'publisher' => 'Computer Science Press'
            ],
            [
                'title' => 'Database Management Systems',
                'author' => 'Robert Johnson',
                'category' => 'Technology',
                'description' => 'Learn about relational databases and SQL.',
                'course' => 'BSIT',
                'year_level' => '3rd Year',
                'published_year' => 2021,
                'availability_status' => 'available',
                'publisher' => 'Data Publishing House'
            ],
            [
                'title' => 'Web Development Fundamentals',
                'author' => 'Sarah Wilson',
                'category' => 'Programming',
                'description' => 'HTML, CSS, and JavaScript basics for web development.',
                'course' => 'BSIT',
                'year_level' => '1st Year',
                'published_year' => 2023,
                'availability_status' => 'available',
                'publisher' => 'Web Tech Books'
            ],
            [
                'title' => 'Computer Networks',
                'author' => 'Michael Brown',
                'category' => 'Technology',
                'description' => 'Understanding network protocols and architecture.',
                'course' => 'BSIT',
                'year_level' => '3rd Year',
                'published_year' => 2020,
                'availability_status' => 'available',
                'publisher' => 'Network Publications'
            ],
            [
                'title' => 'Software Engineering Principles',
                'author' => 'Emily Davis',
                'category' => 'Programming',
                'description' => 'Best practices in software development lifecycle.',
                'course' => 'BSIT',
                'year_level' => '4th Year',
                'published_year' => 2022,
                'availability_status' => 'available',
                'publisher' => 'Engineering Books Ltd'
            ],
        ];

        foreach ($books as $bookData) {
            Book::updateOrCreate(
                ['title' => $bookData['title']],
                $bookData
            );
        }

        $this->command->info('Sample books seeded successfully!');
        $this->command->info('Books added for BSIT course (various year levels).');
    }
}