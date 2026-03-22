@extends('layouts.librarian')

@section('title', 'Borrowing Statistics')

@section('content')
<div class="p-6 space-y-6">
    <style>
        @media print { .header, .sidebar, .sidebar-backdrop, .no-print { display:none!important } .main-content{margin:0!important;padding:0!important;width:100%!important} a[href]:after{content:""!important} }
    </style>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 print-page">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 flex items-center">
                    <i class="fas fa-chart-line text-blue-600 mr-3"></i>
                    Borrowing Statistics
                </h1>
                <p class="text-gray-600 mt-1">Period: {{ $data['period']['from'] }} to {{ $data['period']['to'] }}</p>
            </div>
            <div class="flex items-center space-x-2 no-print">
                <a href="{{ route('librarian.reports.index') }}" class="px-3 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">
                    <i class="fas fa-arrow-left mr-1"></i> Back
                </a>

                <a href="{{ route('librarian.reports.print', 'borrowing-statistics') }}?date_from={{ $data['period']['from'] }}&date_to={{ $data['period']['to'] }}" target="_blank" class="px-3 py-2 rounded-lg bg-gray-700 hover:bg-gray-800 text-white">
                    <i class="fas fa-print mr-1"></i> Generate Printable Report
                </a>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="rounded-lg border p-4">
                <p class="text-sm text-gray-600">Total Borrows</p>
                <p class="text-3xl font-bold text-gray-900">{{ $data['summary']['total_borrows'] }}</p>
            </div>
            <div class="rounded-lg border p-4">
                <p class="text-sm text-gray-600">Total Returns</p>
                <p class="text-3xl font-bold text-gray-900">{{ $data['summary']['total_returns'] }}</p>
            </div>
            <div class="rounded-lg border p-4">
                <p class="text-sm text-gray-600">Average Per Day</p>
                <p class="text-3xl font-bold text-gray-900">{{ $data['summary']['average_per_day'] }}</p>
            </div>
        </div>

        <div class="mt-8 grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white border rounded-lg">
                <div class="p-4 border-b">
                    <h2 class="text-lg font-semibold text-gray-900">Monthly Breakdown</h2>
                </div>
                <div class="p-4 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-left text-gray-600">
                            <tr>
                                <th class="py-2 pr-4">Period</th>
                                <th class="py-2 pr-4">Count</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-900">
                            @forelse($data['monthly_data'] as $m)
                            <tr class="border-t">
                                <td class="py-2 pr-4">{{ $m['period'] }}</td>
                                <td class="py-2 pr-4 font-medium">{{ $m['count'] }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="2" class="py-4 text-gray-500">No data.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="bg-white border rounded-lg">
                <div class="p-4 border-b">
                    <h2 class="text-lg font-semibold text-gray-900">Category Breakdown</h2>
                </div>
                <div class="p-4 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-left text-gray-600">
                            <tr>
                                <th class="py-2 pr-4">Category</th>
                                <th class="py-2 pr-4">Count</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-900">
                            @forelse($data['category_data'] as $c)
                            <tr class="border-t">
                                <td class="py-2 pr-4">{{ $c->category ?? '—' }}</td>
                                <td class="py-2 pr-4 font-medium">{{ $c->count }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="2" class="py-4 text-gray-500">No data.</td></tr>
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
