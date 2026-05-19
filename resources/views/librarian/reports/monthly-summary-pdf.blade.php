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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monthly Report - {{ $data['period']['month_name'] }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 10mm;
        }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            margin: 0;
            padding: 14px;
            color: #1f2937;
            background: #ffffff;
            font-size: 12px;
            line-height: 1.45;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .shell {
            background: #dfead8;
            border: 1px solid #a7c6a1;
            border-radius: 18px;
            padding: 16px;
            overflow: visible;
        }

        .header {
            background: #d9ecd0;
            border: 1px solid #a7c6a1;
            border-radius: 16px;
            padding: 18px;
            text-align: center;
            margin-bottom: 12px;
            page-break-inside: avoid;
        }

        .eyebrow {
            font-size: 11px;
            font-weight: bold;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            color: #21584a;
            margin-bottom: 4px;
        }

        .header h1 {
            margin: 0;
            font-size: 26px;
            text-transform: uppercase;
            color: #123f38;
        }

        .header h2 {
            margin: 4px 0 8px;
            font-size: 16px;
            text-transform: uppercase;
            color: #1d6f5f;
        }

        .header p {
            margin: 0;
            color: #365b50;
        }

        .banner,
        .panel,
        .note {
            background: #f8fcf6;
            border: 1px solid #bfd6b9;
            border-radius: 14px;
            padding: 14px;
            margin-bottom: 12px;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .banner {
            background: #edf6e8;
        }

        .section-title {
            margin: 0 0 10px;
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            color: #0f5a50;
        }

        .summary-grid,
        .two-col,
        .mini-grid {
            width: 100%;
            border-collapse: separate;
            border-spacing: 10px 0;
            margin: 0 -10px 12px;
        }

        .summary-card,
        .mini-card {
            background: #ffffff;
            border: 1px solid #d4e4ce;
            border-radius: 12px;
            padding: 12px;
            text-align: center;
        }

        .summary-label,
        .mini-label {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            color: #4b635b;
        }

        .summary-value {
            font-size: 24px;
            font-weight: bold;
            margin-top: 6px;
            color: #0f5a50;
        }

        .bar-row {
            margin-bottom: 10px;
        }

        .bar-meta {
            overflow: hidden;
            margin-bottom: 3px;
        }

        .bar-meta .left {
            float: left;
        }

        .bar-meta .right {
            float: right;
            font-weight: bold;
        }

        .bar-track {
            clear: both;
            height: 10px;
            border-radius: 999px;
            background: #dcead7;
            overflow: hidden;
        }

        .bar-fill-green,
        .bar-fill-teal {
            height: 10px;
            border-radius: 999px;
        }

        .bar-fill-green { background: #2f855a; }
        .bar-fill-teal { background: #1d6f5f; }

        .gender-icon {
            width: 46px;
            height: 46px;
            margin: 0 auto 8px;
            border-radius: 999px;
            border: 6px solid #dcead7;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #0f5a50;
            font-weight: bold;
            font-size: 16px;
            background: #ffffff;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
            page-break-inside: auto;
        }

        table.data-table thead {
            display: table-header-group;
        }

        table.data-table tr {
            page-break-inside: avoid;
            break-inside: avoid;
        }

        table.data-table th,
        table.data-table td {
            border-bottom: 1px solid #d7e7d5;
            padding: 7px 6px;
            text-align: left;
            vertical-align: top;
        }

        table.data-table th {
            color: #0f5a50;
            font-size: 11px;
            text-transform: uppercase;
        }

        .note {
            background: #edf7eb;
            color: #3e5e54;
        }

        .footer {
            margin-top: 12px;
            text-align: center;
            font-size: 11px;
            color: #4d665d;
        }
    </style>
</head>
<body>
    <div class="shell">
        <div class="header">
            <div class="eyebrow">Monthly Library Report</div>
            <h1>JHCSC KNOWLY</h1>
            <h2>College Library</h2>
            <p>{{ $data['period']['month_name'] }} | {{ $data['period']['start_date'] }} to {{ $data['period']['end_date'] }}</p>
        </div>

        <div class="banner">
            <div class="section-title">{{ strtoupper($data['period']['month_name']) }} Statistics</div>
            <div>This monthly report presents library borrowing activity, student participation by program, collection distribution, and demographic trends for the selected reporting period.</div>
        </div>

        <table class="summary-grid">
            <tr>
                <td width="25%"><div class="summary-card"><div class="summary-label">Books Borrowed</div><div class="summary-value">{{ $data['monthly_stats']['books_borrowed'] }}</div></div></td>
                <td width="25%"><div class="summary-card"><div class="summary-label">Books Returned</div><div class="summary-value">{{ $data['monthly_stats']['books_returned'] }}</div></div></td>
                <td width="25%"><div class="summary-card"><div class="summary-label">New Students</div><div class="summary-value">{{ $data['monthly_stats']['new_students'] }}</div></div></td>
                <td width="25%"><div class="summary-card"><div class="summary-label">Active Students</div><div class="summary-value">{{ $data['monthly_stats']['active_students'] }}</div></div></td>
            </tr>
        </table>

        <table class="two-col">
            <tr>
                <td width="50%" valign="top">
                    <div class="panel">
                        <div class="section-title">Report Highlights</div>
                        <div><strong>{{ $data['monthly_stats']['books_borrowed'] + $data['monthly_stats']['books_returned'] + $data['monthly_stats']['new_students'] }}</strong> total monthly transactions were recorded from borrowed books, returned books, and new student registrations.</div>
                        <div style="margin-top:8px;"><strong>{{ $data['monthly_stats']['active_students'] }}</strong> active students used the library this month, while <strong>{{ $data['monthly_stats']['overdue_books'] }}</strong> overdue records remained open during the same period.</div>
                    </div>
                </td>
                <td width="50%" valign="top">
                    <div class="panel">
                        <div class="section-title">Demographic Notes</div>
                        <div>
                            @if($topProgram)
                                <strong>{{ $topProgram['program'] }}</strong> recorded the highest student participation with <strong>{{ $topProgram['student_count'] }}</strong> active student borrowers.
                            @else
                                No program participation data is available for this period.
                            @endif
                        </div>
                        <div style="margin-top:8px;">
                            @if($topGender)
                                <strong>{{ ucfirst($topGender['gender']) }}</strong> represents the largest gender group with <strong>{{ $topGender['count'] }}</strong> active students.
                            @else
                                No gender distribution data is available for this period.
                            @endif
                        </div>
                    </div>
                </td>
            </tr>
        </table>

        <table class="two-col">
            <tr>
                <td width="50%" valign="top">
                    <div class="panel">
                        <div class="section-title">Students Per Program</div>
                        @forelse($programDistribution as $program)
                            <div class="bar-row">
                                <div class="bar-meta">
                                    <span class="left">{{ $program['program'] }}</span>
                                    <span class="right">{{ $program['student_count'] }}</span>
                                </div>
                                <div class="bar-track">
                                    <div class="bar-fill-green" style="width: {{ ($program['student_count'] / $programMax) * 100 }}%"></div>
                                </div>
                            </div>
                        @empty
                            <div>No program distribution data for this month.</div>
                        @endforelse
                    </div>
                </td>
                <td width="50%" valign="top">
                    <div class="panel">
                        <div class="section-title">Books Distribution By Program</div>
                        @forelse($booksByProgram as $program => $count)
                            <div class="bar-row">
                                <div class="bar-meta">
                                    <span class="left">{{ $program }}</span>
                                    <span class="right">{{ $count }}</span>
                                </div>
                                <div class="bar-track">
                                    <div class="bar-fill-teal" style="width: {{ ($count / $booksMax) * 100 }}%"></div>
                                </div>
                            </div>
                        @empty
                            <div>No book distribution data available.</div>
                        @endforelse
                    </div>
                </td>
            </tr>
        </table>

        <table class="two-col">
            <tr>
                <td width="50%" valign="top">
                    <div class="panel">
                        <div class="section-title">Student Gender Distribution</div>
                        <table class="mini-grid">
                            <tr>
                                @forelse($genderDistribution as $gender)
                                    @php
                                        $percentage = ($gender['count'] / $genderTotal) * 100;
                                        $normalizedGender = strtolower(trim($gender['gender']));
                                        $genderSymbol = $normalizedGender === 'female' ? 'F' : ($normalizedGender === 'male' ? 'M' : 'U');
                                    @endphp
                                    <td valign="top">
                                        <div class="mini-card">
                                            <div class="gender-icon">{{ $genderSymbol }}</div>
                                            <div class="mini-label">{{ ucfirst($gender['gender']) }}</div>
                                            <div class="summary-value" style="font-size:20px;">{{ $gender['count'] }}</div>
                                            <div style="font-size:10px; color:#6b7280;">{{ number_format($percentage, 1) }}% of active students</div>
                                        </div>
                                    </td>
                                @empty
                                    <td>No gender data available for this month.</td>
                                @endforelse
                            </tr>
                        </table>
                    </div>
                </td>
                <td width="50%" valign="top">
                    <div class="panel">
                        <div class="section-title">Top Categories</div>
                        <table class="data-table">
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
                                        <td><strong>{{ $category->borrow_count }}</strong></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2">No category data for this month.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </td>
            </tr>
        </table>

        <table class="two-col" style="margin-top:12px;">
            <tr>
                <td width="50%" valign="top">
                    <div class="panel">
                        <div class="section-title">Students by Campus</div>
                        @forelse($data['campus_distribution'] ?? [] as $campus)
                            <div class="bar-row">
                                <div class="bar-meta">
                                    <span class="left">{{ $campus['campus'] }}</span>
                                    <span class="right">{{ $campus['count'] }}</span>
                                </div>
                                <div class="bar-track">
                                    <div class="bar-fill-green" style="width: {{ ($campus['count'] / max(1, collect($data['campus_distribution'])->max('count'))) * 100 }}%"></div>
                                </div>
                            </div>
                        @empty
                            <div>No campus distribution data available.</div>
                        @endforelse
                    </div>
                </td>
                <td width="50%" valign="top">
                    <div class="panel">
                        <div class="section-title">Resource Types</div>
                        @forelse($data['resource_types'] ?? [] as $resource)
                            <div class="bar-row">
                                <div class="bar-meta">
                                    <span class="left">{{ $resource['category'] }}</span>
                                    <span class="right">{{ $resource['count'] }}</span>
                                </div>
                                <div class="bar-track">
                                    <div class="bar-fill-teal" style="width: {{ ($resource['count'] / max(1, collect($data['resource_types'])->max('count'))) * 100 }}%"></div>
                                </div>
                            </div>
                        @empty
                            <div>No resource type data available.</div>
                        @endforelse
                    </div>
                </td>
            </tr>
        </table>

        <table class="two-col" style="margin-top:12px;">
            <tr>
                <td width="50%" valign="top">
                    <div class="panel">
                        <div class="section-title">Top Student Borrowers</div>
                        <table class="data-table" style="font-size: 10px;">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Course</th>
                                    <th width="40" style="text-align:center;">Borrows</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($data['top_students'] as $student)
                                    <tr>
                                        <td><strong>{{ $student['name'] }}</strong><br><small style="color:#666;">ID: {{ $student['library_id'] }}</small></td>
                                        <td>{{ $student['course'] }}</td>
                                        <td style="text-align:center;"><strong>{{ $student['borrow_count'] }}</strong></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3">No student activity this month.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </td>
                <td width="50%" valign="top">
                    <div class="panel">
                        <div class="section-title">Most Borrowed Books</div>
                        <table class="data-table" style="font-size: 10px;">
                            <thead>
                                <tr>
                                    <th>Book Title</th>
                                    <th width="40" style="text-align:center;">Borrows</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($data['popular_books'] ?? [] as $book)
                                    <tr>
                                        <td><strong>{{ $book['title'] }}</strong><br><small style="color:#666;">{{ $book['author'] }}</small></td>
                                        <td style="text-align:center;"><strong>{{ $book['borrow_count'] }}</strong></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2">No borrowing activity this month.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </td>
            </tr>
        </table>

        <div class="panel" style="margin-top:12px;">
            <div class="section-title">Daily Library Activity</div>
            <table class="data-table">
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
                            <td><strong>{{ $row['borrows'] }}</strong></td>
                            <td><strong>{{ $row['returns'] }}</strong></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">No daily activity for this month.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="note" style="margin-top:12px;">
            <strong>Interpretation:</strong> This monthly report summarizes the selected period's borrowing activity, active student participation, collection distribution, and key library usage patterns to support planning and reporting.
        </div>

        <div class="footer">
            Generated on {{ now()->format('Y-m-d H:i:s') }} | JHCSC KNOWLY Library Management System
        </div>
    </div>

    @if(!empty($isPrintView))
    <script>
        window.addEventListener('load', function () {
            window.print();
        });
    </script>
    @endif
</body>
</html>
