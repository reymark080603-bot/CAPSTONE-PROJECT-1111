@extends('layouts.librarian')

@section('title', 'Monthly Report')

@section('content')
@php
    $programDistribution = collect($data['program_distribution'] ?? []);
    $genderDistribution = collect($data['gender_distribution'] ?? []);
    $booksByProgram = collect($data['books_by_program'] ?? []);
    $topProgram = $programDistribution->sortByDesc('student_count')->first();
    $topGender = $genderDistribution->sortByDesc('count')->first();
    $programMax = max(1, (int) $programDistribution->max('student_count'));
    $booksMax = max(1, (int) $booksByProgram->max());
    $genderTotal = max(1, (int) $genderDistribution->sum('count'));
@endphp

<div class="p-6 space-y-6 report-page">
    <style>
        :root {
            --report-bg: #dfead8;
            --report-card: #f8fcf6;
            --report-card-soft: #eef7e9;
            --report-border: #bfd6b9;
            --report-border-strong: #a7c6a1;
            --report-title: #0f5a50;
            --report-subtitle: #365b50;
            --report-teal: #1d6f5f;
            --report-green: #2f855a;
            --report-amber: #d97706;
        }

        .monthly-report-shell {
            background: linear-gradient(180deg, rgba(223, 234, 216, 0.98), rgba(237, 246, 232, 0.98));
            border: 1px solid var(--report-border-strong);
            border-radius: 28px;
            box-shadow: 0 18px 35px rgba(46, 91, 59, 0.08);
            overflow: hidden;
        }

        .monthly-header {55
            background: linear-gradient(135deg, #d9ecd0 0%, #c5e0bf 100%);
            border-bottom: 1px solid var(--report-border-strong);
        }

        .monthly-title {
            color: var(--report-title);
            letter-spacing: 0.04em;
        }

        .monthly-section,
        .monthly-stat,
        .monthly-panel,
        .monthly-note {
            background: var(--report-card);
            border: 1px solid var(--report-border);
            border-radius: 20px;
            box-shadow: 0 6px 14px rgba(46, 91, 59, 0.06);
        }

        .monthly-banner {
            background: #edf6e8;
            border: 1px solid var(--report-border);
            border-radius: 18px;
        }

        .monthly-section-title {
            color: var(--report-title);
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .monthly-stat {
            background: linear-gradient(180deg, #ffffff 0%, var(--report-card-soft) 100%);
        }

        .monthly-grid-pair {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1.5rem;
        }

        .monthly-summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 1rem;
        }

        .monthly-summary-value {
            font-size: 2.2rem;
            line-height: 1;
        }

        .monthly-section-copy {
            color: var(--report-subtitle);
            font-size: 0.9rem;
            margin-top: 0.35rem;
            margin-bottom: 1rem;
        }

        .monthly-bar-track {
            background: #dcead7;
            border-radius: 9999px;
            overflow: hidden;
        }

        .monthly-bar-fill {
            height: 100%;
            border-radius: 9999px;
        }

        .monthly-note {
            background: #edf7eb;
        }

        .monthly-action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: 42px;
            padding: 0.5rem 1rem;
            border-radius: 0.65rem;
            border: 2px solid transparent;
            font-size: 0.875rem;
            font-weight: 700;
            letter-spacing: 0.01em;
            box-shadow: 0 6px 14px rgba(46, 91, 59, 0.08);
            white-space: nowrap;
        }

        .monthly-filter-select {
            min-height: 42px;
            border-radius: 0.75rem;
            border: 1px solid #cbd5e1;
            background: #ffffff;
        }

        .monthly-actions-wrap {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 0.5rem;
            margin-left: auto;
        }

        .monthly-filters-wrap {
            margin-left: auto;
        }

        .gender-visual {
            width: 70px;
            height: 70px;
            border-radius: 9999px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.75rem;
            border: 8px solid #dcead7;
            background: linear-gradient(180deg, #ffffff 0%, #eef8ea 100%);
            color: var(--report-title);
            font-size: 1.5rem;
        }

        .monthly-data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .monthly-data-table th {
            text-align: left;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--report-title);
            border-bottom: 1px solid var(--report-border);
            padding: 0.75rem 0.85rem;
        }

        .monthly-data-table td {
            padding: 0.75rem 0.85rem;
            border-bottom: 1px solid #d7e7d5;
            color: #374151;
        }

        .monthly-data-table tr:last-child td {
            border-bottom: none;
        }

        @media (max-width: 1279px) {
            .monthly-summary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 1023px) {
            .monthly-grid-pair {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 767px) {
            .monthly-summary-grid {
                grid-template-columns: 1fr;
            }
        }

        @media print {
            html,
            body {
                background: #ffffff !important;
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
            .report-page,
            .monthly-report-shell {
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
                border-radius: 0 !important;
                width: 100% !important;
                max-width: none !important;
            }

            .monthly-report-shell {
                border: none !important;
            }

            .monthly-section,
            .monthly-stat,
            .monthly-panel,
            .monthly-note {
                break-inside: avoid;
                page-break-inside: avoid;
            }
        }
    </style>

    <div class="monthly-report-shell p-6 md:p-8">
        <div class="monthly-header rounded-[22px] p-6 md:p-8 mb-6">
            <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-6">
                <div>
                    <p class="text-sm font-bold uppercase tracking-[0.2em] text-[#21584a] mb-1">Monthly Library Report</p>
                    <h1 class="monthly-title text-3xl md:text-4xl font-extrabold uppercase">JHSCS KNOWLY</h1>
                    <h2 class="text-xl md:text-2xl font-extrabold uppercase text-[var(--report-teal)] mt-1">College Library</h2>
                    <p class="text-sm md:text-base text-[var(--report-subtitle)] mt-2">
                        {{ $data['period']['month_name'] }} | {{ $data['period']['start_date'] }} to {{ $data['period']['end_date'] }}
                    </p>
                </div>
                <div class="monthly-actions-wrap no-print lg:max-w-[440px]">
                    <a href="{{ route('librarian.reports.index') }}" class="monthly-action-btn border border-slate-300 bg-white text-slate-700 hover:bg-slate-50">
                        <i class="fas fa-arrow-left mr-1"></i> Back
                    </a>
                    <a href="{{ route('librarian.reports.export', 'monthly-summary') }}?year={{ $data['period']['year'] }}&month={{ $data['period']['month'] }}&format=pdf" class="monthly-action-btn border border-red-700 bg-red-600 hover:bg-red-700 text-white">
                        <i class="fas fa-file-pdf mr-1"></i> Export PDF
                    </a>
                    <a href="{{ route('librarian.reports.print', 'monthly-summary') }}?year={{ $data['period']['year'] }}&month={{ $data['period']['month'] }}" target="_blank" class="monthly-action-btn border-2 border-[var(--report-title)] bg-white text-[var(--report-title)] hover:bg-[#eef8ea]">
                        <i class="fas fa-print mr-1"></i> Generate Monthly Report
                    </a>
                </div>
            </div>
        </div>

        <form method="GET" action="{{ route('librarian.reports.monthly-summary') }}" class="monthly-banner p-4 md:p-5 mb-6 no-print">
            <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">
                <div class="flex-1">
                    <h3 class="monthly-section-title text-lg">Monthly Report Filters</h3>
                    <p class="text-sm text-[var(--report-subtitle)] mt-1">Select the month and year you want to generate.</p>
                </div>
                <div class="monthly-filters-wrap grid grid-cols-1 sm:grid-cols-3 gap-3 w-full lg:w-auto lg:min-w-[520px]">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Month</label>
                        <select name="month" class="monthly-filter-select w-full px-3 py-2">
                            @for($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" {{ (int) $data['period']['month'] === $m ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::create()->month($m)->format('F') }}
                                </option>
                            @endfor
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Year</label>
                        <select name="year" class="monthly-filter-select w-full px-3 py-2">
                            @for($y = now()->year; $y >= now()->year - 5; $y--)
                                <option value="{{ $y }}" {{ (int) $data['period']['year'] === $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="monthly-action-btn w-full border-2 border-[var(--report-green)] bg-white text-[var(--report-green)] hover:bg-[#eef8ea]">
                            <i class="fas fa-chart-line mr-1"></i> View Report
                        </button>
                    </div>
                </div>
            </div>
        </form>

        <div class="monthly-banner p-4 md:p-5 mb-6">
            <h3 class="monthly-section-title text-xl">{{ strtoupper($data['period']['month_name']) }} Statistics</h3>
            <p class="text-sm text-[var(--report-subtitle)] mt-2">
                This monthly report presents library borrowing activity, student participation by program, collection distribution, and demographic trends for the selected reporting period.
            </p>
        </div>

        <div class="monthly-summary-grid mb-6">
            <div class="monthly-stat p-5">
                <p class="text-sm font-semibold uppercase tracking-wide text-[var(--report-subtitle)]">Books Borrowed</p>
                <p class="monthly-summary-value font-extrabold text-[var(--report-teal)] mt-2">{{ $data['monthly_stats']['books_borrowed'] }}</p>
            </div>
            <div class="monthly-stat p-5">
                <p class="text-sm font-semibold uppercase tracking-wide text-[var(--report-subtitle)]">Books Returned</p>
                <p class="monthly-summary-value font-extrabold text-[var(--report-green)] mt-2">{{ $data['monthly_stats']['books_returned'] }}</p>
            </div>
            <div class="monthly-stat p-5">
                <p class="text-sm font-semibold uppercase tracking-wide text-[var(--report-subtitle)]">New Students</p>
                <p class="monthly-summary-value font-extrabold text-[var(--report-amber)] mt-2">{{ $data['monthly_stats']['new_students'] }}</p>
            </div>
            <div class="monthly-stat p-5">
                <p class="text-sm font-semibold uppercase tracking-wide text-[var(--report-subtitle)]">Active Students</p>
                <p class="monthly-summary-value font-extrabold text-[var(--report-title)] mt-2">{{ $data['monthly_stats']['active_students'] }}</p>
            </div>
        </div>

        <div class="monthly-grid-pair mb-6">
            <div class="monthly-panel p-5">
                <h3 class="monthly-section-title text-lg mb-4">Report Highlights</h3>
                <p class="text-sm text-gray-700">
                    <strong>{{ $data['monthly_stats']['books_borrowed'] + $data['monthly_stats']['books_returned'] + $data['monthly_stats']['new_students'] }}</strong>
                    total monthly transactions were recorded from borrowed books, returned books, and new student registrations.
                </p>
                <p class="text-sm text-gray-700 mt-3">
                    <strong>{{ $data['monthly_stats']['active_students'] }}</strong> active students used the library this month,
                    while <strong>{{ $data['monthly_stats']['overdue_books'] }}</strong> overdue records remained open during the same period.
                </p>
            </div>
            <div class="monthly-panel p-5">
                <h3 class="monthly-section-title text-lg mb-4">Demographic Notes</h3>
                <p class="text-sm text-gray-700">
                    @if($topProgram)
                        <strong>{{ $topProgram['program'] }}</strong> recorded the highest student participation with
                        <strong>{{ $topProgram['student_count'] }}</strong> active student borrowers.
                    @else
                        No program participation data is available for this period.
                    @endif
                </p>
                <p class="text-sm text-gray-700 mt-3">
                    @if($topGender)
                        <strong>{{ ucfirst($topGender['gender']) }}</strong> represents the largest gender group for the month with
                        <strong>{{ $topGender['count'] }}</strong> active students.
                    @else
                        No gender distribution data is available for this period.
                    @endif
                </p>
            </div>
        </div>

        <div class="monthly-grid-pair mb-6">
            <div class="monthly-section p-5">
                <h3 class="monthly-section-title text-lg mb-4">Students Per Program</h3>
                <p class="monthly-section-copy">Student participation by program for the selected month.</p>
                <div class="space-y-4">
                    @forelse($programDistribution as $program)
                        <div>
                            <div class="flex items-center justify-between text-sm font-medium text-gray-700 mb-1">
                                <span>{{ $program['program'] }}</span>
                                <span>{{ $program['student_count'] }}</span>
                            </div>
                            <div class="monthly-bar-track h-3">
                                <div class="monthly-bar-fill bg-[var(--report-green)]" style="width: {{ ($program['student_count'] / $programMax) * 100 }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">No program distribution data for this month.</p>
                    @endforelse
                </div>
            </div>

            <div class="monthly-section p-5">
                <h3 class="monthly-section-title text-lg mb-4">Books Distribution By Program</h3>
                <p class="monthly-section-copy">Collection distribution grouped by program assignment.</p>
                <div class="space-y-4">
                    @forelse($booksByProgram as $program => $count)
                        <div>
                            <div class="flex items-center justify-between text-sm font-medium text-gray-700 mb-1">
                                <span>{{ $program }}</span>
                                <span>{{ $count }}</span>
                            </div>
                            <div class="monthly-bar-track h-3">
                                <div class="monthly-bar-fill bg-[var(--report-teal)]" style="width: {{ ($count / $booksMax) * 100 }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">No book distribution data available.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="monthly-grid-pair mb-6">
            <div class="monthly-section p-5">
                <h3 class="monthly-section-title text-lg mb-4">Student Gender Distribution</h3>
                <p class="monthly-section-copy">Active student borrowers grouped by recorded gender.</p>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    @forelse($genderDistribution as $gender)
                        @php
                            $percentage = ($gender['count'] / $genderTotal) * 100;
                            $normalizedGender = strtolower(trim($gender['gender']));
                            $genderIcon = match ($normalizedGender) {
                                'female' => 'fa-venus',
                                'male' => 'fa-mars',
                                default => 'fa-users',
                            };
                        @endphp
                        <div class="monthly-stat p-4 text-center">
                            <div class="gender-visual">
                                <i class="fas {{ $genderIcon }}"></i>
                            </div>
                            <p class="text-sm font-semibold uppercase tracking-wide text-[var(--report-subtitle)]">{{ ucfirst($gender['gender']) }}</p>
                            <p class="text-3xl font-extrabold text-[var(--report-title)] mt-2">{{ $gender['count'] }}</p>
                            <p class="text-xs text-gray-500 mt-1">{{ number_format($percentage, 1) }}% of active students</p>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 col-span-full">No gender data available for this month.</p>
                    @endforelse
                </div>
            </div>

            <div class="monthly-section p-5">
                <h3 class="monthly-section-title text-lg mb-4">Top Categories</h3>
                <p class="monthly-section-copy">Most borrowed library categories for the month.</p>
                <div class="overflow-x-auto">
                    <table class="monthly-data-table text-sm">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Borrow Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($data['top_categories'] as $category)
                                <tr>
                                    <td>{{ $category->category ?? '-' }}</td>
                                    <td class="font-semibold text-[var(--report-green)]">{{ $category->borrow_count }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-center text-gray-500">No category data for this month.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="monthly-grid-pair mb-6">
            <div class="monthly-section p-5">
                <h3 class="monthly-section-title text-lg mb-4">Top Student Borrowers</h3>
                <p class="monthly-section-copy">Students with the highest borrowing activity during the month.</p>
                <div class="overflow-x-auto">
                    <table class="monthly-data-table text-sm">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Library ID</th>
                                <th>Course</th>
                                <th>Borrow Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($data['top_students'] as $student)
                                <tr>
                                    <td>{{ $student['name'] }}</td>
                                    <td>{{ $student['library_id'] }}</td>
                                    <td>{{ $student['course'] }}</td>
                                    <td class="font-semibold text-[var(--report-green)]">{{ $student['borrow_count'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-gray-500">No student activity this month.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="monthly-section p-5">
                <h3 class="monthly-section-title text-lg mb-4">Daily Library Activity</h3>
                <p class="monthly-section-copy">Daily borrowing and return counts across the reporting period.</p>
                <div class="overflow-x-auto">
                    <table class="monthly-data-table text-sm">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Day</th>
                                <th>Borrows</th>
                                <th>Returns</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($data['daily_stats'] as $row)
                                <tr>
                                    <td>{{ $row['date'] }}</td>
                                    <td>{{ $row['day_name'] }}</td>
                                    <td class="font-semibold text-[var(--report-teal)]">{{ $row['borrows'] }}</td>
                                    <td class="font-semibold text-[var(--report-green)]">{{ $row['returns'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-gray-500">No daily activity for this month.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="monthly-note p-4">
            <p class="text-sm text-gray-700">
                <strong class="text-[var(--report-title)]">Interpretation:</strong>
                This monthly report summarizes the selected period's borrowing activity, active student participation, collection distribution, and key library usage patterns to support planning and reporting.
            </p>
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
