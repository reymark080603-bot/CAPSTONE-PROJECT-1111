<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Services\PdfCoverService;
use App\Services\LibrarianNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class BulkUploadController extends Controller
{
    protected PdfCoverService $pdfCoverService;
    protected LibrarianNotificationService $librarianNotificationService;

    const STORAGE_DIR = 'ebooks';
    const DEFAULT_COVER = 'covers/default-book.png';

    public function __construct(PdfCoverService $pdfCoverService, LibrarianNotificationService $librarianNotificationService)
    {
        $this->pdfCoverService = $pdfCoverService;
        $this->librarianNotificationService = $librarianNotificationService;
        
        $this->middleware(function ($request, $next) {
            if (!Auth::guard('librarian')->check()) {
                if ($request->expectsJson()) {
                    return response()->json(['error' => 'Authentication required'], 401);
                }
                return redirect()->route('librarian.login');
            }
            return $next($request);
        });
    }

    public function index()
    {
        $user = Auth::user();
        return view('librarian.books.bulk-upload', compact('user'));
    }

    public function process(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'pdfs' => 'required|array|min:1',
            'pdfs.*' => 'required|file|mimes:pdf|max:51200',
        ], [
            'pdfs.required' => 'Please select at least one PDF file.',
            'pdfs.array' => 'Invalid upload payload.',
            'pdfs.*.required' => 'Please select at least one PDF file.',
            'pdfs.*.file' => 'Each file must be a valid file.',
            'pdfs.*.mimes' => 'All files must be PDF format.',
            'pdfs.*.max' => 'Each file must not exceed 50MB.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        if (!$request->hasFile('pdfs') || empty($request->file('pdfs'))) {
            return response()->json([
                'success' => false,
                'message' => 'Please select at least one PDF file to upload.'
            ], 422);
        }

        $files = $request->file('pdfs');
        $seenOriginalNames = [];
        $seenFileHashes = [];
        $uploadedCount = 0;
        $failedCount = 0;
        $errors = [];
        $createdBooks = [];
        $createdBookModels = [];
        $coversGenerated = 0;
        $duplicatesSkipped = 0;

        $this->ensureStorageDirectoriesExist();

        DB::beginTransaction();

        try {
            foreach ($files as $file) {
                try {
                    if ($file->getMimeType() !== 'application/pdf') {
                        throw new \Exception('Invalid file type. Only PDF files are allowed.');
                    }

                    $originalName = trim((string) $file->getClientOriginalName());
                    if (isset($seenOriginalNames[$originalName])) {
                        $duplicatesSkipped++;
                        throw new \Exception('Duplicate file in current upload batch.');
                    }
                    $seenOriginalNames[$originalName] = true;

                    $fileHash = hash_file('sha256', $file->getRealPath());
                    if (!$fileHash) {
                        throw new \Exception('Failed to calculate uploaded file hash.');
                    }

                    if (isset($seenFileHashes[$fileHash])) {
                        $duplicatesSkipped++;
                        throw new \Exception('Duplicate PDF content detected in current upload batch.');
                    }
                    $seenFileHashes[$fileHash] = true;

                    $metadata = $this->parseFilename($originalName);

                    if (Schema::hasColumn('books', 'file_hash')) {
                        $existingHashMatch = Book::query()
                            ->where('file_hash', $fileHash)
                            ->first();

                        if ($existingHashMatch) {
                            $duplicatesSkipped++;
                            throw new \Exception("Exact duplicate PDF already exists (Book ID: {$existingHashMatch->id}).");
                        }
                    }

                    $existingBook = Book::query()
                        ->where('title', $metadata['title'])
                        ->where('author', $metadata['author'])
                        ->where('published_year', $metadata['year'])
                        ->where('program', $metadata['program'])
                        ->where('resource_type', $metadata['resource_type'])
                        ->first();

                    if ($existingBook) {
                        $duplicatesSkipped++;
                        throw new \Exception("Duplicate record exists (Book ID: {$existingBook->id}).");
                    }

                    $filePath = $this->storeFile($file, $metadata['title']);

                    if (!$filePath) {
                        throw new \Exception('Failed to store PDF file');
                    }

                    $coverToSave = self::DEFAULT_COVER;
                    $thumbnails = $request->input('thumbnails', []);
                    $hasFrontendThumb = false;
                    
                    if (isset($thumbnails[$originalName]) && !empty($thumbnails[$originalName])) {
                        try {
                            $base64Data = $thumbnails[$originalName];
                            if (preg_match('/^data:image\/(\w+);base64,/', $base64Data, $type)) {
                                $base64Data = substr($base64Data, strpos($base64Data, ',') + 1);
                                $ext = strtolower($type[1]);
                                if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
                                    $decoded = base64_decode($base64Data);
                                    if ($decoded !== false) {
                                        $coverFilename = \Illuminate\Support\Str::random(12) . '_' . time() . '.' . $ext;
                                        $coverPath = 'covers/' . $coverFilename;
                                        if (env('CLOUDINARY_URL')) {
                                                $result = Cloudinary::upload("data:image/$ext;base64,$base64Data", [
                                                    'folder' => 'knowly/covers'
                                                ]);
                                                $coverToSave = $result->getSecurePath();
                                                $coversGenerated++;
                                                $hasFrontendThumb = true;
                                            } else if (Storage::disk('public')->put($coverPath, $decoded)) {
                                                $coverToSave = $coverPath;
                                                $coversGenerated++;
                                                $hasFrontendThumb = true;
                                            }
                                        }
                                    }
                                }
                            }
                        } catch (\Exception $e) {
                            Log::error("Failed to decode frontend thumbnail: " . $e->getMessage());
                        }
                    }
                    
                    if (!$hasFrontendThumb) {
                        $fullPdfPath = Storage::disk('public')->path($filePath);
                        $coverImagePath = $this->pdfCoverService->generateCover($fullPdfPath, $metadata['title']);

                        if ($coverImagePath) {
                            $coverToSave = $coverImagePath;
                            $coversGenerated++;
                            Log::info("Cover generated for: {$metadata['title']}");
                        }
                    }

                    $book = Book::create([
                        'title' => $metadata['title'],
                        'author' => $metadata['author'],
                        'published_year' => $metadata['year'],
                        'program' => $metadata['program'],
                        'course' => $metadata['program'],
                        'resource_type' => $metadata['resource_type'],
                        'file_type' => 'pdf',
                        'pdf_file' => $filePath,
                        'file_path' => $filePath,
                        'file_hash' => $fileHash,
                        'cover_image' => $coverToSave,
                        'cover_photo' => $coverToSave,
                        'availability_status' => 'available',
                        'language' => 'English',
                    ]);

                    $uploadedCount++;
                    $createdBookModels[] = $book;
                    $createdBooks[] = [
                        'id' => $book->id,
                        'title' => $book->title,
                        'author' => $book->author,
                        'cover_image' => $coverToSave,
                        'cover_url' => asset('storage/' . ltrim($coverToSave, '/'))
                    ];

                } catch (\Exception $e) {
                    $failedCount++;
                    $errors[] = "Failed to upload {$file->getClientOriginalName()}: " . $e->getMessage();
                    Log::error("Bulk upload error for file {$file->getClientOriginalName()}: " . $e->getMessage());
                }
            }

            DB::commit();

            if ($uploadedCount > 0) {
                $librarian = Auth::guard('librarian')->user() ?: Auth::user();
                if ($librarian) {
                    $this->librarianNotificationService->notifyBulkResourcesUploaded($librarian, $createdBookModels, 'bulk_upload');
                }
            }

            if ($uploadedCount > 0) {
                $message = "Successfully uploaded {$uploadedCount} book(s).";
                
                if ($coversGenerated > 0) {
                    $message .= " {$coversGenerated} cover image(s) auto-generated.";
                }

                if ($duplicatesSkipped > 0) {
                    $message .= " {$duplicatesSkipped} duplicate file(s) skipped.";
                }
                
                if ($failedCount > 0) {
                    $message .= " {$failedCount} file(s) failed.";
                }

                Log::info('Bulk PDF upload completed', [
                    'user_id' => Auth::id(),
                    'uploaded' => $uploadedCount,
                    'failed' => $failedCount,
                    'duplicates_skipped' => $duplicatesSkipped,
                    'covers_generated' => $coversGenerated
                ]);

                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'data' => [
                        'uploaded' => $uploadedCount,
                        'failed' => $failedCount,
                        'duplicates_skipped' => $duplicatesSkipped,
                        'covers_generated' => $coversGenerated,
                        'created_books' => $createdBooks,
                        'errors' => $errors
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No files were uploaded successfully.',
                    'errors' => $errors
                ], 422);
            }

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Bulk upload failed', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred during upload. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    private function parseFilename(string $filename): array
    {
        $filenameWithoutExt = pathinfo($filename, PATHINFO_FILENAME);

        if (preg_match('/^(.*?)\s+-\s*(.*?)\s+-\s*(\d{4})\s+-\s*([A-Za-z0-9][A-Za-z0-9 ]*)\s+-\s*([A-Za-z0-9_\/ -]+)$/', $filenameWithoutExt, $matches)) {
            return [
                'title' => trim($matches[1]) !== '' ? trim($matches[1]) : 'Unknown Title',
                'author' => trim($matches[2]) !== '' ? trim($matches[2]) : 'Unknown Author',
                'year' => (int) $matches[3],
                'program' => trim($matches[4]) !== '' ? trim($matches[4]) : 'General',
                'resource_type' => $this->normalizeResourceType($matches[5]),
            ];
        }

        if (preg_match('/^(.*?)\s+-\s*(.*?)\s+-\s*(\d{4})\s+-\s*([A-Za-z0-9][A-Za-z0-9 ]*)$/', $filenameWithoutExt, $matches)) {
            return [
                'title' => trim($matches[1]) !== '' ? trim($matches[1]) : 'Unknown Title',
                'author' => trim($matches[2]) !== '' ? trim($matches[2]) : 'Unknown Author',
                'year' => (int) $matches[3],
                'program' => trim($matches[4]) !== '' ? trim($matches[4]) : 'General',
                'resource_type' => 'book',
            ];
        }

        return [
            'title' => trim($filenameWithoutExt) !== '' ? trim($filenameWithoutExt) : 'Unknown Title',
            'author' => 'Unknown Author',
            'year' => date('Y'),
            'program' => 'General',
            'resource_type' => 'book',
        ];
    }

    private function normalizeResourceType(?string $type): string
    {
        $normalized = strtolower(trim((string) $type));
        $normalized = str_replace(['-', ' '], '_', $normalized);

        return match ($normalized) {
            'e_journal', 'ejournal', 'journal' => 'e_journal',
            'e_thesis', 'thesis' => 'thesis',
            'ebook', 'ebooks', 'book', 'books' => 'book',
            default => 'book',
        };
    }

    private function storeFile($file, string $title)
    {
        try {
            $sanitizedTitle = preg_replace('/[^a-zA-Z0-9_-]/', '_', $title);
            $filename = time() . '_' . $sanitizedTitle . '_' . substr(md5(uniqid((string) mt_rand(), true)), 0, 8) . '.pdf';
            $path = $file->storeAs(self::STORAGE_DIR, $filename, 'public');
            
            return $path ?: false;
        } catch (\Exception $e) {
            Log::error('Error storing file: ' . $e->getMessage());
            return false;
        }
    }

    private function ensureStorageDirectoriesExist(): void
    {
        if (!Storage::disk('public')->exists(self::STORAGE_DIR)) {
            Storage::disk('public')->makeDirectory(self::STORAGE_DIR);
        }

        if (!Storage::disk('public')->exists('covers')) {
            Storage::disk('public')->makeDirectory('covers');
        }
    }

    public function checkStorage()
    {
        try {
            $storageInfo = $this->pdfCoverService->getStorageInfo();

            return response()->json([
                'success' => true,
                'storage_status' => [
                    'linked' => $this->isStorageLinked(),
                    'pdf_directory' => self::STORAGE_DIR,
                    'covers_directory' => 'covers',
                    'pdf_count' => $storageInfo['pdf']['count'],
                    'pdf_size_mb' => $storageInfo['pdf']['size_mb'],
                    'cover_count' => $storageInfo['covers']['count'],
                    'cover_size_mb' => $storageInfo['covers']['size_mb'],
                    'pdftoppm_available' => $storageInfo['pdftoppm_available'],
                    'imagick_available' => $storageInfo['imagick_available'],
                    'spatie_pdf_to_image_available' => $storageInfo['spatie_pdf_to_image_available'] ?? false,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check storage: ' . $e->getMessage()
            ], 500);
        }
    }

    private function isStorageLinked(): bool
    {
        try {
            $testFile = 'storage_link_test_' . time() . '.txt';

            Storage::disk('public')->put($testFile, 'test');
            $exists = Storage::disk('public')->exists($testFile);
            Storage::disk('public')->delete($testFile);

            return $exists;
        } catch (\Throwable $e) {
            Log::error('Storage link check failed: ' . $e->getMessage());
            return false;
        }
    }
}