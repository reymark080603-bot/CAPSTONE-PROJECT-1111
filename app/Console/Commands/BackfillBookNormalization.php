<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use App\Models\Book;
use App\Models\Author;
use App\Models\Category;
use App\Models\Publisher;

class BackfillBookNormalization extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'library:backfill-normalization {--dry-run : Show what would change without saving}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill authors, categories, and publisher relations from existing Book fields (non-destructive)';

    public function handle(): int
    {
        $dryRun = (bool)$this->option('dry-run');
        $this->info('Backfilling normalized relations from existing books...');
        if ($dryRun) {
            $this->comment('DRY RUN: No changes will be written.');
        }

        $total = Book::count();
        if ($total === 0) {
            $this->info('No books found. Nothing to backfill.');
            return 0;
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        Book::query()->orderBy('id')->chunk(200, function ($books) use ($bar, $dryRun) {
            foreach ($books as $book) {
                // Authors (from string column `author`)
                $authorNames = $this->parseMultiNames((string)($book->author ?? ''));
                foreach ($authorNames as $name) {
                    if ($name === '') continue;
                    $author = Author::where('name', $name)->first();
                    if (!$author && !$dryRun) {
                        $author = Author::create(['name' => $name]);
                    }
                    if ($author && !$dryRun) {
                        // Attach if not already
                        if (!$book->authors->contains($author->id)) {
                            $book->authors()->attach($author->id);
                        }
                    }
                }

                // Categories (from string column `category`)
                $categoryNames = $this->parseMultiNames((string)($book->category ?? ''));
                foreach ($categoryNames as $catName) {
                    if ($catName === '') continue;
                    // Prefer lookup by name (unique); fallback to slug
                    $category = Category::where('name', $catName)->first();
                    if (!$category) {
                        $slug = $this->uniqueSlug($catName);
                        $category = Category::where('slug', $slug)->first();
                    }
                    if (!$category && !$dryRun) {
                        $slug = $this->uniqueSlug($catName);
                        $category = Category::create(['name' => $catName, 'slug' => $slug]);
                    }
                    if ($category && !$dryRun) {
                        if (!$book->categories->contains($category->id)) {
                            $book->categories()->attach($category->id);
                        }
                    }
                }

                // Publisher (from string column `publisher`)
                $publisherName = trim((string)$book->publisher);
                if ($publisherName !== '' && !$book->publisher_id) {
                    $publisher = Publisher::where('name', $publisherName)->first();
                    if (!$publisher && !$dryRun) {
                        $publisher = Publisher::create(['name' => $publisherName]);
                    }
                    if ($publisher && !$dryRun) {
                        $book->publisher_id = $publisher->id;
                        // Direct property assignment is allowed even if not in $fillable
                        $book->save();
                    }
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->info('Backfill complete.');
        if ($dryRun) {
            $this->comment('Run without --dry-run to apply changes.');
        }
        return 0;
    }

    /**
     * Parse a possibly multi-valued string into individual names.
     * Supports comma, semicolon, and the word "and" as separators.
     *
     * @param string $value
     * @return array<int, string>
     */
    protected function parseMultiNames(string $value): array
    {
        if ($value === '') return [];
        // Normalize separators: replace ' and ' with commas to unify splitting
        $normalized = preg_replace('/\s+and\s+/i', ',', $value);
        $parts = preg_split('/[;,]/', (string)$normalized) ?: [];
        $parts = array_map(fn($s) => trim($s), $parts);
        $parts = array_filter($parts, fn($s) => $s !== '');
        // De-duplicate while preserving order
        $seen = [];
        $unique = [];
        foreach ($parts as $p) {
            $key = mb_strtolower($p);
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $unique[] = $p;
            }
        }
        return $unique;
    }

    /**
     * Create a unique slug for a category name.
     */
    protected function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        if ($base === '') {
            $base = Str::slug('category');
        }
        $slug = $base;
        $i = 2;
        while (Category::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i;
            $i++;
            if ($i > 1000) { // safety stop
                break;
            }
        }
        return $slug;
    }
}
