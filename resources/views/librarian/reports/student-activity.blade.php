@extends('layouts.librarian')

@section('title', 'Student Activity')

@section('content')
<div class="p-6 space-y-6">
    <style>
        @media print { .header, .sidebar, .sidebar-backdrop, .no-print { display:none!important } .main-content{margin:0!important;padding:0!important;width:100%!important} a[href]:after{content:""!important} }
    </style>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 print-page">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 flex items-center">
                    <i class="fas fa-user-graduate text-green-600 mr-3"></i>
                    Student Activity
                </h1>
                <p class="text-gray-600 mt-1">Period: {{ $data['period']['from'] }} to {{ $data['period']['to'] }}</p>
            </div>
            <div class="flex items-center space-x-2 no-print">
                <a href="{{ route('librarian.reports.index') }}" class="px-3 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">
                    <i class="fas fa-arrow-left mr-1"></i> Back
                </a>
                <a href="{{ route('librarian.reports.export', 'student-activity') }}?date_from={{ $data['period']['from'] }}&date_to={{ $data['period']['to'] }}" class="px-3 py-2 rounded-lg bg-green-600 hover:bg-green-700 text-white">
                    <i class="fas fa-download mr-1"></i> Export CSV
                </a>
                <a href="{{ route('librarian.reports.print', 'student-activity') }}?date_from={{ $data['period']['from'] }}&date_to={{ $data['period']['to'] }}" target="_blank" class="px-3 py-2 rounded-lg bg-gray-700 hover:bg-gray-800 text-white">
                    <i class="fas fa-print mr-1"></i> Print
                </a>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="rounded-lg border p-4">
                <p class="text-sm text-gray-600">Active Students</p>
                <p class="text-3xl font-bold text-gray-900">{{ $data['summary']['total_active_students'] }}</p>
            </div>
            <div class="rounded-lg border p-4">
                <p class="text-sm text-gray-600">Avg. Borrows / Active Student</p>
                <p class="text-3xl font-bold text-gray-900">{{ $data['summary']['average_borrows_per_student'] }}</p>
            </div>
            <div class="rounded-lg border p-4">
                <p class="text-sm text-gray-600">Most Active Student</p>
                <p class="text-base font-semibold text-gray-900">{{ isset($data['summary']['most_active_student']) && isset($data['summary']['most_active_student']['name']) ? $data['summary']['most_active_student']['name'] : '—' }}</p>
            </div>
        </div>

        <div class="mt-8 bg-white border rounded-lg">
            <div class="p-4 border-b">
                <h2 class="text-lg font-semibold text-gray-900">Students</h2>
            </div>
            <div class="p-4 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-gray-600">
                        <tr>
                            <th class="py-2 pr-4">Name</th>
                            <th class="py-2 pr-4">Library ID</th>
                            <th class="py-2 pr-4">Course</th>
                            <th class="py-2 pr-4">Year</th>
                            <th class="py-2 pr-4">Total Borrowed</th>
                            <th class="py-2 pr-4">Returned</th>
                            <th class="py-2 pr-4">Currently Borrowed</th>
                            <th class="py-2 pr-4">Overdue</th>
                            <th class="py-2 pr-4">Activity</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-900">
                        @forelse($data['students'] as $s)
                        <tr class="border-t">
                            <td class="py-2 pr-4">{{ $s['name'] }}</td>
                            <td class="py-2 pr-4">{{ $s['library_id'] }}</td>
                            <td class="py-2 pr-4">{{ $s['course'] }}</td>
                            <td class="py-2 pr-4">{{ $s['year'] }}</td>
                            <td class="py-2 pr-4 font-medium">{{ $s['total_borrowed'] }}</td>
                            <td class="py-2 pr-4">{{ $s['total_returned'] }}</td>
                            <td class="py-2 pr-4">{{ $s['currently_borrowed'] }}</td>
                            <td class="py-2 pr-4">{{ $s['overdue_books'] }}</td>
                            <td class="py-2 pr-4">{{ $s['activity_level'] }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="9" class="py-4 text-gray-500">No student activity for this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
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