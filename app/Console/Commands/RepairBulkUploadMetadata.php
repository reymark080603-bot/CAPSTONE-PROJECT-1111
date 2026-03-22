<?php

namespace App\Console\Commands;

use App\Models\Book;
use Illuminate\Console\Command;

class RepairBulkUploadMetadata extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'library:repair-bulk-metadata {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Repair author/program/year metadata for bulk-uploaded books when it can be recovered from the title';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (!$this->option('force') && !$this->confirm('Repair recoverable bulk-upload metadata from existing book titles?')) {
            $this->info('Operation cancelled.');

            return self::SUCCESS;
        }

        $updated = 0;

        Book::query()->orderBy('id')->chunkById(100, function ($books) use (&$updated) {
            foreach ($books as $book) {
                $payload = [];

                if ($this->looksLikeEmbeddedMetadata($book->title)) {
                    $parsed = $this->parseEmbeddedMetadata($book->title);

                    if ($parsed) {
                        if ($this->isUnknown($book->author)) {
                            $payload['author'] = $parsed['author'];
                        }

                        if (empty($book->course)) {
                            $payload['course'] = $parsed['program'];
                        }

                        if (($book->program === null || $book->program === '' || $book->program === 'General') && $parsed['program'] !== '') {
                            $payload['program'] = $parsed['program'];
                        }

                        if (empty($book->published_year) || (int) $book->published_year === (int) date('Y')) {
                            $payload['published_year'] = $parsed['year'];
                        }

                        if (!empty($payload['author'])) {
                            $payload['title'] = $parsed['title'];
                        }
                    }
                }

                if (empty($book->course) && !empty($book->program) && $book->program !== 'General') {
                    $payload['course'] = $book->program;
                }

                if ($payload !== []) {
                    $book->update($payload);
                    $updated++;
                }
            }
        });

        $this->info("Metadata repair complete. Updated {$updated} book(s).");

        return self::SUCCESS;
    }

    private function looksLikeEmbeddedMetadata(?string $title): bool
    {
        return is_string($title) && preg_match('/^(.*?)\s+-\s*(.*?)\s+-\s*(\d{4})\s+-\s*([A-Za-z0-9][A-Za-z0-9 ]*)$/', $title) === 1;
    }

    private function parseEmbeddedMetadata(string $value): ?array
    {
        if (!preg_match('/^(.*?)\s+-\s*(.*?)\s+-\s*(\d{4})\s+-\s*([A-Za-z0-9][A-Za-z0-9 ]*)$/', $value, $matches)) {
            return null;
        }

        return [
            'title' => trim($matches[1]),
            'author' => trim($matches[2]),
            'year' => (int) $matches[3],
            'program' => trim($matches[4]),
        ];
    }

    private function isUnknown(?string $author): bool
    {
        $normalized = strtolower(trim((string) $author));

        return $normalized === '' || $normalized === 'unknown author' || $normalized === 'n/a';
    }
}
