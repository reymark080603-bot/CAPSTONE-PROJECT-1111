@extends('layouts.librarian')

@section('title', 'Popular Books')

@section('content')
<div class="p-6 space-y-6">
    <style>
        :root { --report-green-900:#1f5b45; --report-green-800:#2d6f55; --report-border:#aac9ab; --report-text:#224438; }
        @page { size:A4; margin:12mm; }
        .report-shell { background:linear-gradient(180deg, rgba(220,235,217,.96), rgba(238,246,232,.98)); border:3px solid var(--report-border); box-shadow:0 18px 35px rgba(31,91,69,.12); }
        .report-topbar { border-bottom:2px solid rgba(31,91,69,.18); }
        .report-brand { color:var(--report-green-900); letter-spacing:.04em; }
        .report-subtitle { color:var(--report-green-800); }
        .report-period { color:var(--report-text); }
        .report-panel { background:rgba(249,248,239,.94); border:2px solid var(--report-border); }
        .report-panel-header { background:linear-gradient(180deg, rgba(221,239,218,1), rgba(211,230,206,1)); border-bottom:1px solid rgba(31,91,69,.15); }
        .report-panel-header h2, .report-panel-header i { color:var(--report-green-900); }
        .report-panel-header p { color:var(--report-green-800); }
        .print-report, .print-table-wrap { overflow:visible; }
        .print-section { break-inside:avoid; page-break-inside:avoid; }
        .print-table { width:100%; border-collapse:collapse; }
        .print-table thead { display:table-header-group; background:rgba(185,214,190,.45); }
        .print-table tr { break-inside:avoid; page-break-inside:avoid; }
        .print-table th, .print-table td { vertical-align:top; word-break:break-word; }
        .print-table th { color:var(--report-green-900); }
        .print-table tbody { color:var(--report-text); }
        .report-row:hover { background:rgba(220,235,217,.45); }
        .report-action-btn { display:inline-flex; align-items:center; justify-content:center; height:42px; padding:.5rem 1rem; border-radius:.65rem; border:2px solid transparent; font-size:.875rem; font-weight:700; letter-spacing:.01em; box-shadow:0 6px 14px rgba(46, 91, 59, 0.08); white-space:nowrap; }
        @if(request()->boolean('pdf'))
        .no-print { display:none !important; }
        @endif
        @media print { html, body { background:#fff !important; font-size:12px; height:auto !important; min-height:0 !important; overflow:visible !important; -webkit-print-color-adjust:exact; print-color-adjust:exact; } .header, .sidebar, .sidebar-backdrop, .no-print { display:none !important; } .main-content, .print-report, .print-page, .space-y-6, .space-y-8 { margin:0 !important; padding:0 !important; width:100% !important; max-width:none !important; min-height:0 !important; height:auto !important; overflow:visible !important; } .print-page { box-shadow:none !important; border-radius:0 !important; } .print-table-wrap { overflow:visible !important; max-height:none !important; height:auto !important; } .print-table { table-layout:fixed; } .print-table thead { display:table-header-group !important; } .print-table tbody { display:table-row-group; } .grid.grid-cols-1.lg\:grid-cols-2 { display:grid !important; grid-template-columns:repeat(2, minmax(0, 1fr)) !important; gap:1rem !important; } }
    </style>

    <div class="report-shell rounded-[28px] p-8 print-page print-report">
        <div class="report-topbar flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6 mb-8 pb-6">
            <div><h1 class="report-brand text-3xl font-extrabold flex items-center mb-2 uppercase"><i class="fas fa-star mr-4 text-2xl"></i>Knowly</h1><p class="report-subtitle text-2xl font-bold uppercase">Popular Books Report</p><p class="report-period text-base font-medium mt-1">{{ $data['period']['from'] }} to {{ $data['period']['to'] }}</p></div>
            <div class="flex w-full flex-col gap-3 sm:w-auto sm:flex-row sm:flex-wrap sm:items-center sm:justify-end no-print"><a href="{{ route('librarian.reports.index') }}" class="report-action-btn border border-slate-300 bg-white text-slate-700 hover:bg-slate-50"><i class="fas fa-arrow-left mr-1"></i><span>Back</span></a><a href="{{ route('librarian.reports.export', 'popular-books') }}?date_from={{ $data['period']['from'] }}&date_to={{ $data['period']['to'] }}&format=pdf" class="report-action-btn border border-red-700 bg-red-600 text-white hover:bg-red-700"><i class="fas fa-file-pdf mr-1"></i><span>PDF</span></a><a href="{{ route('librarian.reports.print', 'popular-books') }}?date_from={{ $data['period']['from'] }}&date_to={{ $data['period']['to'] }}" target="_blank" class="report-action-btn border-2 border-[#1f5b45] bg-white text-[#1f5b45] hover:bg-[#eef8ea]"><i class="fas fa-print mr-1"></i><span>Generate Printable Report</span></a></div>
        </div>

        <section class="report-panel rounded-[22px] shadow-sm print-section"><div class="report-panel-header px-6 py-4"><h2 class="text-xl font-semibold flex items-center uppercase"><i class="fas fa-book mr-3"></i>Top Books</h2><p class="text-sm mt-1">Trending and most requested titles</p></div><div class="p-6 print-table-wrap"><table class="min-w-full text-sm print-table"><thead class="text-left border-b border-[#aac9ab]"><tr><th class="py-2 pr-4">Title</th><th class="py-2 pr-4">Author</th><th class="py-2 pr-4">Category</th><th class="py-2 pr-4">Course</th><th class="py-2 pr-4">Borrow Count</th><th class="py-2 pr-4">Unique Borrowers</th></tr></thead><tbody>@forelse($data['popular_books'] as $b)<tr class="report-row border-b border-[#d7e7d5]"><td class="py-2 pr-4">{{ $b->title }}</td><td class="py-2 pr-4">{{ $b->author }}</td><td class="py-2 pr-4">{{ $b->category }}</td><td class="py-2 pr-4">{{ $b->course }}</td><td class="py-2 pr-4 font-medium text-[#2d6f55]">{{ $b->borrow_count }}</td><td class="py-2 pr-4">{{ $b->unique_borrowers }}</td></tr>@empty<tr><td colspan="6" class="py-4 text-gray-500">No data.</td></tr>@endforelse</tbody></table></div></section>

        <div class="mt-8 grid grid-cols-1 lg:grid-cols-2 gap-6">
            <section class="report-panel rounded-[22px] shadow-sm print-section"><div class="report-panel-header px-6 py-4"><h2 class="text-xl font-semibold flex items-center uppercase"><i class="fas fa-tags mr-3"></i>Popular by Category</h2><p class="text-sm mt-1">Category borrowing trends</p></div><div class="p-6 print-table-wrap"><table class="min-w-full text-sm print-table"><thead class="text-left border-b border-[#aac9ab]"><tr><th class="py-2 pr-4">Category</th><th class="py-2 pr-4">Borrows</th><th class="py-2 pr-4">Unique Books</th></tr></thead><tbody>@forelse($data['popular_by_category'] as $c)<tr class="report-row border-b border-[#d7e7d5]"><td class="py-2 pr-4">{{ $c->category ?? '-' }}</td><td class="py-2 pr-4 font-medium text-[#2d6f55]">{{ $c->total_borrows }}</td><td class="py-2 pr-4">{{ $c->unique_books }}</td></tr>@empty<tr><td colspan="3" class="py-4 text-gray-500">No data.</td></tr>@endforelse</tbody></table></div></section>
            <section class="report-panel rounded-[22px] shadow-sm print-section"><div class="report-panel-header px-6 py-4"><h2 class="text-xl font-semibold flex items-center uppercase"><i class="fas fa-graduation-cap mr-3"></i>Popular by Course</h2><p class="text-sm mt-1">Course-level borrowing trends</p></div><div class="p-6 print-table-wrap"><table class="min-w-full text-sm print-table"><thead class="text-left border-b border-[#aac9ab]"><tr><th class="py-2 pr-4">Course</th><th class="py-2 pr-4">Borrows</th><th class="py-2 pr-4">Unique Books</th></tr></thead><tbody>@forelse($data['popular_by_course'] as $c)<tr class="report-row border-b border-[#d7e7d5]"><td class="py-2 pr-4">{{ $c->course ?? '-' }}</td><td class="py-2 pr-4 font-medium text-[#2d6f55]">{{ $c->total_borrows }}</td><td class="py-2 pr-4">{{ $c->unique_books }}</td></tr>@empty<tr><td colspan="3" class="py-4 text-gray-500">No data.</td></tr>@endforelse</tbody></table></div></section>
        </div>
    </div>
</div>
@endsection

@section('scripts')
@if(request()->boolean('print'))
<script>window.addEventListener('load',()=>{window.print(); setTimeout(()=>{window.close&&window.close()},600)})</script>
@endif
@endsection
