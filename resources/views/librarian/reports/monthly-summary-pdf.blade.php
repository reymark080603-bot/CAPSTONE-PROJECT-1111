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

    // SVG Line graph points calculation (Top 5 programs)
    $polylinePoints = "";
    $pointIndex = 0;
    $top5Programs = $programDistribution->sortByDesc('student_count')->take(5);
    $pointCount = $top5Programs->count();
    $svgWidth = 240;
    $svgHeight = 80;
    $padding = 20;
    
    $coordinates = [];
    foreach($top5Programs as $program) {
        $x = $padding + ($pointIndex * (($svgWidth - (2 * $padding)) / max(1, $pointCount - 1)));
        $y = $svgHeight - 15 - (($program['student_count'] / $programMax) * ($svgHeight - 28));
        $coordinates[] = [
            'x' => $x, 
            'y' => $y, 
            'label' => substr($program['program'], 0, 8), 
            'count' => $program['student_count']
        ];
        $polylinePoints .= "{$x},{$y} ";
        $pointIndex++;
    }

    // SVG Pie Chart calculations (Books Borrowed vs Returned)
    $totalBorrowsReturns = max(1, $data['monthly_stats']['books_borrowed'] + $data['monthly_stats']['books_returned']);
    $borrowPercent = ($data['monthly_stats']['books_borrowed'] / $totalBorrowsReturns) * 100;
    $returnPercent = ($data['monthly_stats']['books_returned'] / $totalBorrowsReturns) * 100;
    $borrowStroke = ($borrowPercent / 100) * 157; // Circumference is 2 * pi * 25 = 157
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
            position: relative;
        }

        .header-logo-container {
            width: 100%;
            border-collapse: collapse;
        }

        .header-logo-container td {
            border: none;
            padding: 0;
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
            position: relative;
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

        /* Board games and computer usage */
        .games-grid {
            width: 100%;
            border-collapse: collapse;
        }

        .games-grid td {
            padding: 3px;
            font-size: 8px;
        }

        .game-pill {
            background: #eef7ee;
            border: 1px solid #cce2cc;
            border-radius: 6px;
            padding: 3px 6px;
            font-weight: bold;
            color: #1e4d3a;
            text-align: center;
        }

        .computer-grid {
            width: 100%;
            border-collapse: collapse;
        }

        .computer-grid td {
            text-align: center;
            padding: 2px;
        }

        .dial-label {
            font-size: 7px;
            font-weight: bold;
            color: #1e4d3a;
            margin-top: 2px;
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
            <table class="header-logo-container">
                <tr>
                    <td style="text-align: center;">
                        <h1>J.H. CERILLES STATE COLLEGE</h1>
                        <h2>COLLEGE LIBRARY</h2>
                        <div class="period">{{ $data['period']['month_name'] }} Statistics</div>
                        <div class="intro-copy">
                            The infographic presents the library's statistics report for {{ $data['period']['month_name'] }}, detailing visits, library IDs issued, borrowing, research, computer use, and board games activities.
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- ROW 1: Library Daily Visits & Interpretation -->
        <table class="grid-table">
            <tr>
                <td width="55%">
                    <div class="panel" style="height: 155px;">
                        <div class="panel-title">Library Daily Visits</div>
                        <table class="bar-chart-table" style="margin-top: 5px;">
                            <tr>
                                @php
                                    $colors = ['#c0392b', '#2980b9', '#d35400', '#16a085', '#8e44ad'];
                                @endphp
                                @forelse($top5Programs as $index => $program)
                                    <td valign="bottom" style="height: 90px;">
                                        <div style="font-size: 7.5px; font-weight: bold; color: #374151; margin-bottom: 2px;">{{ $program['student_count'] }}</div>
                                        <div class="vertical-bar" style="background: {{ $colors[$index % 5] }}; height: {{ max(10, ($program['student_count'] / $programMax) * 75) }}px;"></div>
                                        <div class="bar-label">{{ substr($program['program'], 0, 8) }}</div>
                                    </td>
                                @empty
                                    <td style="height: 90px; text-align: center; vertical-align: middle; color: #9ca3af;">No program visits.</td>
                                @endforelse
                            </tr>
                        </table>
                    </div>
                </td>
                <td width="45%">
                    <div class="panel panel-dark" style="height: 155px;">
                        <div class="panel-title panel-title-white">Interpretation</div>
                        <div class="interpretation-text">
                            This monthly dashboard highlights library operations during <span class="interpretation-highlight">{{ $data['period']['month_name'] }}</span>.
                            <br><br>
                            @if($topProgram)
                                The <span class="interpretation-highlight">{{ $topProgram['program'] }}</span> program recorded the largest share of student activity, with <span class="interpretation-highlight">{{ $topProgram['student_count'] }}</span> active users.
                            @endif
                            A total of <span class="interpretation-highlight">{{ $data['monthly_stats']['books_borrowed'] + $data['monthly_stats']['books_returned'] }}</span> resource circulation records were processed, indicating active engagement with learning collections.
                        </div>
                    </div>
                </td>
            </tr>
        </table>

        <!-- ROW 2: Transactions & Demographic Profile -->
        <table class="grid-table">
            <tr>
                <td width="55%">
                    <div class="panel" style="height: 100px;">
                        <div class="panel-title">Library Entry Per Type of Transaction</div>
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
                                    <!-- Male SVG Icon -->
                                    <svg viewBox="0 0 24 24" style="width: 24px; height: 24px; fill: #2980b9; margin: 0 auto 2px; display: block;">
                                        <path d="M12,2A4,4 0 0,0 8,6A4,4 0 0,0 12,10A4,4 0 0,0 16,6A4,4 0 0,0 12,2M12,12C7.58,12 4,14.24 4,17V20H20V17C20,14.24 16.42,12 12,12Z" />
                                    </svg>
                                    <div class="demographic-label" style="color: #2980b9;">Male</div>
                                    <div class="demographic-percentage" style="color: #2980b9;">{{ number_format($malePercentage, 1) }}%</div>
                                    <div style="font-size: 7px; color: #6b7280; font-weight: bold; margin-top: 2px;">{{ $maleCount }} Active</div>
                                </td>
                                <td width="50%">
                                    <!-- Female SVG Icon -->
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

        <!-- ROW 3: Book Utilization Report & Library ID Application -->
        <table class="grid-table">
            <tr>
                <td width="55%">
                    <div class="panel" style="height: 155px;">
                        <div class="panel-title">Book Utilization Report</div>
                        @if($pointCount > 0)
                            <div style="text-align: center; margin-top: 6px;">
                                <svg viewBox="0 0 240 85" style="width: 240px; height: 85px; display: inline-block;">
                                    <!-- Grid background lines -->
                                    <line x1="20" y1="12" x2="220" y2="12" stroke="#e6eee6" stroke-width="1" />
                                    <line x1="20" y1="36" x2="220" y2="36" stroke="#e6eee6" stroke-width="1" />
                                    <line x1="20" y1="60" x2="220" y2="60" stroke="#e6eee6" stroke-width="1" />
                                    
                                    <!-- Vector red line chart -->
                                    <polyline points="{{ trim($polylinePoints) }}" fill="none" stroke="#b22222" stroke-width="2.5" />
                                    
                                    <!-- Data dots and labels -->
                                    @foreach($coordinates as $pt)
                                        <circle cx="{{ $pt['x'] }}" cy="{{ $pt['y'] }}" r="3.5" fill="#ffffff" stroke="#b22222" stroke-width="2" />
                                        <text x="{{ $pt['x'] }}" y="{{ $pt['y'] - 6 }}" font-size="7.5" font-family="DejaVu Sans" font-weight="bold" fill="#b22222" text-anchor="middle">{{ $pt['count'] }}</text>
                                        <text x="{{ $pt['x'] }}" y="76" font-size="7.5" font-family="DejaVu Sans" font-weight="bold" fill="#1e4d3a" text-anchor="middle">{{ $pt['label'] }}</text>
                                    @endforeach
                                </svg>
                            </div>
                        @else
                            <div style="text-align: center; line-height: 120px; color: #9ca3af;">No program visits.</div>
                        @endif
                    </div>
                </td>
                <td width="45%">
                    <div class="panel" style="height: 155px;">
                        <div class="panel-title">Library Circulation Balance</div>
                        <div style="text-align: center; padding-top: 6px;">
                            <svg viewBox="0 0 100 100" style="width: 80px; height: 80px; display: inline-block;">
                                <!-- Concentric circle segments -->
                                <circle cx="50" cy="50" r="25" fill="none" stroke="#e67e22" stroke-width="14" />
                                <circle cx="50" cy="50" r="25" fill="none" stroke="#27ae60" stroke-width="14" 
                                        stroke-dasharray="{{ $borrowStroke }} 157" 
                                        transform="rotate(-90 50 50)" />
                            </svg>
                            <div style="margin-top: 6px; text-align: center;">
                                <span style="display: inline-block; font-size: 7.5px; font-weight: bold; color: #27ae60; margin-right: 6px;">
                                    ● Borrows ({{ number_format($borrowPercent, 0) }}%)
                                </span>
                                <span style="display: inline-block; font-size: 7.5px; font-weight: bold; color: #e67e22;">
                                    ● Returns ({{ number_format($returnPercent, 0) }}%)
                                </span>
                            </div>
                        </div>
                    </div>
                </td>
            </tr>
        </table>

        <!-- ROW 4: Auxiliary Services (Board Games & Computer Usage) -->
        <table class="grid-table">
            <tr>
                <td width="55%">
                    <div class="panel" style="height: 110px;">
                        <div class="panel-title">Auxiliary Materials & Games</div>
                        <table class="games-grid" style="margin-top: 2px;">
                            <tr>
                                <td>
                                    <div class="game-pill">CHESS</div>
                                    <div style="text-align: center; font-size: 7px; color: #4b5563; font-weight: bold; margin-top: 2px;">Active Play</div>
                                </td>
                                <td>
                                    <div class="game-pill" style="background: #eef2f7; border-color: #cbd5e1; color: #2980b9;">SCRABBLE</div>
                                    <div style="text-align: center; font-size: 7px; color: #4b5563; font-weight: bold; margin-top: 2px;">Word Building</div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="game-pill" style="background: #fdf6ec; border-color: #fbd5b5; color: #d35400;">JENGA</div>
                                    <div style="text-align: center; font-size: 7px; color: #4b5563; font-weight: bold; margin-top: 2px;">Tower Build</div>
                                </td>
                                <td>
                                    <div class="game-pill" style="background: #fdf2f2; border-color: #fecaca; color: #c0392b;">SNAKES & LADDERS</div>
                                    <div style="text-align: center; font-size: 7px; color: #4b5563; font-weight: bold; margin-top: 2px;">Table Roll</div>
                                </td>
                            </tr>
                        </table>
                    </div>
                </td>
                <td width="45%">
                    <div class="panel" style="height: 110px;">
                        <div class="panel-title">Computer & Station Usage</div>
                        <table class="computer-grid" style="margin-top: 4px;">
                            <tr>
                                @foreach($top5Programs->take(4) as $index => $program)
                                    @php
                                        $compPercent = max(5, round(($program['student_count'] / $programMax) * 95));
                                        $dashVal = ($compPercent / 100) * 75.4;
                                        $compColors = ['#c0392b', '#2980b9', '#d35400', '#16a085'];
                                    @endphp
                                    <td>
                                        <svg viewBox="0 0 36 36" style="width: 32px; height: 32px; display: inline-block;">
                                            <circle cx="18" cy="18" r="12" fill="none" stroke="#dcead7" stroke-width="3" />
                                            <circle cx="18" cy="18" r="12" fill="none" stroke="{{ $compColors[$index % 4] }}" stroke-width="3" 
                                                    stroke-dasharray="{{ $dashVal }} 75.4" 
                                                    transform="rotate(-90 18 18)" />
                                            <text x="18" y="21" font-size="7" font-family="DejaVu Sans" font-weight="bold" fill="#1e4d3a" text-anchor="middle">{{ $compPercent }}%</text>
                                        </svg>
                                        <div class="dial-label">{{ substr($program['program'], 0, 5) }}</div>
                                    </td>
                                @endforeach
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
        </table>

        <!-- Infographic Footer Badge -->
        <div class="footer">
            Generated on {{ now()->format('Y-m-d H:i:s') }} | JHCSC College Library Infographics Statistics Report | System Powered by JHCSC KNOWLY
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
