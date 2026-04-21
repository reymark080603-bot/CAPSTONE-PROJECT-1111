@extends('layouts.librarian')

@section('title', 'Monthly Summary')

@section('content')
<div class="p-6 space-y-6">
    <style>
        :root {
            --report-green-900: #1f5b45;
            --report-green-800: #2d6f55;
            --report-green-700: #3f8768;
            --report-green-200: #b9d6be;
            --report-green-100: #dcebd9;
            --report-green-50: #eef6e8;
            --report-cream: #f9f8ef;
            --report-border: #aac9ab;
            --report-text: #224438;
        }

        @page {
            size: A4;
            margin: 12mm;
        }

        .report-shell {
            background:
                linear-gradient(180deg, rgba(220, 235, 217, 0.96), rgba(238, 246, 232, 0.98)),
                radial-gradient(circle at top left, rgba(63, 135, 104, 0.12), transparent 34%),
                radial-gradient(circle at bottom right, rgba(31, 91, 69, 0.12), transparent 30%);
            border: 3px solid var(--report-border);
            box-shadow: 0 18px 35px rgba(31, 91, 69, 0.12);
        }

        .print-report {
            overflow: visible;
        }

        .print-section {
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .print-table-wrap {
            overflow-x: auto;
            overflow-y: visible;
        }

        .print-table {
            width: 100%;
            border-collapse: collapse;
        }

        .print-table thead {
            display: table-header-group;
        }

        .print-table tfoot {
            display: table-footer-group;
        }

        .print-table tr {
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .print-table th,
        .print-table td {
            vertical-align: top;
            word-break: break-word;
        }

        .report-topbar {
            border-bottom: 2px solid rgba(31, 91, 69, 0.18);
        }

        .report-brand {
            color: var(--report-green-900);
            letter-spacing: 0.04em;
        }

        .report-subtitle {
            color: var(--report-green-800);
        }

        .report-period {
            color: var(--report-text);
        }

        .report-summary-card {
            background: rgba(249, 248, 239, 0.88);
            border: 2px solid var(--report-border);
            color: var(--report-text);
        }

        .report-summary-card p:first-child {
            color: var(--report-green-800);
        }

        .report-icon-chip {
            background: rgba(63, 135, 104, 0.12);
            color: var(--report-green-900);
        }

        .report-panel {
            background: rgba(249, 248, 239, 0.94);
            border: 2px solid var(--report-border);
        }

        .report-panel-header {
            background: linear-gradient(180deg, rgba(221, 239, 218, 1), rgba(211, 230, 206, 1));
            border-bottom: 1px solid rgba(31, 91, 69, 0.15);
        }

        .report-panel-header h2,
        .report-panel-header i {
            color: var(--report-green-900);
        }

        .report-panel-header p {
            color: var(--report-green-800);
        }

        .print-table thead {
            background: rgba(185, 214, 190, 0.45);
        }

        .print-table th {
            color: var(--report-green-900);
        }

        .print-table tbody {
            color: var(--report-text);
        }

        .report-row:hover {
            background: rgba(220, 235, 217, 0.45);
        }

        @media print {
            html,
            body {
                background: #fff !important;
                font-size: 12px;
                height: auto !important;
                min-height: 0 !important;
                overflow: visible !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .header,
            .sidebar,
            .sidebar-backdrop,
            .no-print {
                display: none !important;
            }

            .main-content,
            .print-report,
            .print-page,
            .space-y-6,
            .space-y-8 {
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                max-width: none !important;
                min-height: 0 !important;
                height: auto !important;
                overflow: visible !important;
            }

            .print-page {
                box-shadow: none !important;
                border-radius: 0 !important;
            }

            a[href]:after {
                content: "" !important;
            }

            .stat-card,
            .print-section,
            .print-keep-together {
                break-inside: avoid;
                page-break-inside: avoid;
            }

            .print-allow-break {
                break-inside: auto !important;
                page-break-inside: auto !important;
            }

            .print-table-wrap {
                overflow: visible !important;
                max-height: none !important;
                height: auto !important;
            }

            .print-table {
                table-layout: fixed;
            }

            .print-table thead {
                display: table-header-group !important;
            }

            .print-table tfoot {
                display: table-footer-group !important;
            }

            .print-table tbody {
                display: table-row-group;
            }

            .print-table tr {
                break-inside: avoid;
                page-break-inside: avoid;
            }

            .print-table th,
            .print-table td {
                background: transparent !important;
            }

            .print-page-break-before {
                break-before: page;
                page-break-before: always;
            }

            .text-3xl { font-size: 1.5rem !important; }
            .text-2xl { font-size: 1.25rem !important; }
            .text-xl { font-size: 1.125rem !important; }
            .text-4xl { font-size: 2rem !important; }
            .p-6 { padding: 1rem !important; }
            .p-8 { padding: 0 !important; }
            .mb-8 { margin-bottom: 1.25rem !important; }
            .mb-10 { margin-bottom: 1.5rem !important; }
            .gap-6 { gap: 1rem !important; }
            .gap-8 { gap: 1.25rem !important; }

            .grid.grid-cols-1.md\:grid-cols-2.lg\:grid-cols-4 {
                display: grid !important;
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                gap: 1rem !important;
            }
        }

        .stat-card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
    </style>

    <div class="report-shell rounded-[28px] p-8 print-page print-report">
        <div class="report-topbar flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6 mb-8 pb-6 print-keep-together">
            <div>
                <h1 class="report-brand text-3xl font-extrabold flex items-center mb-2 uppercase">
                    <i class="fas fa-calendar-alt mr-4 text-2xl"></i>
                    Knowly
                </h1>
                <p class="report-subtitle text-2xl font-bold uppercase">Monthly Report Activity</p>
                <p class="report-period text-base font-medium mt-1">{{ $data['period']['month_name'] }} | {{ $data['period']['start_date'] }} to {{ $data['period']['end_date'] }}</p>
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

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
            <div class="stat-card report-summary-card rounded-2xl p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium uppercase tracking-wide">Books Borrowed</p>
                        <p class="text-4xl font-bold mt-2">{{ $data['monthly_stats']['books_borrowed'] }}</p>
                    </div>
                    <div class="report-icon-chip rounded-full p-4">
                        <i class="fas fa-book text-3xl"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card report-summary-card rounded-2xl p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium uppercase tracking-wide">Books Returned</p>
                        <p class="text-4xl font-bold mt-2">{{ $data['monthly_stats']['books_returned'] }}</p>
                    </div>
                    <div class="report-icon-chip rounded-full p-4">
                        <i class="fas fa-undo text-3xl"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card report-summary-card rounded-2xl p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium uppercase tracking-wide">New Students</p>
                        <p class="text-4xl font-bold mt-2">{{ $data['monthly_stats']['new_students'] }}</p>
                    </div>
                    <div class="report-icon-chip rounded-full p-4">
                        <i class="fas fa-user-plus text-3xl"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card report-summary-card rounded-2xl p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium uppercase tracking-wide">Active Students</p>
                        <p class="text-4xl font-bold mt-2">{{ $data['monthly_stats']['active_students'] }}</p>
                    </div>
                    <div class="report-icon-chip rounded-full p-4">
                        <i class="fas fa-users text-3xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-8">
            <section class="report-panel rounded-[22px] shadow-sm print-section print-allow-break overflow-hidden print:overflow-visible">
                <div class="report-panel-header px-6 py-4 print-keep-together">
                    <h2 class="text-xl font-semibold flex items-center uppercase">
                        <i class="fas fa-chart-line mr-3"></i>
                        Daily Activity
                    </h2>
                    <p class="text-sm mt-1">Borrows and returns per day</p>
                </div>
                <div class="p-6 print-table-wrap">
                    <table class="min-w-full text-sm print-table">
                        <thead class="text-left border-b border-[#aac9ab]">
                            <tr>
                                <th class="py-3 pr-4 font-medium">Date</th>
                                <th class="py-3 pr-4 font-medium">Day</th>
                                <th class="py-3 pr-4 font-medium">Borrows</th>
                                <th class="py-3 pr-4 font-medium">Returns</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-900">
                            @forelse($data['daily_stats'] as $row)
                                <tr class="report-row border-b border-[#d7e7d5] transition-colors">
                                    <td class="py-3 pr-4">{{ $row['date'] }}</td>
                                    <td class="py-3 pr-4">{{ $row['day_name'] }}</td>
                                    <td class="py-3 pr-4 font-semibold text-[#2d6f55]">{{ $row['borrows'] }}</td>
                                    <td class="py-3 pr-4 font-semibold text-[#3f8768]">{{ $row['returns'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-8 text-center text-gray-500">No daily activity for this month.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="report-panel rounded-[22px] shadow-sm overflow-hidden print-section">
                <div class="report-panel-header px-6 py-4 print-keep-together">
                    <h2 class="text-xl font-semibold flex items-center uppercase">
                        <i class="fas fa-tags mr-3"></i>
                        Top Categories
                    </h2>
                    <p class="text-sm mt-1">Most borrowed categories this month</p>
                </div>
                <div class="p-6 print-table-wrap">
                    <table class="min-w-full text-sm print-table">
                        <thead class="text-left border-b border-[#aac9ab]">
                            <tr>
                                <th class="py-3 pr-4 font-medium">Category</th>
                                <th class="py-3 pr-4 font-medium">Borrow Count</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-900">
                            @forelse($data['top_categories'] as $c)
                                <tr class="report-row border-b border-[#d7e7d5] transition-colors">
                                    <td class="py-3 pr-4">{{ $c->category ?? '-' }}</td>
                                    <td class="py-3 pr-4 font-semibold text-[#2d6f55]">{{ $c->borrow_count }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="py-8 text-center text-gray-500">No category data for this period.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="report-panel rounded-[22px] shadow-sm overflow-hidden print-section">
                <div class="report-panel-header px-6 py-4 print-keep-together">
                    <h2 class="text-xl font-semibold flex items-center uppercase">
                        <i class="fas fa-trophy mr-3"></i>
                        Top Students
                    </h2>
                    <p class="text-sm mt-1">Most active borrowers this month</p>
                </div>
                <div class="p-6 print-table-wrap">
                    <table class="min-w-full text-sm print-table">
                        <thead class="text-left border-b border-[#aac9ab]">
                            <tr>
                                <th class="py-3 pr-4 font-medium">Name</th>
                                <th class="py-3 pr-4 font-medium">Library ID</th>
                                <th class="py-3 pr-4 font-medium">Course</th>
                                <th class="py-3 pr-4 font-medium">Borrow Count</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-900">
                            @forelse($data['top_students'] as $s)
                                <tr class="report-row border-b border-[#d7e7d5] transition-colors">
                                    <td class="py-3 pr-4">{{ $s['name'] }}</td>
                                    <td class="py-3 pr-4">{{ $s['library_id'] }}</td>
                                    <td class="py-3 pr-4">{{ $s['course'] }}</td>
                                    <td class="py-3 pr-4 font-semibold text-[#2d6f55]">{{ $s['borrow_count'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-8 text-center text-gray-500">No student activity this month.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</div>
@endsection

@section('scripts')
@if(request()->boolean('print'))
<script>
    window.addEventListener('load', function() {
        window.print();
        setTimeout(function () {
            window.close && window.close();
        }, 600);
    });
</script>
@endif
@endsection


