@extends('layouts.librarian')

@section('title', 'Monthly Summary')

@section('content')
<div class="p-6 space-y-6">
    <style>
        @media print {
            .header, .sidebar, .sidebar-backdrop, .no-print { display: none !important; }
            .main-content { margin: 0 !important; padding: 0 !important; width: 100% !important; }
            .print-page { box-shadow: none !important; border: none !important; padding: 20px !important; page-break-inside: avoid; }
            a[href]:after { content: "" !important; }
            .stat-card { break-inside: avoid; page-break-inside: avoid; margin-bottom: 1rem !important; }
            .grid { display: block !important; }
            .grid.grid-cols-1.md\:grid-cols-2.lg\:grid-cols-4 { 
                display: grid !important; 
                grid-template-columns: repeat(2, 1fr) !important; 
                gap: 1rem !important; 
                page-break-inside: avoid;
            }
            .grid.grid-cols-1.lg\:grid-cols-3 { display: block !important; }
            .grid.grid-cols-1.lg\:grid-cols-3 > * { margin-bottom: 2rem; page-break-inside: avoid; }
            .space-y-8 > * { page-break-inside: avoid; break-inside: avoid; }
            .bg-white.border.border-gray-200.rounded-xl.shadow-sm.overflow-hidden { 
                page-break-inside: avoid; 
                break-inside: avoid;
                margin-bottom: 2rem !important;
            }
            body { font-size: 12px; }
            .text-3xl { font-size: 1.5rem !important; }
            .text-2xl { font-size: 1.25rem !important; }
            .text-xl { font-size: 1.125rem !important; }
            .text-4xl { font-size: 2rem !important; }
            .p-6 { padding: 1rem !important; }
            .p-8 { padding: 1.5rem !important; }
            .mb-8 { margin-bottom: 1.5rem !important; }
            .mb-10 { margin-bottom: 2rem !important; page-break-after: avoid; }
            .gap-6 { gap: 1rem !important; }
            .gap-8 { gap: 1.5rem !important; }
            
            /* Force new page for major sections if needed */
            .space-y-8 > *:not(:first-child) {
                page-break-before: auto;
                break-before: auto;
            }
            
            /* Ensure tables don't break */
            table { page-break-inside: auto; }
            thead { display: table-row-group; }
            tr { page-break-inside: avoid; page-break-after: auto; }
        }
        .stat-card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
    </style>

    <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-8 print-page">
        <!-- Header Section -->
        <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6 mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 flex items-center mb-2">
                    <i class="fas fa-calendar-alt text-purple-600 mr-4 text-2xl"></i>
                    Monthly Summary — {{ $data['period']['month_name'] }}
                </h1>
                <p class="text-gray-600 text-lg">Period: {{ $data['period']['start_date'] }} to {{ $data['period']['end_date'] }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-2 no-print">
                <a href="{{ route('librarian.reports.index') }}" class="px-3 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 transition-colors text-sm">
                    <i class="fas fa-arrow-left mr-1"></i> Back
                </a>
                <a href="{{ route('librarian.reports.export', 'monthly-summary') }}?year={{ $data['period']['year'] }}&month={{ $data['period']['month'] }}&format=pdf" class="px-3 py-2 rounded-lg bg-red-600 hover:bg-red-700 text-white transition-colors text-sm">
                    <i class="fas fa-file-pdf mr-1"></i> Export PDF
                </a>
                <a href="{{ route('librarian.reports.print', 'monthly-summary') }}?year={{ $data['period']['year'] }}&month={{ $data['period']['month'] }}" target="_blank" class="px-3 py-2 rounded-lg bg-purple-600 hover:bg-purple-700 text-white transition-colors text-sm">
                    <i class="fas fa-print mr-1"></i> Generate Printable Report
                </a>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
            <div class="stat-card bg-white border-2 border-blue-600 rounded-xl p-6 shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-blue-600 text-sm font-medium uppercase tracking-wide">Books Borrowed</p>
                        <p class="text-4xl font-bold text-blue-600 mt-2">{{ $data['monthly_stats']['books_borrowed'] }}</p>
                    </div>
                    <div class="bg-blue-100 rounded-full p-4">
                        <i class="fas fa-book text-3xl text-blue-600"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card bg-white border-2 border-green-600 rounded-xl p-6 shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-green-600 text-sm font-medium uppercase tracking-wide">Books Returned</p>
                        <p class="text-4xl font-bold text-green-600 mt-2">{{ $data['monthly_stats']['books_returned'] }}</p>
                    </div>
                    <div class="bg-green-100 rounded-full p-4">
                        <i class="fas fa-undo text-3xl text-green-600"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card bg-white border-2 border-purple-600 rounded-xl p-6 shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-purple-600 text-sm font-medium uppercase tracking-wide">New Students</p>
                        <p class="text-4xl font-bold text-purple-600 mt-2">{{ $data['monthly_stats']['new_students'] }}</p>
                    </div>
                    <div class="bg-purple-100 rounded-full p-4">
                        <i class="fas fa-user-plus text-3xl text-purple-600"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card bg-white border-2 border-orange-600 rounded-xl p-6 shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-orange-600 text-sm font-medium uppercase tracking-wide">Active Students</p>
                        <p class="text-4xl font-bold text-orange-600 mt-2">{{ $data['monthly_stats']['active_students'] }}</p>
                    </div>
                    <div class="bg-orange-100 rounded-full p-4">
                        <i class="fas fa-users text-3xl text-orange-600"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Data Tables Section -->
        <div class="space-y-8">
            <!-- Daily Activity -->
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                <div class="bg-blue-50 px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900 flex items-center">
                        <i class="fas fa-chart-line text-blue-600 mr-3"></i>
                        Daily Activity
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">Borrows and returns per day</p>
                </div>
                <div class="p-6 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-left text-gray-600 border-b border-gray-200 bg-gray-50">
                            <tr>
                                <th class="py-3 pr-4 font-medium">Date</th>
                                <th class="py-3 pr-4 font-medium">Day</th>
                                <th class="py-3 pr-4 font-medium">Borrows</th>
                                <th class="py-3 pr-4 font-medium">Returns</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-900">
                            @foreach($data['daily_stats'] as $row)
                                <tr class="border-b border-gray-100 hover:bg-blue-50 transition-colors">
                                    <td class="py-3 pr-4">{{ $row['date'] }}</td>
                                    <td class="py-3 pr-4">{{ $row['day_name'] }}</td>
                                    <td class="py-3 pr-4 font-semibold text-blue-600">{{ $row['borrows'] }}</td>
                                    <td class="py-3 pr-4 font-semibold text-green-600">{{ $row['returns'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Top Categories -->
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                <div class="bg-purple-50 px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900 flex items-center">
                        <i class="fas fa-tags text-purple-600 mr-3"></i>
                        Top Categories
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">Most borrowed categories this month</p>
                </div>
                <div class="p-6 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-left text-gray-600 border-b border-gray-200 bg-gray-50">
                            <tr>
                                <th class="py-3 pr-4 font-medium">Category</th>
                                <th class="py-3 pr-4 font-medium">Borrow Count</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-900">
                            @forelse($data['top_categories'] as $c)
                                <tr class="border-b border-gray-100 hover:bg-purple-50 transition-colors">
                                    <td class="py-3 pr-4">{{ $c->category ?? '—' }}</td>
                                    <td class="py-3 pr-4 font-semibold text-purple-600">{{ $c->borrow_count }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="py-8 text-center text-gray-500">No category data for this period.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Top Students -->
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                <div class="bg-orange-50 px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900 flex items-center">
                        <i class="fas fa-trophy text-orange-600 mr-3"></i>
                        Top Students
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">Most active borrowers this month</p>
                </div>
                <div class="p-6 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-left text-gray-600 border-b border-gray-200 bg-gray-50">
                            <tr>
                                <th class="py-3 pr-4 font-medium">Name</th>
                                <th class="py-3 pr-4 font-medium">Library ID</th>
                                <th class="py-3 pr-4 font-medium">Course</th>
                                <th class="py-3 pr-4 font-medium">Borrow Count</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-900">
                            @forelse($data['top_students'] as $s)
                                <tr class="border-b border-gray-100 hover:bg-orange-50 transition-colors">
                                    <td class="py-3 pr-4">{{ $s['name'] }}</td>
                                    <td class="py-3 pr-4">{{ $s['library_id'] }}</td>
                                    <td class="py-3 pr-4">{{ $s['course'] }}</td>
                                    <td class="py-3 pr-4 font-semibold text-orange-600">{{ $s['borrow_count'] }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="py-8 text-center text-gray-500">No student activity this month.</td></tr>
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
<script>
    window.addEventListener('load', function() {
        window.print();
        setTimeout(function(){ window.close && window.close(); }, 600);
    });
</script>
@endif
@endsection
