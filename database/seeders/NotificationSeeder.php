<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Notification;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Find the librarian user
        $librarian = User::where('role_id', 2)->first(); // Assuming role_id 2 = librarian
        
        if (!$librarian) {
            $this->command->warn('No librarian user found. Skipping notification seeding.');
            return;
        }

        // Create sample notifications for the librarian
        $notifications = [
            [
                'type' => 'info',
                'message' => 'Welcome to the Librarian Dashboard!',
                'data' => ['action' => 'dashboard'],
                'is_read' => false,
            ],
            [
                'type' => 'warning',
                'message' => '3 books are overdue for return',
                'data' => ['count' => 3],
                'is_read' => false,
            ],
            [
                'type' => 'success',
                'message' => 'New student registration completed',
                'data' => ['student_count' => 1],
                'is_read' => false,
            ],
            [
                'type' => 'alert',
                'message' => 'Library will be closed on Monday for maintenance',
                'data' => ['date' => now()->addDays(3)->format('Y-m-d')],
                'is_read' => false,
            ],
            [
                'type' => 'info',
                'message' => 'Monthly report is ready for download',
                'data' => ['report_type' => 'monthly'],
                'is_read' => true,
            ],
        ];

        foreach ($notifications as $notification) {
            Notification::create([
                'user_id' => $librarian->id,
                'type' => $notification['type'],
                'message' => $notification['message'],
                'data' => $notification['data'],
                'is_read' => $notification['is_read'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command->info('Sample notifications created for librarian ID: ' . $librarian->id);
    }
}
