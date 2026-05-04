@extends('layouts.librarian')

@section('title', 'View Book')

@section('content')
@php
    $authorNames = $book->authors->pluck('name')->filter()->implode(', ');
    $storedAuthor = trim((string) $book->getOriginal('author'));
    $authorDisplay = $authorNames !== ''
        ? $authorNames
        : ($storedAuthor !== '' && strtolower($storedAuthor) !== 'unknown author' ? $storedAuthor : 'Unknown');
    $resourceType = $book->resource_type ?: 'book';
    $resourceTypeDisplay = match ($resourceType) {
        'e_journal' => 'E-Journal',
        'thesis' => 'Thesis',
        default => 'Book',
    };
    $courseDisplay = $book->course ?? 'All Programs';
    $publishedYearDisplay = $book->published_year ?? 'Not specified';
    $languageDisplay = $book->language ?? 'Not specified';
@endphp

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
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Cover Photo</label>
            <div class="aspect-[3/4] w-full max-w-[260px] rounded-lg overflow-hidden bg-gray-100 flex items-center justify-center border mx-auto lg:mx-0">
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

        <div class="lg:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                <div class="w-full border border-gray-300 rounded-lg px-4 py-3 bg-gray-50 text-gray-900">{{ $book->title ?? 'Untitled' }}</div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Author</label>
                <div class="w-full border border-gray-300 rounded-lg px-4 py-3 bg-gray-50 text-gray-900">{{ $authorDisplay }}</div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Program</label>
                <div class="w-full border border-gray-300 rounded-lg px-4 py-3 bg-gray-50 text-gray-900">{{ $courseDisplay }}</div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Availability</label>
                @php($status = $book->availability_status ?? 'available')
                <div class="w-full border border-gray-300 rounded-lg px-4 py-3 bg-gray-50 text-gray-900 capitalize">{{ $status }}</div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Resource Type</label>
                <div class="w-full border border-gray-300 rounded-lg px-4 py-3 bg-gray-50 text-gray-900">{{ $resourceTypeDisplay }}</div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Published Year</label>
                <div class="w-full border border-gray-300 rounded-lg px-4 py-3 bg-gray-50 text-gray-900">{{ $publishedYearDisplay }}</div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Language</label>
                <div class="w-full border border-gray-300 rounded-lg px-4 py-3 bg-gray-50 text-gray-900">{{ $languageDisplay }}</div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Created</label>
                <div class="w-full border border-gray-300 rounded-lg px-4 py-3 bg-gray-50 text-gray-900">{{ optional($book->created_at)->format('Y-m-d H:i') ?? 'Not specified' }}</div>
            </div>
            @if(($book->file_type ?? null) && ($book->pdf_file || $book->epub_file || $book->doc_file))
            <div class="md:col-span-2 rounded-lg border border-blue-200 bg-blue-50 p-4">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-file text-blue-600"></i>
                        <div>
                            <div class="text-sm text-blue-900 font-medium">Digital file available</div>
                            <div class="text-xs text-blue-700">Type: {{ strtoupper($book->file_type) }}</div>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2">
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
