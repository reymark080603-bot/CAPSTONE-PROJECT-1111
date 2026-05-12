<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Book extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'isbn',
        'resource_type',
        'volume',
        'issue',
        'advisor',
        'defense_date',
        'degree',
        'cover_photo',
        'cover_image', // PDF-generated cover thumbnail
        'pdf_file',
        'epub_file',
        'doc_file',
        'content',
        'published_year',
        'availability_status',
        'course',
        'publisher_id',
        'language',
        'file_type',
        'copies_total',
        'copies_available',
        'year_level',
        'author',
        'category',
        // Bulk upload fields
        'year',
        'program',
        'file_path',
        'file_hash',
    ];

    protected $casts = [
        'published_year' => 'integer',
    ];

    public function borrowRecords()
    {
        return $this->hasMany(BorrowRecord::class);
    }

    public function authors()
    {
        return $this->belongsToMany(Author::class, 'author_book');
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'book_category');
    }

    public function publisher()
    {
        return $this->belongsTo(Publisher::class, 'publisher_id');
    }

    public function currentBorrower()
    {
        return $this->hasOne(BorrowRecord::class)
            ->where('status', 'borrowed')
            ->latest('borrowed_date');
    }

    public function isAvailable()
    {
        return $this->availability_status === 'available';
    }

    public function scopeAvailable($query)
    {
        return $query->where('availability_status', 'available');
    }

    public function scopeByCategory($query, $category)
    {
        return $query->whereHas('categories', function ($q) use ($category) {
            $q->where('name', $category);
        });
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'LIKE', "%{$search}%")
              ->orWhere('description', 'LIKE', "%{$search}%")
              ->orWhereHas('authors', function ($a) use ($search) {
                  $a->where('name', 'LIKE', "%{$search}%");
              })
              ->orWhereHas('categories', function ($c) use ($search) {
                  $c->where('name', 'LIKE', "%{$search}%");
              })
              ->orWhereHas('publisher', function ($p) use ($search) {
                  $p->where('name', 'LIKE', "%{$search}%");
              });
        });
    }

    public function scopeByCourse($query, $course)
    {
        return $query->where('course', $course)
                    ->orWhereNull('course')
                    ->orWhere('course', '');
    }

    public function hasPdfFile()
    {
        $paths = array_filter([
            $this->pdf_file,
            // Legacy/dedicated bulk upload used file_path for PDFs
            $this->file_path,
        ]);

        foreach ($paths as $path) {
            $normalized = ltrim($path, '/');

            if (file_exists(public_path($normalized))) {
                return true;
            }

            if (Storage::disk('public')->exists($normalized)) {
                return true;
            }

            if (str_starts_with($normalized, 'storage/')) {
                $relative = substr($normalized, 8);
                if (Storage::disk('public')->exists($relative)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function getPdfUrl()
    {
        $paths = array_filter([
            $this->pdf_file,
            // Legacy/dedicated bulk upload used file_path for PDFs
            $this->file_path,
        ]);

        foreach ($paths as $path) {
            $normalized = ltrim($path, '/');

            if (file_exists(public_path($normalized))) {
                return asset($normalized);
            }

            if (Storage::disk('public')->exists($normalized)) {
                return asset('library-assets/' . $normalized);
            }

            if (str_starts_with($normalized, 'storage/')) {
                $relative = substr($normalized, 8);
                if (Storage::disk('public')->exists($relative)) {
                    return asset('library-assets/' . $relative);
                }
            }
        }

        return null;
    }

    public function hasEpubFile()
    {
        return !empty($this->epub_file) && file_exists(public_path($this->epub_file));
    }

    public function hasDocFile()
    {
        return !empty($this->doc_file) && file_exists(public_path($this->doc_file));
    }

    public function hasAnyEbookFile()
    {
        return $this->hasPdfFile() || $this->hasEpubFile() || $this->hasDocFile();
    }

    public function hasReadableContent()
    {
        return $this->hasAnyEbookFile() || !empty($this->content);
    }

    public function getPrimaryFileUrl()
    {
        if ($this->hasPdfFile()) return $this->getPdfUrl();
        if ($this->hasEpubFile()) return asset('library-assets/' . ltrim($this->epub_file, '/'));
        if ($this->hasDocFile()) return asset('library-assets/' . ltrim($this->doc_file, '/'));
        return null;
    }

    public function getPrimaryFileType()
    {
        if ($this->hasPdfFile()) return 'pdf';
        if ($this->hasEpubFile()) return 'epub';
        if ($this->hasDocFile()) return 'doc';
        return 'html';
    }

    public function getAvailableFormats()
    {
        $formats = [];
        if ($this->hasPdfFile()) $formats['pdf'] = $this->getPdfUrl();
        if ($this->hasEpubFile()) $formats['epub'] = asset('library-assets/' . ltrim($this->epub_file, '/'));
        if ($this->hasDocFile()) $formats['doc'] = asset('library-assets/' . ltrim($this->doc_file, '/'));
        if (!empty($this->content)) $formats['html'] = 'html';
        return $formats;
    }

    public function getAuthorAttribute()
    {
        if ($this->relationLoaded('authors')) {
            $names = $this->authors->pluck('name')->all();
            if (!empty($names)) {
                return implode(', ', $names);
            }
        }

        $storedAuthor = trim((string)($this->attributes['author'] ?? ''));
        if ($storedAuthor !== '' && strtolower($storedAuthor) !== 'unknown author') {
            return $storedAuthor;
        }
        
        return 'Unknown Author';
    }

    public function getCategoryAttribute()
    {
        $storedCategory = $this->attributes['category'] ?? null;
        if (!empty($storedCategory)) {
            return $storedCategory;
        }

        if ($this->relationLoaded('categories')) {
            $names = $this->categories->pluck('name')->all();
            if (!empty($names)) {
                return implode(', ', $names);
            }
        }
        
        return 'General';
    }

        public function getPublisherNameAttribute()
    {
        if ($this->relationLoaded('publisher') && !is_null($this->getRelation('publisher'))) {
            return $this->getRelation('publisher')->name;
        }
        
        $legacy = $this->getOriginal('publisher');
        if (!empty($legacy)) {
            return $legacy;
        }
        
        return 'Not specified';
    }

    public function getCustomIdAttribute()
    {
        return 'BK-' . str_pad($this->id, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Get cover photo URL with default fallback
     */
    public function getCoverPhotoUrlAttribute()
    {
        if ($this->cover_photo) {
            $resolved = $this->resolveCoverUrl($this->cover_photo);
            if ($resolved) {
                return $resolved;
            }
        }
        
        // Return default cover
        return asset('library-assets/covers/default-book.png');
    }

    /**
     * Check if book has a cover image
     */
    public function hasCoverImage()
    {
        return !empty($this->cover_photo);
    }

    /**
     * Get the best available cover image URL.
     * Priority: cover_image (PDF generated) > cover_photo (uploaded) > default
     * 
     * @return string
     */
    public function getDisplayCoverUrlAttribute()
    {
        // First priority: PDF-generated cover thumbnail
        if (!empty($this->cover_image)) {
            $coverUrl = $this->resolveCoverUrl($this->cover_image);
            if (!empty($coverUrl)) {
                return $coverUrl;
            }
        }

        // Second priority: Manually uploaded cover photo
        if (!empty($this->cover_photo)) {
            $photoUrl = $this->resolveCoverUrl($this->cover_photo);
            if (!empty($photoUrl)) {
                return $photoUrl;
            }
        }

        // Fallback: Default cover image
        return asset('library-assets/covers/default-book.png');
    }

    /**
     * Check if book has a PDF-generated cover image
     * 
     * @return bool
     */
    public function hasPdfCover()
    {
        if (empty($this->cover_image)) {
            return false;
        }

        $path = ltrim($this->cover_image, '/');
        if (file_exists(public_path($path))) {
            return true;
        }

        if (Storage::disk('public')->exists($path)) {
            return true;
        }

        if (str_starts_with($path, 'storage/')) {
            return Storage::disk('public')->exists(substr($path, 8));
        }

        return false;
    }

    /**
     * Resolve mixed legacy/new cover paths to a public URL.
     */
    private function resolveCoverUrl(?string $path): ?string
    {
        if (empty($path)) return null;
        if (str_starts_with($path, 'http')) return $path;

        $normalized = ltrim($path, '/');
        
        // If it starts with storage/, we redirect it through our ghost route
        if (str_starts_with($normalized, 'storage/')) {
            $pathWithoutStorage = substr($normalized, 8);
            return asset('library-assets/' . $pathWithoutStorage);
        }

        // Otherwise, assume it's a relative path and route it through library-assets
        return asset('library-assets/' . $normalized);
    }
}
