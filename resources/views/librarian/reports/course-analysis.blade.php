@extends('layouts.librarian')

@section('title', 'Course Analysis')

@section('content')
<div class="p-6 space-y-6">
    <style>
        @media print { .header, .sidebar, .sidebar-backdrop, .no-print { display:none!important } .main-content{margin:0!important;padding:0!important;width:100%!important} a[href]:after{content:""!important} }
    </style>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 print-page">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 flex items-center">
                    <i class="fas fa-graduation-cap text-indigo-600 mr-3"></i>
                    Course Analysis
                </h1>
                <p class="text-gray-600 mt-1">Period: {{ $data['period']['from'] }} to {{ $data['period']['to'] }}</p>
            </div>
            <div class="flex items-center space-x-2 no-print">
                <a href="{{ route('librarian.reports.index') }}" class="px-3 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">
                    <i class="fas fa-arrow-left mr-1"></i> Back
                </a>
                <a href="{{ route('librarian.reports.print', 'course-analysis') }}?date_from={{ $data['period']['from'] }}&date_to={{ $data['period']['to'] }}" target="_blank" class="px-3 py-2 rounded-lg bg-gray-700 hover:bg-gray-800 text-white">
                    <i class="fas fa-print mr-1"></i> Generate Printable Report
                </a>
            </div>
        </div>

        <div class="mt-8 bg-white border rounded-lg">
            <div class="p-4 border-b">
                <h2 class="text-lg font-semibold text-gray-900">Course Statistics</h2>
            </div>
            <div class="p-4 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-gray-600">
                        <tr>
                            <th class="py-2 pr-4">Course</th>
                            <th class="py-2 pr-4">Total Students</th>
                            <th class="py-2 pr-4">Total Borrows</th>
                            <th class="py-2 pr-4">Active Borrows</th>
                            <th class="py-2 pr-4">Overdue Borrows</th>
                            <th class="py-2 pr-4">Borrow Rate / Student</th>
                            <th class="py-2 pr-4">Overdue Rate %</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-900">
                        @forelse($data['course_statistics'] as $c)
                        <tr class="border-t">
                            <td class="py-2 pr-4">{{ $c['course'] ?? '—' }}</td>
                            <td class="py-2 pr-4">{{ $c['total_students'] }}</td>
                            <td class="py-2 pr-4 font-medium">{{ $c['total_borrows'] }}</td>
                            <td class="py-2 pr-4">{{ $c['active_borrows'] }}</td>
                            <td class="py-2 pr-4">{{ $c['overdue_borrows'] }}</td>
                            <td class="py-2 pr-4">{{ $c['borrow_rate_per_student'] }}</td>
                            <td class="py-2 pr-4">{{ $c['overdue_rate_percent'] }}%</td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="py-4 text-gray-500">No data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-8 bg-white border rounded-lg">
            <div class="p-4 border-b">
                <h2 class="text-lg font-semibold text-gray-900">Top Books by Course</h2>
            </div>
            <div class="p-4 space-y-6">
                @forelse($data['books_by_course'] as $course => $books)
                    <div>
                        <h3 class="font-semibold text-gray-900">{{ $course ?: '—' }}</h3>
                        <div class="mt-2 overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead class="text-left text-gray-600">
                                    <tr>
                                        <th class="py-2 pr-4">Title</th>
                                        <th class="py-2 pr-4">Author</th>
                                        <th class="py-2 pr-4">Category</th>
                                        <th class="py-2 pr-4">Borrow Count</th>
                                    </tr>
                                </thead>
                                <tbody class="text-gray-900">
                                    @forelse($books as $b)
                                    <tr class="border-t">
                                        <td class="py-2 pr-4">{{ $b->title }}</td>
                                        <td class="py-2 pr-4">{{ $b->author }}</td>
                                        <td class="py-2 pr-4">{{ $b->category }}</td>
                                        <td class="py-2 pr-4 font-medium">{{ $b->borrow_count }}</td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="4" class="py-4 text-gray-500">No books.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @empty
                    <div class="text-gray-500">No course data.</div>
                @endforelse
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