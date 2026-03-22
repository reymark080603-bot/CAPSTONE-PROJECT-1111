<?php

namespace App\Console\Commands;

use App\Models\Book;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ClearUploadedEbooks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'library:clear-ebook-files {--force : Skip the confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete uploaded ebook files and clear their file references from books';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (!$this->option('force') && !$this->confirm('This will delete uploaded ebook files and clear their file references. Continue?')) {
            $this->info('Operation cancelled.');

            return self::SUCCESS;
        }

        $deletedFiles = 0;
        $missingFiles = 0;
        $updatedBooks = 0;

        Book::query()
            ->where(function ($query) {
                $query->whereNotNull('pdf_file')
                    ->orWhereNotNull('epub_file')
                    ->orWhereNotNull('doc_file')
                    ->orWhereNotNull('file_path');
            })
            ->chunkById(100, function ($books) use (&$deletedFiles, &$missingFiles, &$updatedBooks) {
                foreach ($books as $book) {
                    $paths = array_filter([
                        $book->pdf_file,
                        $book->epub_file,
                        $book->doc_file,
                        $book->file_path,
                    ]);

                    foreach (array_unique($paths) as $path) {
                        $result = $this->deleteStoredFile($path);

                        if ($result === true) {
                            $deletedFiles++;
                        } else {
                            $missingFiles++;
                        }
                    }

                    $book->forceFill([
                        'pdf_file' => null,
                        'epub_file' => null,
                        'doc_file' => null,
                        'file_path' => null,
                        'file_type' => null,
                    ])->save();

                    $updatedBooks++;
                }
            });

        foreach (['ebooks', 'books/pdfs', 'books/documents'] as $directory) {
            if (!Storage::disk('public')->exists($directory)) {
                continue;
            }

            foreach (Storage::disk('public')->allFiles($directory) as $file) {
                if (Storage::disk('public')->delete($file)) {
                    $deletedFiles++;
                } else {
                    $missingFiles++;
                }
            }
        }

        $this->info('Uploaded ebook cleanup complete.');
        $this->line("Books updated: {$updatedBooks}");
        $this->line("Files deleted: {$deletedFiles}");
        $this->line("Files already missing or not deleted: {$missingFiles}");

        return self::SUCCESS;
    }

    private function deleteStoredFile(string $path): bool
    {
        $normalized = ltrim($path, '/');

        if (Storage::disk('public')->exists($normalized)) {
            return Storage::disk('public')->delete($normalized);
        }

        if (str_starts_with($normalized, 'storage/')) {
            $relative = substr($normalized, 8);

            if (Storage::disk('public')->exists($relative)) {
                return Storage::disk('public')->delete($relative);
            }
        }

        if (file_exists(public_path($normalized))) {
            return unlink(public_path($normalized));
        }

        return false;
    }
}
