{{--
    Book Cover Component
    
    Displays a book cover image with automatic fallback to default cover.
    Uses the following priority:
    1. cover_image (PDF-generated cover thumbnail)
    2. cover_photo (manually uploaded cover)
    3. Default cover image
    
    Usage:
    <x-book-cover :book="$book" />
    <x-book-cover :src="$coverPath" alt="Book Title" class="custom-class" />
    
    @param Book|null $book - Book model instance
    @param string|null $src - Direct cover image path (optional, overrides book)
    @param string $alt - Alt text for image
    @param string $class - Additional CSS classes
    @param int $width - Image width
    @param int $height - Image height
--}}

@props([
    'book' => null,
    'src' => null,
    'alt' => 'Book Cover',
    'class' => '',
    'width' => null,
    'height' => null,
])

@php
    // Determine the cover image URL
    $coverUrl = null;
    
    if ($src) {
        // Direct source provided
        $coverUrl = asset($src);
    } elseif ($book) {
        // Use book's display cover URL (has built-in fallback logic)
        $coverUrl = $book->display_cover_url;
    }
    
    // Build style attribute for dimensions
    $style = '';
    if ($width) {
        $style .= 'width: ' . $width . 'px;';
    }
    if ($height) {
        $style .= 'height: ' . $height . 'px;';
    }
    
    // Build img attributes
    $imgAttributes = [
        'src' => $coverUrl ?? asset('storage/covers/default-book.png'),
        'alt' => $alt,
        'class' => 'book-cover-img ' . $class,
        'loading' => 'lazy',
    ];
    
    if ($style) {
        $imgAttributes['style'] = $style;
    }
@endphp

<img {{ collect($imgAttributes)->map(fn($value, $key) => "$key=\"$value\"")->join(' ') }}>

