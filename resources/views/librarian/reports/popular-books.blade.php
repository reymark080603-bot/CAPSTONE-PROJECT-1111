@extends('layouts.librarian')

@section('title', 'Popular Books')

@section('content')
<div class="p-6 space-y-6">
    <style>
        @media print { .header, .sidebar, .sidebar-backdrop, .no-print { display:none!important } .main-content{margin:0!important;padding:0!important;width:100%!important} a[href]:after{content:""!important} }
    </style>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 print-page">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 flex items-center">
                    <i class="fas fa-star text-yellow-500 mr-3"></i>
                    Popular Books
                </h1>
                <p class="text-gray-600 mt-1">Period: {{ $data['period']['from'] }} to {{ $data['period']['to'] }}</p>
            </div>
            <div class="flex items-center space-x-2 no-print">
                <a href="{{ route('librarian.reports.index') }}" class="px-3 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">
                    <i class="fas fa-arrow-left mr-1"></i> Back
                </a>
                <a href="{{ route('librarian.reports.export', 'popular-books') }}?date_from={{ $data['period']['from'] }}&date_to={{ $data['period']['to'] }}" class="px-3 py-2 rounded-lg bg-green-600 hover:bg-green-700 text-white">
                    <i class="fas fa-download mr-1"></i> Export CSV
                </a>
                <a href="{{ route('librarian.reports.print', 'popular-books') }}?date_from={{ $data['period']['from'] }}&date_to={{ $data['period']['to'] }}" target="_blank" class="px-3 py-2 rounded-lg bg-gray-700 hover:bg-gray-800 text-white">
                    <i class="fas fa-print mr-1"></i> Print
                </a>
            </div>
        </div>

        <div class="mt-8 bg-white border rounded-lg">
            <div class="p-4 border-b">
                <h2 class="text-lg font-semibold text-gray-900">Top Books</h2>
            </div>
            <div class="p-4 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-gray-600">
                        <tr>
                            <th class="py-2 pr-4">Title</th>
                            <th class="py-2 pr-4">Author</th>
                            <th class="py-2 pr-4">Category</th>
                            <th class="py-2 pr-4">Course</th>
                            <th class="py-2 pr-4">Borrow Count</th>
                            <th class="py-2 pr-4">Unique Borrowers</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-900">
                        @forelse($data['popular_books'] as $b)
                        <tr class="border-t">
                            <td class="py-2 pr-4">{{ $b->title }}</td>
                            <td class="py-2 pr-4">{{ $b->author }}</td>
                            <td class="py-2 pr-4">{{ $b->category }}</td>
                            <td class="py-2 pr-4">{{ $b->course }}</td>
                            <td class="py-2 pr-4 font-medium">{{ $b->borrow_count }}</td>
                            <td class="py-2 pr-4">{{ $b->unique_borrowers }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="py-4 text-gray-500">No data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-8 grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white border rounded-lg">
                <div class="p-4 border-b">
                    <h2 class="text-lg font-semibold text-gray-900">Popular by Category</h2>
                </div>
                <div class="p-4 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-left text-gray-600">
                            <tr>
                                <th class="py-2 pr-4">Category</th>
                                <th class="py-2 pr-4">Borrows</th>
                                <th class="py-2 pr-4">Unique Books</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-900">
                            @forelse($data['popular_by_category'] as $c)
                            <tr class="border-t">
                                <td class="py-2 pr-4">{{ $c->category ?? '—' }}</td>
                                <td class="py-2 pr-4 font-medium">{{ $c->total_borrows }}</td>
                                <td class="py-2 pr-4">{{ $c->unique_books }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="py-4 text-gray-500">No data.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="bg-white border rounded-lg">
                <div class="p-4 border-b">
                    <h2 class="text-lg font-semibold text-gray-900">Popular by Course</h2>
                </div>
                <div class="p-4 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-left text-gray-600">
                            <tr>
                                <th class="py-2 pr-4">Course</th>
                                <th class="py-2 pr-4">Borrows</th>
                                <th class="py-2 pr-4">Unique Books</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-900">
                            @forelse($data['popular_by_course'] as $c)
                            <tr class="border-t">
                                <td class="py-2 pr-4">{{ $c->course ?? '—' }}</td>
                                <td class="py-2 pr-4 font-medium">{{ $c->total_borrows }}</td>
                                <td class="py-2 pr-4">{{ $c->unique_books }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="py-4 text-gray-500">No data.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
@if(request()->boolean('print'))
<script>window.addEventListener('load',()=>{window.print(); setTimeout(()=>{window.close&&window.close()},600)})</script>
@endif
@endsection