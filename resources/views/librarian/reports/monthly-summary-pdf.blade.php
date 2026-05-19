@php
    $programDistribution = collect($data['program_distribution'] ?? []);
    $genderDistribution = collect($data['gender_distribution'] ?? []);
    $booksByProgram = collect($data['books_by_program'] ?? []);
    $campusDistribution = collect($data['campus_distribution'] ?? []);
    $resourceTypes = collect($data['resource_types'] ?? []);
    $popularBooks = collect($data['popular_books'] ?? []);
    
    $programMax = max(1, (int) $programDistribution->max('student_count'));
    $booksMax = max(1, (int) $booksByProgram->max());
    $genderTotal = max(1, (int) $genderDistribution->sum('count'));
    $campusMax = max(1, (int) $campusDistribution->max('count'));
    
    $topProgram = $programDistribution->sortByDesc('student_count')->first();
    $topGender = $genderDistribution->sortByDesc('count')->first();

    $maleCount = $genderDistribution->where('gender', 'male')->first()['count'] ?? 0;
    $femaleCount = $genderDistribution->where('gender', 'female')->first()['count'] ?? 0;
    $malePercentage = $genderTotal > 0 ? ($maleCount / $genderTotal) * 100 : 0;
    $femalePercentage = $genderTotal > 0 ? ($femaleCount / $genderTotal) * 100 : 0;

    // Campus vertical bar chart data (Top 5 campuses)
    $top5Campuses = $campusDistribution->sortByDesc('count')->take(5);

    // Book Distribution by Course line graph points calculation (Top 5 programs)
    $booksPolylinePoints = "";
    $booksPointIndex = 0;
    $top5BooksProg = collect($data['books_by_program'])->sortByDesc(fn($v) => $v)->take(5);
    $booksProgCount = $top5BooksProg->count();
    $booksCoordinates = [];
    foreach($top5BooksProg as $program => $count) {
        $x = 20 + ($booksPointIndex * ((240 - 40) / max(1, $booksProgCount - 1)));
        $y = 80 - 15 - (($count / $booksMax) * (80 - 28));
        $booksCoordinates[] = [
            'x' => $x, 
            'y' => $y, 
            'label' => substr($program, 0, 8), 
            'count' => $count
        ];
        $booksPolylinePoints .= "{$x},{$y} ";
        $booksPointIndex++;
    }

    // Resource Type borrowing donut chart calculations (SVG circumference = 157)
    $resStats = [];
    $accumulatedPercentage = 0;
    $resourceTotalSum = max(1, $resourceTypes->sum('count'));
    $resourceColors = ['#16a085', '#2980b9', '#8e44ad'];
    $resourceIndex = 0;
    foreach($resourceTypes as $resource) {
        $percent = ($resource['count'] / $resourceTotalSum) * 100;
        $strokeLength = ($percent / 100) * 157;
        $strokeOffset = 157 - ($accumulatedPercentage / 100) * 157;
        $resStats[] = [
            'category' => $resource['category'],
            'count' => $resource['count'],
            'percent' => $percent,
            'strokeLength' => $strokeLength,
            'strokeOffset' => $strokeOffset,
            'color' => $resourceColors[$resourceIndex % 3]
        ];
        $accumulatedPercentage += $percent;
        $resourceIndex++;
    }

    // Daily library activity timeline graph points calculation (Sampled 7 points)
    $dailyStatsColl = collect($data['daily_stats']);
    $dailyMaxCount = max(1, (int) $dailyStatsColl->max(fn($row) => max($row['borrows'], $row['returns'])));
    $dailyPointsBorrows = "";
    $dailyPointsReturns = "";
    $dailyCoordinates = [];
    $dailyIndex = 0;
    $sampledDailyStats = $dailyStatsColl->nth(max(1, round($dailyStatsColl->count() / 7)))->take(7);
    $sampledCount = $sampledDailyStats->count();
    foreach($sampledDailyStats as $row) {
        $x = 20 + ($dailyIndex * ((240 - 40) / max(1, $sampledCount - 1)));
        $yB = 80 - 15 - (($row['borrows'] / $dailyMaxCount) * (80 - 28));
        $yR = 80 - 15 - (($row['returns'] / $dailyMaxCount) * (80 - 28));
        
        $dailyCoordinates[] = [
            'x' => $x,
            'yB' => $yB,
            'yR' => $yR,
            'label' => substr($row['date'], 8, 2),
            'borrows' => $row['borrows'],
            'returns' => $row['returns']
        ];
        $dailyPointsBorrows .= "{$x},{$yB} ";
        $dailyPointsReturns .= "{$x},{$yR} ";
        $dailyIndex++;
    }
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JHCSC College Library - {{ $data['period']['month_name'] }} Statistics</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 6mm 10mm;
        }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: #ffffff;
            color: #1f2937;
            font-size: 10px;
            line-height: 1.35;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .shell {
            background: #eef7ee;
            border: 2px solid #a3cca3;
            border-radius: 16px;
            padding: 14px;
            width: 100%;
            box-sizing: border-box;
        }

        /* Header Style */
        .header {
            background: #1e4d3a;
            border-radius: 12px;
            padding: 12px 16px;
            color: #ffffff;
            text-align: center;
            margin-bottom: 10px;
        }

        .header h1 {
            margin: 0;
            font-size: 16px;
            letter-spacing: 0.05em;
            color: #ffffff;
            font-weight: bold;
        }

        .header h2 {
            margin: 2px 0;
            font-size: 12px;
            letter-spacing: 0.1em;
            color: #8cd19e;
            font-weight: normal;
        }

        .header .period {
            background: #8c2626;
            display: inline-block;
            padding: 2px 10px;
            border-radius: 99px;
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-top: 4px;
        }

        .intro-copy {
            font-size: 8px;
            color: #dcead7;
            margin-top: 6px;
            line-height: 1.4;
        }

        /* Grid elements */
        .grid-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 8px 0;
            margin: 0 -8px 8px;
        }

        .grid-table td {
            padding: 0;
            vertical-align: top;
        }

        .panel {
            background: #ffffff;
            border: 1px solid #cce2cc;
            border-radius: 12px;
            padding: 10px;
            box-sizing: border-box;
        }

        .panel-dark {
            background: #1e4d3a;
            color: #ffffff;
            border: 1px solid #143528;
        }

        .panel-title {
            font-size: 9px;
            font-weight: bold;
            color: #1e4d3a;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 2px solid #d0ebd0;
            padding-bottom: 4px;
            margin-bottom: 8px;
        }

        .panel-title-white {
            color: #8cd19e;
            border-bottom-color: #2a634c;
        }

        /* Vertical Bar Chart styles */
        .bar-chart-table {
            width: 100%;
            border-collapse: collapse;
        }

        .bar-chart-table td {
            text-align: center;
            vertical-align: bottom;
            padding: 2px;
        }

        .vertical-bar {
            width: 16px;
            border-radius: 3px 3px 0 0;
            margin: 0 auto;
        }

        .bar-label {
            font-size: 7px;
            font-weight: bold;
            color: #1e4d3a;
            margin-top: 4px;
            text-transform: uppercase;
        }

        /* Interpretation styling */
        .interpretation-text {
            font-size: 8.5px;
            line-height: 1.45;
            color: #f1f8f1;
        }

        .interpretation-highlight {
            color: #ffdd6b;
            font-weight: bold;
        }

        /* Circular Transaction Badges */
        .transaction-grid {
            width: 100%;
            border-collapse: collapse;
        }

        .transaction-grid td {
            text-align: center;
            padding: 4px 2px;
        }

        .transaction-circle {
            width: 38px;
            height: 38px;
            border-radius: 99px;
            margin: 0 auto 4px;
            display: block;
            border: 3px solid;
            background: #ffffff;
        }

        .transaction-circle span {
            display: block;
            font-size: 11px;
            font-weight: bold;
            line-height: 38px;
            color: #1f2937;
        }

        .transaction-label {
            font-size: 7px;
            font-weight: bold;
            color: #374151;
            text-transform: uppercase;
            line-height: 1.2;
        }

        /* Demographic styling */
        .demographic-container {
            width: 100%;
            border-collapse: collapse;
        }

        .demographic-container td {
            padding: 4px;
            text-align: center;
            vertical-align: top;
        }

        .demographic-percentage {
            font-size: 13px;
            font-weight: bold;
            margin-top: 2px;
        }

        .demographic-label {
            font-size: 7.5px;
            font-weight: bold;
            color: #4b5563;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        /* Table Infographic designs */
        .infographic-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }

        .infographic-table th {
            font-size: 8px;
            font-weight: bold;
            color: #1e4d3a;
            text-transform: uppercase;
            border-bottom: 2px solid #d0ebd0;
            padding: 4px 6px;
            text-align: left;
        }

        .infographic-table td {
            font-size: 8px;
            padding: 5px 6px;
            border-bottom: 1px solid #e2efe2;
            color: #374151;
            vertical-align: middle;
        }

        .infographic-table tr:last-child td {
            border-bottom: none;
        }

        /* Footer styling */
        .footer {
            margin-top: 8px;
            text-align: center;
            font-size: 7.5px;
            color: #4d665d;
            border-top: 1px solid #cce2cc;
            padding-top: 6px;
        }
    </style>
</head>
<body>
    <div class="shell">
        <!-- Main Library Header Badge -->
        <div class="header">
            <h1>J.H. CERILLES STATE COLLEGE</h1>
            <h2>COLLEGE LIBRARY</h2>
            <div class="period">{{ $data['period']['month_name'] }} Statistics</div>
            <div class="intro-copy">
                This monthly analytics dashboard presents active registered student demographics, book distribution by course, learning resource borrowings, and daily transaction activities for the {{ $data['period']['month_name'] }} reporting period.
            </div>
        </div>

        <!-- ROW 1: Registered Students by Campus & Interpretation -->
        <table class="grid-table">
            <tr>
                <td width="55%">
                    <div class="panel" style="height: 155px;">
                        <div class="panel-title">Registered Students by Campus</div>
                        <table class="bar-chart-table" style="margin-top: 5px;">
                            <tr>
                                @php
                                    $campusColors = ['#c0392b', '#2980b9', '#d35400', '#16a085', '#8e44ad'];
                                @endphp
                                @forelse($top5Campuses as $index => $campus)
                                    <td valign="bottom" style="height: 90px;">
                                        <div style="font-size: 7.5px; font-weight: bold; color: #374151; margin-bottom: 2px;">{{ $campus['count'] }}</div>
                                        <div class="vertical-bar" style="background: {{ $campusColors[$index % 5] }}; height: {{ max(10, ($campus['count'] / $campusMax) * 75) }}px;"></div>
                                        <div class="bar-label">{{ substr($campus['campus'], 0, 8) }}</div>
                                    </td>
                                @empty
                                    <td style="height: 90px; text-align: center; vertical-align: middle; color: #9ca3af;">No campus distribution data.</td>
                                @endforelse
                            </tr>
                        </table>
                    </div>
                </td>
                <td width="45%">
                    <div class="panel panel-dark" style="height: 155px;">
                        <div class="panel-title panel-title-white">Interpretation</div>
                        <div class="interpretation-text">
                            This monthly statistics report summarizes active engagement for <span class="interpretation-highlight">{{ $data['period']['month_name'] }}</span>.
                            <br><br>
                            @if($topProgram)
                                The <span class="interpretation-highlight">{{ $topProgram['program'] }}</span> program recorded the highest overall borrowing count, with <span class="interpretation-highlight">{{ $topProgram['student_count'] }}</span> active student transactions.
                            @endif
                            Circulation activities show strong participation from the <span class="interpretation-highlight">{{ $topGender ? ucfirst($topGender['gender']) : 'N/A' }}</span> borrower demographic, accounting for <span class="interpretation-highlight">{{ $topGender ? $topGender['count'] : '0' }}</span> active users.
                        </div>
                    </div>
                </td>
            </tr>
        </table>

        <!-- ROW 2: Stats Cards (Type of Transaction) & Demographic Profile -->
        <table class="grid-table">
            <tr>
                <td width="55%">
                    <div class="panel" style="height: 100px;">
                        <div class="panel-title">Circulation Stats (Type of Transaction)</div>
                        <table class="transaction-grid">
                            <tr>
                                <td>
                                    <div class="transaction-circle" style="border-color: #c0392b;">
                                        <span>{{ $data['monthly_stats']['books_borrowed'] }}</span>
                                    </div>
                                    <div class="transaction-label">Books<br>Borrowed</div>
                                </td>
                                <td>
                                    <div class="transaction-circle" style="border-color: #2980b9;">
                                        <span>{{ $data['monthly_stats']['books_returned'] }}</span>
                                    </div>
                                    <div class="transaction-label">Books<br>Returned</div>
                                </td>
                                <td>
                                    <div class="transaction-circle" style="border-color: #d35400;">
                                        <span>{{ $data['monthly_stats']['new_students'] }}</span>
                                    </div>
                                    <div class="transaction-label">New<br>Registered</div>
                                </td>
                                <td>
                                    <div class="transaction-circle" style="border-color: #16a085;">
                                        <span>{{ $data['monthly_stats']['active_students'] }}</span>
                                    </div>
                                    <div class="transaction-label">Active<br>Borrowers</div>
                                </td>
                            </tr>
                        </table>
                    </div>
                </td>
                <td width="45%">
                    <div class="panel" style="height: 100px;">
                        <div class="panel-title">Demographic Profile</div>
                        <table class="demographic-container" style="margin-top: 4px;">
                            <tr>
                                <td width="50%">
                                    <svg viewBox="0 0 24 24" style="width: 24px; height: 24px; fill: #2980b9; margin: 0 auto 2px; display: block;">
                                        <path d="M12,2A4,4 0 0,0 8,6A4,4 0 0,0 12,10A4,4 0 0,0 16,6A4,4 0 0,0 12,2M12,12C7.58,12 4,14.24 4,17V20H20V17C20,14.24 16.42,12 12,12Z" />
                                    </svg>
                                    <div class="demographic-label" style="color: #2980b9;">Male</div>
                                    <div class="demographic-percentage" style="color: #2980b9;">{{ number_format($malePercentage, 1) }}%</div>
                                    <div style="font-size: 7px; color: #6b7280; font-weight: bold; margin-top: 2px;">{{ $maleCount }} Active</div>
                                </td>
                                <td width="50%">
                                    <svg viewBox="0 0 24 24" style="width: 24px; height: 24px; fill: #ec4899; margin: 0 auto 2px; display: block;">
                                        <path d="M12,2A4,4 0 0,0 8,6A4,4 0 0,0 12,10A4,4 0 0,0 16,6A4,4 0 0,0 12,2M12,12C7.58,12 4,14.24 4,17V20H20V17C20,14.24 16.42,12 12,12Z" />
                                    </svg>
                                    <div class="demographic-label" style="color: #ec4899;">Female</div>
                                    <div class="demographic-percentage" style="color: #ec4899;">{{ number_format($femalePercentage, 1) }}%</div>
                                    <div style="font-size: 7px; color: #6b7280; font-weight: bold; margin-top: 2px;">{{ $femaleCount }} Active</div>
                                </td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
        </table>

        <!-- ROW 3: Book Distribution by Course & Resource Type Donut Chart -->
        <table class="grid-table">
            <tr>
                <td width="55%">
                    <div class="panel" style="height: 155px;">
                        <div class="panel-title">Book Distribution by Course</div>
                        @if($booksProgCount > 0)
                            <div style="text-align: center; margin-top: 6px;">
                                <svg viewBox="0 0 240 85" style="width: 240px; height: 85px; display: inline-block;">
                                    <line x1="20" y1="12" x2="220" y2="12" stroke="#e6eee6" stroke-width="1" />
                                    <line x1="20" y1="36" x2="220" y2="36" stroke="#e6eee6" stroke-width="1" />
                                    <line x1="20" y1="60" x2="220" y2="60" stroke="#e6eee6" stroke-width="1" />
                                    
                                    <polyline points="{{ trim($booksPolylinePoints) }}" fill="none" stroke="#b22222" stroke-width="2.5" />
                                    
                                    @foreach($booksCoordinates as $pt)
                                        <circle cx="{{ $pt['x'] }}" cy="{{ $pt['y'] }}" r="3.5" fill="#ffffff" stroke="#b22222" stroke-width="2" />
                                        <text x="{{ $pt['x'] }}" y="{{ $pt['y'] - 6 }}" font-size="7.5" font-family="DejaVu Sans" font-weight="bold" fill="#b22222" text-anchor="middle">{{ $pt['count'] }}</text>
                                        <text x="{{ $pt['x'] }}" y="76" font-size="7.5" font-family="DejaVu Sans" font-weight="bold" fill="#1e4d3a" text-anchor="middle">{{ $pt['label'] }}</text>
                                    @endforeach
                                </svg>
                            </div>
                        @else
                            <div style="text-align: center; line-height: 120px; color: #9ca3af;">No course distribution data available.</div>
                        @endif
                    </div>
                </td>
                <td width="45%">
                    <div class="panel" style="height: 155px;">
                        <div class="panel-title">Resource Type by Borrowing Activity</div>
                        <div style="text-align: center; padding-top: 6px;">
                            <svg viewBox="0 0 100 100" style="width: 76px; height: 76px; display: inline-block;">
                                <!-- Overlapping circle segments representing exact resource ratios -->
                                <circle cx="50" cy="50" r="25" fill="none" stroke="#e2eee2" stroke-width="14" />
                                @foreach($resStats as $stat)
                                    <circle cx="50" cy="50" r="25" fill="none" stroke="{{ $stat['color'] }}" stroke-width="14" 
                                            stroke-dasharray="{{ $stat['strokeLength'] }} 157" 
                                            stroke-dashoffset="{{ $stat['strokeOffset'] }}"
                                            transform="rotate(-90 50 50)" />
                                @endforeach
                            </svg>
                            <div style="margin-top: 6px; text-align: center; line-height: 1.35;">
                                @foreach($resStats as $stat)
                                    <span style="display: inline-block; font-size: 7.5px; font-weight: bold; color: {{ $stat['color'] }}; margin-right: 4px;">
                                        ● {{ $stat['category'] }} ({{ number_format($stat['percent'], 0) }}%)
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </td>
            </tr>
        </table>

        <!-- ROW 4: Top Student Borrowers & Daily Library Activity Graph -->
        <table class="grid-table">
            <tr>
                <td width="55%">
                    <div class="panel" style="height: 155px;">
                        <div class="panel-title">Top Student Borrowers</div>
                        <table class="infographic-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Course</th>
                                    <th style="text-align: center;">Borrows</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($data['top_students']->take(4) as $student)
                                    <tr>
                                        <td><strong>{{ $student['name'] }}</strong><br><small style="color: #6b7280;">ID: {{ $student['library_id'] }}</small></td>
                                        <td>{{ $student['course'] }}</td>
                                        <td style="text-align: center; font-weight: bold; color: #16a085;">{{ $student['borrow_count'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" style="text-align: center; color: #9ca3af; padding: 12px;">No active borrowers this month.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </td>
                <td width="45%">
                    <div class="panel" style="height: 155px;">
                        <div class="panel-title">Daily Library Activity Timeline</div>
                        <div style="text-align: center; margin-top: 6px;">
                            <svg viewBox="0 0 240 85" style="width: 240px; height: 85px; display: inline-block;">
                                <line x1="20" y1="12" x2="220" y2="12" stroke="#e6eee6" stroke-width="1" />
                                <line x1="20" y1="36" x2="220" y2="36" stroke="#e6eee6" stroke-width="1" />
                                <line x1="20" y1="60" x2="220" y2="60" stroke="#e6eee6" stroke-width="1" />
                                
                                <!-- Timeline polyline for borrows (teal) -->
                                <polyline points="{{ trim($dailyPointsBorrows) }}" fill="none" stroke="#16a085" stroke-width="2" />
                                <!-- Timeline polyline for returns (orange) -->
                                <polyline points="{{ trim($dailyPointsReturns) }}" fill="none" stroke="#e67e22" stroke-width="2" />
                                
                                @foreach($dailyCoordinates as $pt)
                                    <circle cx="{{ $pt['x'] }}" cy="{{ $pt['yB'] }}" r="2.5" fill="#ffffff" stroke="#16a085" stroke-width="1.5" />
                                    <circle cx="{{ $pt['x'] }}" cy="{{ $pt['yR'] }}" r="2.5" fill="#ffffff" stroke="#e67e22" stroke-width="1.5" />
                                    <text x="{{ $pt['x'] }}" y="76" font-size="7.5" font-family="DejaVu Sans" font-weight="bold" fill="#1e4d3a" text-anchor="middle">Day {{ $pt['label'] }}</text>
                                @endforeach
                            </svg>
                            <div style="margin-top: 2px; text-align: center;">
                                <span style="display: inline-block; font-size: 7px; font-weight: bold; color: #16a085; margin-right: 8px;">
                                    — Borrows
                                </span>
                                <span style="display: inline-block; font-size: 7px; font-weight: bold; color: #e67e22;">
                                    — Returns
                                </span>
                            </div>
                        </div>
                    </div>
                </td>
            </tr>
        </table>

        <!-- ROW 5: Most Borrowed Books (Top 10) -->
        <div class="panel" style="margin-bottom: 8px;">
            <div class="panel-title">Most Borrowed Books (Top 10)</div>
            <table class="infographic-table">
                <thead>
                    <tr>
                        <th width="30">Rank</th>
                        <th>Book Title</th>
                        <th>Author</th>
                        <th width="100" style="text-align: center;">Borrow Tally</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($popularBooks->take(6) as $index => $book)
                        <tr>
                            <td><strong>#{{ $index + 1 }}</strong></td>
                            <td><strong>{{ $book['title'] }}</strong></td>
                            <td>{{ $book['author'] }}</td>
                            <td style="text-align: center; font-weight: bold; color: #2980b9;">{{ $book['borrow_count'] }} borrows</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" style="text-align: center; color: #9ca3af; padding: 12px;">No borrowing activity recorded this month.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Infographic Footer Badge -->
        <div class="footer">
            Generated on {{ now()->format('Y-m-d H:i:s') }} | JHCSC College Library Infographics Statistics Report | System Powered by JHCSC KNOWLY Library
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
