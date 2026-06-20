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
    protected ?string $base64Thumbnail;

    /**
     * Create a new job instance.
     */
    public function __construct(int $bookId, ?string $base64Thumbnail = null)
    {
        $this->bookId = $bookId;
        $this->base64Thumbnail = $base64Thumbnail;
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

            $generatedCover = null;

            // If a base64 thumbnail was generated on the frontend, upload it in the background
            if (!empty($this->base64Thumbnail)) {
                $useCloudinary = (bool) env('CLOUDINARY_URL');
                if ($useCloudinary) {
                    try {
                        $result = \CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary::upload($this->base64Thumbnail, [
                            'folder' => 'knowly/covers'
                        ]);
                        $generatedCover = $result->getSecurePath();
                    } catch (\Exception $e) {
                        Log::error("GenerateBookCoverJob Cloudinary Upload failed: " . $e->getMessage());
                    }
                } else {
                    if (preg_match('/^data:image\/(\w+);base64,/', $this->base64Thumbnail, $type)) {
                        $ext = strtolower($type[1]);
                        if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
                            $rawBase64 = substr($this->base64Thumbnail, strpos($this->base64Thumbnail, ',') + 1);
                            $decoded   = base64_decode($rawBase64);
                            if ($decoded !== false) {
                                $coverPath = 'covers/' . \Illuminate\Support\Str::random(12) . '_' . time() . '.' . $ext;
                                if (Storage::disk('public')->put($coverPath, $decoded)) {
                                    $generatedCover = $coverPath;
                                }
                            }
                        }
                    }
                }
            }

            // Fallback to generating cover from PDF if no frontend thumbnail was provided or if its upload failed
            if (empty($generatedCover)) {
                $pdfPath = $book->pdf_file ?? $book->file_path;

                if (!empty($pdfPath)) {
                    $normalized = ltrim($pdfPath, '/');

                    if (str_starts_with($normalized, 'storage/')) {
                        $relative = substr($normalized, 8);
                        $fullPdfPath = Storage::disk('public')->path($relative);
                    } else {
                        $fullPdfPath = Storage::disk('public')->path($normalized);
                    }

                    if (file_exists($fullPdfPath) && is_readable($fullPdfPath)) {
                        Log::info('GenerateBookCoverJob: Starting cover generation from PDF', [
                            'book_id' => $book->id,
                            'title' => $book->title,
                        ]);
                        $generatedCover = $pdfCoverService->generateCover($fullPdfPath, $book->title);
                    } else {
                        Log::warning('GenerateBookCoverJob: PDF file missing or unreadable', [
                            'book_id' => $book->id,
                            'full_path' => $fullPdfPath,
                        ]);
                    }
                }
            }

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
                Log::warning('GenerateBookCoverJob: Failed to generate/upload cover.', [
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
