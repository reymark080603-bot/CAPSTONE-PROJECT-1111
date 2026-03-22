<?php

namespace App\Services;

use App\Models\Book;
use App\Models\BorrowRecord;
use App\Models\Notification;
use App\Models\User;

class LibrarianNotificationService
{
    public function notifyBookBorrowed(User $student, Book $book, BorrowRecord $borrowRecord): void
    {
        $message = sprintf('%s borrowed "%s"', $student->full_name, $book->title);
        $description = sprintf(
            'Library ID: %s. Due on %s.',
            $student->library_id ?: 'N/A',
            optional($borrowRecord->due_date)->format('M d, Y')
        );

        $this->notifyAllLibrarians('info', $message, [
            'description' => $description,
            'student_id' => $student->id,
            'book_id' => $book->id,
            'borrow_record_id' => $borrowRecord->id,
            'action_url' => '/librarian/loans',
            'event' => 'book_borrowed',
        ]);
    }

    public function notifySingleResourceUploaded(User $actor, Book $book, string $source = 'single_upload'): void
    {
        $message = sprintf('%s uploaded a new resource: "%s"', $actor->full_name ?: ($actor->name ?: 'A librarian'), $book->title);
        $description = sprintf(
            'Author: %s. Program: %s.',
            $book->author ?: 'Unknown Author',
            $book->course ?: ($book->program ?: 'General')
        );

        $this->notifyAllLibrarians('success', $message, [
            'description' => $description,
            'book_id' => $book->id,
            'source' => $source,
            'action_url' => '/librarian/books',
            'event' => 'resource_uploaded',
        ]);
    }

    public function notifyBulkResourcesUploaded(User $actor, array $books, string $source = 'bulk_upload'): void
    {
        if ($books === []) {
            return;
        }

        $count = count($books);
        $message = sprintf('%s uploaded %d new resource(s)', $actor->full_name ?: ($actor->name ?: 'A librarian'), $count);
        $titles = collect($books)
            ->map(fn (Book $book) => $book->title)
            ->take(3)
            ->implode(', ');

        $description = $titles !== ''
            ? sprintf('Recent uploads: %s%s', $titles, $count > 3 ? '...' : '.')
            : 'New resources were added through bulk upload.';

        $this->notifyAllLibrarians('success', $message, [
            'description' => $description,
            'book_ids' => collect($books)->pluck('id')->all(),
            'count' => $count,
            'source' => $source,
            'action_url' => '/librarian/books',
            'event' => 'bulk_resources_uploaded',
        ]);
    }

    private function notifyAllLibrarians(string $type, string $message, array $data = []): void
    {
        $librarians = User::query()
            ->where(function ($query) {
                $query->where('role', 'librarian')
                    ->orWhereHas('role', function ($roleQuery) {
                        $roleQuery->where('name', 'librarian');
                    });
            })
            ->get();

        foreach ($librarians as $librarian) {
            Notification::create([
                'user_id' => $librarian->id,
                'type' => $type,
                'message' => $message,
                'data' => $data,
                'is_read' => false,
            ]);
        }
    }
}
