@extends('layouts.librarian')

@section('title', 'Borrowing Statistics')

@section('content')
<div class="p-6 space-y-6">
    <style>
        :root {
            --report-green-900: #1f5b45;
            --report-green-800: #2d6f55;
            --report-green-700: #3f8768;
            --report-green-200: #b9d6be;
            --report-green-100: #dcebd9;
            --report-cream: #f9f8ef;
            --report-border: #aac9ab;
            --report-text: #224438;
        }
        @page { size: A4; margin: 12mm; }
        .report-shell { background: linear-gradient(180deg, rgba(220, 235, 217, 0.96), rgba(238, 246, 232, 0.98)); border: 3px solid var(--report-border); box-shadow: 0 18px 35px rgba(31, 91, 69, 0.12); }
        .report-topbar { border-bottom: 2px solid rgba(31, 91, 69, 0.18); }
        .report-brand { color: var(--report-green-900); letter-spacing: 0.04em; }
        .report-subtitle { color: var(--report-green-800); }
        .report-period { color: var(--report-text); }
        .report-summary-card { background: rgba(249, 248, 239, 0.88); border: 2px solid var(--report-border); color: var(--report-text); }
        .report-summary-card p:first-child { color: var(--report-green-800); }
        .report-icon-chip { background: rgba(63, 135, 104, 0.12); color: var(--report-green-900); }
        .report-panel { background: rgba(249, 248, 239, 0.94); border: 2px solid var(--report-border); }
        .report-panel-header { background: linear-gradient(180deg, rgba(221, 239, 218, 1), rgba(211, 230, 206, 1)); border-bottom: 1px solid rgba(31, 91, 69, 0.15); }
        .report-panel-header h2, .report-panel-header i { color: var(--report-green-900); }
        .report-panel-header p { color: var(--report-green-800); }
        .print-report { overflow: visible; }
        .print-section, .print-keep-together, .report-summary-card { break-inside: avoid; page-break-inside: avoid; }
        .print-table-wrap { overflow-x: auto; overflow-y: visible; }
        .print-table { width: 100%; border-collapse: collapse; }
        .print-table thead { display: table-header-group; background: rgba(185, 214, 190, 0.45); }
        .print-table tr { break-inside: avoid; page-break-inside: avoid; }
        .print-table th, .print-table td { vertical-align: top; word-break: break-word; }
        .print-table th { color: var(--report-green-900); }
        .print-table tbody { color: var(--report-text); }
        .report-row:hover { background: rgba(220, 235, 217, 0.45); }
        @media print {
            html, body { background: #fff !important; font-size: 12px; height: auto !important; min-height: 0 !important; overflow: visible !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .header, .sidebar, .sidebar-backdrop, .no-print { display: none !important; }
            .main-content, .print-report, .print-page, .space-y-6, .space-y-8 { margin: 0 !important; padding: 0 !important; width: 100% !important; max-width: none !important; min-height: 0 !important; height: auto !important; overflow: visible !important; }
            .print-page { box-shadow: none !important; border-radius: 0 !important; }
            .print-table-wrap { overflow: visible !important; max-height: none !important; height: auto !important; }
            .print-table { table-layout: fixed; }
            .print-table thead { display: table-header-group !important; }
            .print-table tbody { display: table-row-group; }
            .grid.grid-cols-1.md\:grid-cols-3 { display: grid !important; grid-template-columns: repeat(3, minmax(0, 1fr)) !important; gap: 1rem !important; }
            .grid.grid-cols-1.lg\:grid-cols-2 { display: grid !important; grid-template-columns: repeat(2, minmax(0, 1fr)) !important; gap: 1rem !important; }
        }
    </style>

    <div class="report-shell rounded-[28px] p-8 print-page print-report">
        <div class="report-topbar flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6 mb-8 pb-6 print-keep-together">
            <div>
                <h1 class="report-brand text-3xl font-extrabold flex items-center mb-2 uppercase"><i class="fas fa-chart-line mr-4 text-2xl"></i>Knowly</h1>
                <p class="report-subtitle text-2xl font-bold uppercase">Borrowing Statistics Report</p>
                <p class="report-period text-base font-medium mt-1">{{ $data['period']['from'] }} to {{ $data['period']['to'] }}</p>
            </div>
            <div class="flex w-full flex-col gap-3 sm:w-auto sm:flex-row sm:flex-wrap sm:items-center sm:justify-end no-print">
                <a href="{{ route('librarian.reports.index') }}" class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-[#c8d8c5] bg-white/55 px-5 py-3 text-base font-medium text-[#355246] shadow-sm transition-colors hover:bg-white sm:w-auto sm:justify-start lg:text-lg"><i class="fas fa-arrow-left"></i><span>Back</span></a>
                <a href="{{ route('librarian.reports.print', 'borrowing-statistics') }}?date_from={{ $data['period']['from'] }}&date_to={{ $data['period']['to'] }}" target="_blank" class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-black bg-[#1f5b45] px-5 py-3 text-base font-semibold text-black shadow-sm transition-colors hover:bg-[#174835] sm:w-auto sm:justify-start lg:text-lg"><i class="fas fa-print"></i><span>Generate Printable Report</span></a>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
            <div class="report-summary-card rounded-2xl p-4"><div class="flex items-center justify-between gap-3"><div><p class="text-sm uppercase tracking-wide">Total Borrows</p><p class="text-3xl font-bold">{{ $data['summary']['total_borrows'] }}</p></div><div class="report-icon-chip rounded-full p-4"><i class="fas fa-book text-2xl"></i></div></div></div>
            <div class="report-summary-card rounded-2xl p-4"><div class="flex items-center justify-between gap-3"><div><p class="text-sm uppercase tracking-wide">Total Returns</p><p class="text-3xl font-bold">{{ $data['summary']['total_returns'] }}</p></div><div class="report-icon-chip rounded-full p-4"><i class="fas fa-undo text-2xl"></i></div></div></div>
            <div class="report-summary-card rounded-2xl p-4"><div class="flex items-center justify-between gap-3"><div><p class="text-sm uppercase tracking-wide">Average Per Day</p><p class="text-3xl font-bold">{{ $data['summary']['average_per_day'] }}</p></div><div class="report-icon-chip rounded-full p-4"><i class="fas fa-calendar-day text-2xl"></i></div></div></div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <section class="report-panel rounded-[22px] shadow-sm print-section"><div class="report-panel-header px-6 py-4 print-keep-together"><h2 class="text-xl font-semibold flex items-center uppercase"><i class="fas fa-chart-bar mr-3"></i>Monthly Breakdown</h2><p class="text-sm mt-1">Monthly borrowing activity summary</p></div><div class="p-6 print-table-wrap"><table class="min-w-full text-sm print-table"><thead class="text-left border-b border-[#aac9ab]"><tr><th class="py-2 pr-4">Period</th><th class="py-2 pr-4">Count</th></tr></thead><tbody>@forelse($data['monthly_data'] as $m)<tr class="report-row border-b border-[#d7e7d5]"><td class="py-2 pr-4">{{ $m['period'] }}</td><td class="py-2 pr-4 font-medium text-[#2d6f55]">{{ $m['count'] }}</td></tr>@empty<tr><td colspan="2" class="py-4 text-gray-500">No data.</td></tr>@endforelse</tbody></table></div></section>
            <section class="report-panel rounded-[22px] shadow-sm print-section"><div class="report-panel-header px-6 py-4 print-keep-together"><h2 class="text-xl font-semibold flex items-center uppercase"><i class="fas fa-tags mr-3"></i>Category Breakdown</h2><p class="text-sm mt-1">Borrowing grouped by category</p></div><div class="p-6 print-table-wrap"><table class="min-w-full text-sm print-table"><thead class="text-left border-b border-[#aac9ab]"><tr><th class="py-2 pr-4">Category</th><th class="py-2 pr-4">Count</th></tr></thead><tbody>@forelse($data['category_data'] as $c)<tr class="report-row border-b border-[#d7e7d5]"><td class="py-2 pr-4">{{ $c->category ?? '-' }}</td><td class="py-2 pr-4 font-medium text-[#2d6f55]">{{ $c->count }}</td></tr>@empty<tr><td colspan="2" class="py-4 text-gray-500">No data.</td></tr>@endforelse</tbody></table></div></section>
        </div>
    </div>
</div>
@endsection

@section('scripts')
@if(request()->boolean('print'))
<script>window.addEventListener('load',()=>{window.print(); setTimeout(()=>{window.close&&window.close()},600)})</script>
@endif
@endsection
