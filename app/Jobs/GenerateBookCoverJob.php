<?php

namespace App\Jobs;

use App\Models\Book;
use App\Services\PdfCoverService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class GenerateBookCoverJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $bookId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $bookId)
    {
        $this->bookId = $bookId;
    }

    /**
     * Execute the job.
     */
    public function handle(PdfCoverService $pdfCoverService): void
    {
        try {
            $book = Book::find($this->bookId);

            if (!$book) {
                Log::warning("GenerateBookCoverJob: Book not found.", ['book_id' => $this->bookId]);
                return;
            }

            $pdfPath = $book->pdf_file ?? $book->file_path;

            if (empty($pdfPath)) {
                Log::warning("GenerateBookCoverJob: PDF file path is empty.", ['book_id' => $book->id]);
                return;
            }

            $normalized = ltrim($pdfPath, '/');

            if (str_starts_with($normalized, 'storage/')) {
                $relative = substr($normalized, 8);
                $fullPdfPath = Storage::disk('public')->path($relative);
            } else {
                $fullPdfPath = Storage::disk('public')->path($normalized);
            }

            if (!file_exists($fullPdfPath) || !is_readable($fullPdfPath)) {
                Log::warning('GenerateBookCoverJob: PDF file missing or unreadable', [
                    'book_id' => $book->id,
                    'full_path' => $fullPdfPath,
                ]);
                return;
            }

            Log::info('GenerateBookCoverJob: Starting cover generation', [
                'book_id' => $book->id,
                'title' => $book->title,
            ]);

            $generatedCover = $pdfCoverService->generateCover($fullPdfPath, $book->title);

            if (!empty($generatedCover)) {
                if (Schema::hasColumn('books', 'cover_image')) {
                    $book->cover_image = $generatedCover;
                }
                if (Schema::hasColumn('books', 'cover_photo')) {
                    $book->cover_photo = $generatedCover;
                }
                
                $book->save();

                Log::info('GenerateBookCoverJob: Cover successfully updated.', [
                    'book_id' => $book->id,
                    'cover' => $generatedCover,
                ]);

                // Flush cache to ensure the new cover is shown instantly on refresh
                Cache::flush();
            } else {
                Log::warning('GenerateBookCoverJob: Failed to generate cover.', [
                    'book_id' => $book->id,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('GenerateBookCoverJob failed with exception: ' . $e->getMessage(), [
                'book_id' => $this->bookId,
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
