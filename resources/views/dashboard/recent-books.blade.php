@extends('layouts.app')

@section('title', 'Recent Books - E-Resources')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="container mx-auto px-4">
        
        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Recent Books</h1>
            <p class="text-gray-600 mt-2">Browse our latest e-resources and academic materials</p>
        </div>

        <!-- Sort Options -->
        <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
            <div class="flex items-center space-x-2">
                <span class="text-gray-600">Sort by:</span>
                <div class="flex gap-2">
                    <a href="{{ route('recent.books', ['sort' => 'newest']) }}" 
                       class="px-4 py-2 rounded-lg text-sm {{ $sortBy === 'newest' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 border hover:bg-gray-50' }}">
                        Newest
                    </a>
                    <a href="{{ route('recent.books', ['sort' => 'oldest']) }}" 
                       class="px-4 py-2 rounded-lg text-sm {{ $sortBy === 'oldest' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 border hover:bg-gray-50' }}">
                        Oldest
                    </a>
                    <a href="{{ route('recent.books', ['sort' => 'title']) }}" 
                       class="px-4 py-2 rounded-lg text-sm {{ $sortBy === 'title' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 border hover:bg-gray-50' }}">
                        Title
                    </a>
                    <a href="{{ route('recent.books', ['sort' => 'author']) }}" 
                       class="px-4 py-2 rounded-lg text-sm {{ $sortBy === 'author' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 border hover:bg-gray-50' }}">
                        Author
                    </a>
                </div>
            </div>
            
            <div class="text-gray-500 text-sm">
                Showing {{ $books->count() }} of {{ $books->total() }} books
            </div>
        </div>

        <!-- Books Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6">
            @forelse($books as $book)
                <!-- Book Card -->
                <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition-shadow duration-300 overflow-hidden border border-gray-100">
                    
                    <!-- Cover Image -->
                    <div class="relative aspect-[3/4] bg-gray-100 overflow-hidden">
                        @if($book->cover_image && file_exists(public_path($book->cover_image)))
                            <img src="{{ asset($book->cover_image) }}" 
                                 alt="{{ $book->title }}" 
                                 class="w-full h-full object-cover"
                                 loading="lazy">
                        @elseif($book->cover_photo && file_exists(public_path($book->cover_photo)))
                            <img src="{{ $book->display_cover_url }}" 
                                 alt="{{ $book->title }}" 
                                 class="w-full h-full object-cover"
                                 loading="lazy">
                        @else
                            <!-- Default Cover -->
                            <div class="w-full h-full flex flex-col items-center justify-center bg-gradient-to-br from-blue-50 to-indigo-100 p-4">
                                <div class="w-16 h-20 bg-blue-600 rounded shadow-lg flex items-center justify-center mb-2">
                                    <i class="fas fa-book text-white text-2xl"></i>
                                </div>
                                <span class="text-xs text-gray-500 text-center">No Cover Available</span>
                            </div>
                        @endif
                        
                        <!-- Availability Badge -->
                        <div class="absolute top-2 right-2">
                            @if($book->availability_status === 'available')
                                <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">
                                    Available
                                </span>
                            @else
                                <span class="px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded-full">
                                    Unavailable
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Book Info -->
                    <div class="p-4">
                        <h3 class="font-semibold text-gray-900 text-sm leading-tight line-clamp-2 min-h-[2.5rem]" title="{{ $book->title }}">
                            {{ $book->title }}
                        </h3>
                        <p class="text-gray-500 text-xs mt-1 truncate" title="{{ $book->author }}">
                            {{ $book->author }}
                        </p>
                        
                        <!-- Meta Info -->
                        <div class="flex items-center justify-between mt-3 text-xs text-gray-400">
                            @if($book->published_year)
                                <span>{{ $book->published_year }}</span>
                            @endif
                            @if($book->program)
                                <span class="px-2 py-0.5 bg-gray-100 rounded">{{ $book->program }}</span>
                            @endif
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex gap-2 mt-4">
                            <!-- View Button -->
                            <a href="{{ route('books.show', $book->id) }}" 
                               class="flex-1 px-3 py-2 text-center text-sm font-medium text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors">
                                <i class="fas fa-eye mr-1"></i> View
                            </a>
                            
                            <!-- Borrow Button -->
                            @auth
                                @if(in_array(Auth::user()->role, ['student', 'librarian']))
                                    @if($book->availability_status === 'available' && !in_array($book->id, $borrowedBookIds))
                                        <button onclick="borrowBook({{ $book->id }})" 
                                                class="flex-1 px-3 py-2 text-center text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg transition-colors">
                                            <i class="fas fa-book-reader mr-1"></i> Borrow
                                        </button>
                                    @elseif(in_array($book->id, $borrowedBookIds))
                                        <button disabled 
                                                class="flex-1 px-3 py-2 text-center text-sm font-medium text-gray-400 bg-gray-100 rounded-lg cursor-not-allowed">
                                            <i class="fas fa-check mr-1"></i> Borrowed
                                        </button>
                                    @else
                                        <button disabled 
                                                class="flex-1 px-3 py-2 text-center text-sm font-medium text-gray-400 bg-gray-100 rounded-lg cursor-not-allowed">
                                            <i class="fas fa-ban mr-1"></i> Unavailable
                                        </button>
                                    @endif
                                @endif
                            @else
                                <a href="{{ route('login') }}" 
                                   class="flex-1 px-3 py-2 text-center text-sm font-medium text-white bg-gray-600 hover:bg-gray-700 rounded-lg transition-colors">
                                    <i class="fas fa-sign-in-alt mr-1"></i> Login
                                </a>
                            @endauth
                        </div>
                    </div>
                </div>
            @empty
                <!-- Empty State -->
                <div class="col-span-full">
                    <div class="text-center py-12">
                        <div class="w-20 h-20 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-book-open text-4xl text-gray-400"></i>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-700">No Books Available</h3>
                        <p class="text-gray-500 mt-2">Check back later for new resources.</p>
                    </div>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        @if($books->hasPages())
            <div class="mt-8">
                {{ $books->links() }}
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
/**
 * Borrow a book
 */
function borrowBook(bookId) {
    if (!confirm('Do you want to borrow this book?')) {
        return;
    }

    fetch(`{{ url('/borrow') }}/${bookId}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
        },
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message || 'Book borrowed successfully!');
            location.reload();
        } else {
            alert(data.message || 'Failed to borrow book.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    });
}
</script>
@endpush

<style>
/* Line clamp for title */
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Custom pagination styling */
.pagination {
    display: flex;
    justify-content: center;
    gap: 0.5rem;
}

.pagination > * {
    padding: 0.5rem 1rem;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    color: #374151;
    text-decoration: none;
    transition: all 0.2s;
}

.pagination > *:hover {
    background-color: #f3f4f6;
}

.pagination > .active {
    background-color: #2563eb;
    color: white;
    border-color: #2563eb;
}

.pagination > .disabled {
    color: #d1d5db;
    pointer-events: none;
}
</style>
@endsection

