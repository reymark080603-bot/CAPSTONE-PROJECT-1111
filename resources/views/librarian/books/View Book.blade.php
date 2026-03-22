@extends('layouts.librarian')

@section('title', 'View Book')

@section('content')
<div class="mb-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Book Details</h1>
            <p class="text-gray-600 mt-2">Full information about this book</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('librarian.books.edit', $book->id) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-3 rounded-lg flex items-center gap-2 transition-all duration-200 hover:transform hover:scale-105 shadow">
                <i class="fas fa-edit"></i>
                Edit Book
            </a>
            <a href="{{ route('librarian.books.index') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-800 px-5 py-3 rounded-lg flex items-center gap-2 transition-all duration-200">
                <i class="fas fa-arrow-left"></i>
                Back to List
            </a>
        </div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border p-6">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Cover -->
        <div>
            <div class="aspect-[3/4] w-full rounded-lg overflow-hidden bg-gray-100 flex items-center justify-center border">
                @if($book->display_cover_url && $book->display_cover_url !== asset('storage/covers/default-book.png'))
                    <img src="{{ $book->display_cover_url }}" alt="{{ $book->title }} cover" class="w-full h-full object-cover">
                @else
                    <div class="text-gray-400 flex flex-col items-center">
                        <i class="fas fa-book text-5xl mb-2"></i>
                        <span>No cover</span>
                    </div>
                @endif
            </div>
        </div>

        <!-- Details -->
        <div class="lg:col-span-2 space-y-6">
            <div>
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">{{ $book->title ?? 'Untitled' }}</h2>
                        <p class="text-gray-600 mt-1">by <span class="font-medium">{{ $book->authors->pluck('name')->implode(', ') ?? 'Unknown' }}</span></p>
                    </div>
                    <div>
                        @php($status = $book->availability_status ?? 'available')
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                            {{ $status === 'available' ? 'bg-green-100 text-green-700' : '' }}
                            {{ $status === 'borrowed' ? 'bg-yellow-100 text-yellow-700' : '' }}
                            {{ $status === 'reserved' ? 'bg-purple-100 text-purple-700' : '' }}
                            {{ $status === 'maintenance' ? 'bg-gray-100 text-gray-700' : '' }}
                        ">
                            <i class="fas fa-circle mr-1 text-[10px]"></i>
                            {{ ucfirst($status) }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="text-xs uppercase text-gray-500">Author</div>
                    <div class="text-gray-900 font-medium">{{ $book->authors->pluck('name')->implode(', ') ?? 'Not specified' }}</div>
                </div>
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="text-xs uppercase text-gray-500">Category</div>
                    <div class="text-gray-900 font-medium">{{ $book->categories->pluck('name')->implode(', ') ?? '—' }}</div>
                </div>
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="text-xs uppercase text-gray-500">Course</div>
                    <div class="text-gray-900 font-medium">{{ $book->course ?? '—' }}</div>
                </div>
                                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="text-xs uppercase text-gray-500">Publisher</div>
                    <div class="text-gray-900 font-medium">{{ $book->publisher_name ?? '—' }}</div>
                </div>
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="text-xs uppercase text-gray-500">Published Year</div>
                    <div class="text-gray-900 font-medium">{{ $book->published_year ?? '—' }}</div>
                </div>
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="text-xs uppercase text-gray-500">Language</div>
                    <div class="text-gray-900 font-medium">{{ $book->language ?? '—' }}</div>
                </div>
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="text-xs uppercase text-gray-500">Created</div>
                    <div class="text-gray-900 font-medium">{{ optional($book->created_at)->format('Y-m-d H:i') ?? '—' }}</div>
                </div>
            </div>

            <div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Description</h3>
                <div class="prose max-w-none text-gray-700">
                    @if(!empty($book->description))
                        {!! nl2br(e($book->description)) !!}
                    @else
                        <p class="text-gray-500">No description provided.</p>
                    @endif
                </div>
            </div>

            @if(($book->file_type ?? null) && ($book->pdf_file || $book->epub_file || $book->doc_file))
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-file text-blue-600"></i>
                        <div>
                            <div class="text-sm text-blue-900 font-medium">Digital file available</div>
                            <div class="text-xs text-blue-700">Type: {{ strtoupper($book->file_type) }}</div>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        @if($book->pdf_file)
                        <a href="{{ asset($book->pdf_file) }}" target="_blank" class="px-3 py-2 text-sm bg-white border border-blue-300 text-blue-700 rounded hover:bg-blue-100">Open PDF</a>
                        @endif
                        @if($book->epub_file)
                        <a href="{{ asset($book->epub_file) }}" target="_blank" class="px-3 py-2 text-sm bg-white border border-blue-300 text-blue-700 rounded hover:bg-blue-100">Download EPUB</a>
                        @endif
                        @if($book->doc_file)
                        <a href="{{ asset($book->doc_file) }}" target="_blank" class="px-3 py-2 text-sm bg-white border border-blue-300 text-blue-700 rounded hover:bg-blue-100">Download DOC</a>
                        @endif
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
