@extends('layouts.librarian')

@section('title', 'Monthly Report')

@section('content')
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
        $x = 40 + ($booksPointIndex * ((320 - 80) / max(1, $booksProgCount - 1)));
        $y = 110 - 20 - (($count / $booksMax) * (110 - 35));
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
        $x = 40 + ($dailyIndex * ((320 - 80) / max(1, $sampledCount - 1)));
        $yB = 110 - 20 - (($row['borrows'] / $dailyMaxCount) * (110 - 35));
        $yR = 110 - 20 - (($row['returns'] / $dailyMaxCount) * (110 - 35));
        
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

<div class="p-6 space-y-6 report-page bg-[#f1f8f1]">
    <style>
        :root {
            --report-bg: #dfead8;
            --report-card: #ffffff;
            --report-card-dark: #1e4d3a;
            --report-border: #cce2cc;
            --report-title: #1e4d3a;
            --report-teal: #1d6f5f;
            --report-green: #2f855a;
        }

        .monthly-report-shell {
            background: #eef7ee;
            border: 2px solid #a3cca3;
            border-radius: 28px;
            box-shadow: 0 15px 35px rgba(46, 91, 59, 0.05);
        }

        .monthly-header {
            background: #1e4d3a;
            border-radius: 20px;
            color: #ffffff;
        }

        .monthly-action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: 42px;
            padding: 0.5rem 1.25rem;
            border-radius: 0.75rem;
            font-size: 0.875rem;
            font-weight: 700;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(30, 77, 58, 0.12);
        }

        .monthly-action-btn:hover {
            transform: translateY(-1px);
        }

        .panel-card {
            background: var(--report-card);
            border: 1px solid var(--report-border);
            border-radius: 20px;
            transition: all 0.3s ease;
        }

        .panel-card:hover {
            box-shadow: 0 10px 25px rgba(46, 91, 59, 0.06);
        }

        .panel-card-dark {
            background: var(--report-card-dark);
            color: #ffffff;
            border: 1px solid #143528;
        }

        .panel-title {
            font-size: 0.9rem;
            font-weight: 800;
            color: var(--report-title);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 2px solid #d0ebd0;
            padding-bottom: 6px;
        }

        .vertical-bar {
            width: 22px;
            border-radius: 6px 6px 0 0;
            margin: 0 auto;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .vertical-bar:hover {
            transform: scaleY(1.05);
            filter: brightness(1.05);
        }

        .transaction-circle {
            width: 58px;
            height: 58px;
            border-radius: 9999px;
            margin: 0 auto 8px;
            border: 4px solid;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .transaction-circle:hover {
            transform: scale(1.08);
        }

        .infographic-table th {
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--report-title);
            text-transform: uppercase;
            border-bottom: 2px solid #d0ebd0;
            padding: 8px 12px;
        }

        .infographic-table td {
            font-size: 0.85rem;
            padding: 10px 12px;
            border-bottom: 1px solid #e2efe2;
            color: #374151;
        }

        .infographic-table tr:last-child td {
            border-bottom: none;
        }

        .game-pill {
            background: #eef7ee;
            border: 1px solid #cce2cc;
            border-radius: 8px;
            padding: 6px 12px;
            font-weight: bold;
            color: #1e4d3a;
            text-align: center;
            transition: all 0.2s ease;
        }

        .game-pill:hover {
            transform: scale(1.03);
            background: #e2f2e2;
        }
    </style>

    <div class="monthly-report-shell p-6 md:p-8">
        <!-- Main Infographics Header -->
        <div class="monthly-header p-6 md:p-8 mb-6 shadow-lg">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.25em] text-[#8cd19e] mb-1">J.H. CERILLES STATE COLLEGE</p>
                    <h1 class="text-3xl md:text-4xl font-extrabold uppercase tracking-wide">COLLEGE LIBRARY</h1>
                    <div class="inline-block bg-[#8c2626] text-white font-bold text-xs px-3 py-1 rounded-full uppercase tracking-wider mt-3 shadow">
                        {{ $data['period']['month_name'] }} Statistics
                    </div>
                    <p class="text-xs text-[#dcead7] mt-3 max-w-2xl leading-relaxed">
                        This dynamic monthly report showcases registered student campus distribution, book assignments by program, learning resource borrow rates, and active daily transaction activities across the {{ $data['period']['month_name'] }} reporting period.
                    </p>
                </div>
                <div class="flex flex-wrap gap-3 lg:justify-end no-print">
                    <a href="{{ route('librarian.reports.index') }}" class="monthly-action-btn border border-slate-300 bg-white text-slate-700 hover:bg-slate-50">
                        <i class="fas fa-arrow-left mr-1.5"></i> Back
                    </a>
                    <a href="{{ route('librarian.reports.export', 'monthly-summary') }}?year={{ $data['period']['year'] }}&month={{ $data['period']['month'] }}&format=pdf" class="monthly-action-btn bg-red-600 hover:bg-red-700 text-white border-2 border-red-700">
                        <i class="fas fa-file-pdf mr-1.5"></i> Export PDF
                    </a>
                    <a href="{{ route('librarian.reports.print', 'monthly-summary') }}?year={{ $data['period']['year'] }}&month={{ $data['period']['month'] }}" target="_blank" class="monthly-action-btn bg-white text-[var(--report-title)] border-2 border-[var(--report-title)] hover:bg-[#eef8ea]">
                        <i class="fas fa-print mr-1.5"></i> Print/PDF Report
                    </a>
                </div>
            </div>
        </div>

        <!-- Filter banner -->
        <form method="GET" action="{{ route('librarian.reports.monthly-summary') }}" class="panel-card p-5 mb-6 no-print">
            <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">
                <div class="flex-1">
                    <h3 class="panel-title border-none text-base p-0">Monthly Report Filters</h3>
                    <p class="text-sm text-gray-500 mt-1">Select the month and year you want to query.</p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 w-full lg:w-auto lg:min-w-[540px]">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1 uppercase tracking-wider">Month</label>
                        <select name="month" class="w-full px-3 py-2 border border-slate-300 rounded-xl bg-white focus:ring-2 focus:ring-[var(--report-green)] text-sm">
                            @for($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" {{ (int) $data['period']['month'] === $m ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::create()->month($m)->format('F') }}
                                </option>
                            @endfor
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1 uppercase tracking-wider">Year</label>
                        <select name="year" class="w-full px-3 py-2 border border-slate-300 rounded-xl bg-white focus:ring-2 focus:ring-[var(--report-green)] text-sm">
                            @for($y = now()->year; $y >= now()->year - 5; $y--)
                                <option value="{{ $y }}" {{ (int) $data['period']['year'] === $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="monthly-action-btn w-full bg-[var(--report-green)] text-white hover:bg-[#256a47] border border-[var(--report-green)]">
                            <i class="fas fa-chart-line mr-1.5"></i> Update Statistics
                        </button>
                    </div>
                </div>
            </div>
        </form>

        <!-- ROW 1: Registered Students by Campus & Interpretation -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-6">
            <div class="panel-card p-6 lg:col-span-7 flex flex-col justify-between min-h-[220px]">
                <h3 class="panel-title">Registered Students by Campus</h3>
                <div class="flex items-end justify-between h-[130px] pt-4">
                    @php
                        $campusColors = ['#c0392b', '#2980b9', '#d35400', '#16a085', '#8e44ad'];
                    @endphp
                    @forelse($top5Campuses as $index => $campus)
                        <div class="flex-1 text-center group">
                            <div class="text-xs font-bold text-gray-700 mb-2 opacity-0 group-hover:opacity-100 transition-opacity duration-200">{{ $campus['count'] }}</div>
                            <div class="vertical-bar" style="background: {{ $campusColors[$index % 5] }}; height: {{ max(15, ($campus['count'] / $campusMax) * 90) }}px;" title="{{ $campus['campus'] }}: {{ $campus['count'] }}"></div>
                            <div class="text-[10px] font-bold text-gray-500 mt-2 uppercase tracking-wide truncate max-w-[80px] mx-auto">{{ substr($campus['campus'], 0, 8) }}</div>
                        </div>
                    @empty
                        <p class="w-full text-center text-sm text-gray-400 self-center">No campus distribution data available.</p>
                    @endforelse
                </div>
            </div>
            <div class="panel-card panel-card-dark p-6 lg:col-span-5 flex flex-col justify-between min-h-[220px]">
                <h3 class="panel-title panel-title-white border-b-[#2a634c]">Interpretation</h3>
                <div class="text-sm leading-relaxed text-[#f1f8f1]">
                    This monthly analytics dashboard highlights operational trends for <span class="text-[#ffdd6b] font-bold">{{ $data['period']['month_name'] }}</span>.
                    <br><br>
                    @if($topProgram)
                        The <span class="text-[#ffdd6b] font-bold">{{ $topProgram['program'] }}</span> program recorded the highest overall borrowing count, with <span class="text-[#ffdd6b] font-bold">{{ $topProgram['student_count'] }}</span> active student transactions.
                    @endif
                    Circulation activities show strong participation from the <span class="text-[#ffdd6b] font-bold">{{ $topGender ? ucfirst($topGender['gender']) : 'N/A' }}</span> borrower demographic, accounting for <span class="text-[#ffdd6b] font-bold">{{ $topGender ? $topGender['count'] : '0' }}</span> active library users.
                </div>
            </div>
        </div>

        <!-- ROW 2: Circulation Stats Cards & Demographic Profile -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-6">
            <div class="panel-card p-6 lg:col-span-7">
                <h3 class="panel-title mb-6">Circulation Stats (Type of Transaction)</h3>
                <div class="grid grid-cols-4 gap-4">
                    <div class="text-center group">
                        <div class="transaction-circle border-[#c0392b] group-hover:border-red-600">
                            <span class="text-xl font-extrabold text-[#c0392b]">{{ $data['monthly_stats']['books_borrowed'] }}</span>
                        </div>
                        <div class="text-[10px] font-bold text-gray-600 uppercase tracking-wide">Books<br>Borrowed</div>
                    </div>
                    <div class="text-center group">
                        <div class="transaction-circle border-[#2980b9] group-hover:border-blue-600">
                            <span class="text-xl font-extrabold text-[#2980b9]">{{ $data['monthly_stats']['books_returned'] }}</span>
                        </div>
                        <div class="text-[10px] font-bold text-gray-600 uppercase tracking-wide">Books<br>Returned</div>
                    </div>
                    <div class="text-center group">
                        <div class="transaction-circle border-[#d35400] group-hover:border-orange-600">
                            <span class="text-xl font-extrabold text-[#d35400]">{{ $data['monthly_stats']['new_students'] }}</span>
                        </div>
                        <div class="text-[10px] font-bold text-gray-600 uppercase tracking-wide">New<br>Registered</div>
                    </div>
                    <div class="text-center group">
                        <div class="transaction-circle border-[#16a085] group-hover:border-teal-600">
                            <span class="text-xl font-extrabold text-[#16a085]">{{ $data['monthly_stats']['active_students'] }}</span>
                        </div>
                        <div class="text-[10px] font-bold text-gray-600 uppercase tracking-wide">Active<br>Borrowers</div>
                    </div>
                </div>
            </div>
            <div class="panel-card p-6 lg:col-span-5">
                <h3 class="panel-title mb-4">Demographic Profile</h3>
                <div class="grid grid-cols-2 gap-4 text-center">
                    <div class="p-2 bg-[#f4faff] border border-blue-100 rounded-2xl group transition-all duration-300 hover:shadow-sm">
                        <svg viewBox="0 0 24 24" class="w-8 h-8 fill-[#2980b9] mx-auto mb-2 transform group-hover:scale-110 transition-transform">
                            <path d="M12,2A4,4 0 0,0 8,6A4,4 0 0,0 12,10A4,4 0 0,0 16,6A4,4 0 0,0 12,2M12,12C7.58,12 4,14.24 4,17V20H20V17C20,14.24 16.42,12 12,12Z" />
                        </svg>
                        <div class="text-[10px] font-extrabold text-blue-600 uppercase tracking-wide">Male</div>
                        <div class="text-xl font-extrabold text-[#2980b9] mt-1">{{ number_format($malePercentage, 1) }}%</div>
                        <div class="text-[10px] text-gray-500 font-bold mt-1">{{ $maleCount }} Active</div>
                    </div>
                    <div class="p-2 bg-[#fff5f7] border border-pink-100 rounded-2xl group transition-all duration-300 hover:shadow-sm">
                        <svg viewBox="0 0 24 24" class="w-8 h-8 fill-[#ec4899] mx-auto mb-2 transform group-hover:scale-110 transition-transform">
                            <path d="M12,2A4,4 0 0,0 8,6A4,4 0 0,0 12,10A4,4 0 0,0 16,6A4,4 0 0,0 12,2M12,12C7.58,12 4,14.24 4,17V20H20V17C20,14.24 16.42,12 12,12Z" />
                        </svg>
                        <div class="text-[10px] font-extrabold text-pink-600 uppercase tracking-wide">Female</div>
                        <div class="text-xl font-extrabold text-[#ec4899] mt-1">{{ number_format($femalePercentage, 1) }}%</div>
                        <div class="text-[10px] text-gray-500 font-bold mt-1">{{ $femaleCount }} Active</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ROW 3: Book Distribution & Resource donut chart -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-6">
            <div class="panel-card p-6 lg:col-span-7 flex flex-col justify-between">
                <h3 class="panel-title">Book Distribution by Course</h3>
                @if($booksProgCount > 0)
                    <div class="text-center mt-6 w-full overflow-hidden">
                        <svg viewBox="0 0 320 120" class="w-full max-h-[120px] mx-auto">
                            <line x1="20" y1="20" x2="300" y2="20" stroke="#e6eee6" stroke-width="1" />
                            <line x1="20" y1="55" x2="300" y2="55" stroke="#e6eee6" stroke-width="1" />
                            <line x1="20" y1="90" x2="300" y2="90" stroke="#e6eee6" stroke-width="1" />
                            
                            <polyline points="{{ trim($booksPolylinePoints) }}" fill="none" stroke="#b22222" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
                            
                            @foreach($booksCoordinates as $pt)
                                <g class="cursor-pointer group">
                                    <circle cx="{{ $pt['x'] }}" cy="{{ $pt['y'] }}" r="4.5" fill="#ffffff" stroke="#b22222" stroke-width="2.5" class="transition-all duration-300 hover:r-6" />
                                    <text x="{{ $pt['x'] }}" y="{{ $pt['y'] - 8 }}" font-size="8" font-family="DejaVu Sans" font-weight="bold" fill="#b22222" text-anchor="middle">{{ $pt['count'] }}</text>
                                    <text x="{{ $pt['x'] }}" y="105" font-size="8" font-family="DejaVu Sans" font-weight="bold" fill="#1e4d3a" text-anchor="middle">{{ $pt['label'] }}</text>
                                </g>
                            @endforeach
                        </svg>
                    </div>
                @else
                    <p class="text-center text-sm text-gray-400 py-10">No course distribution data available.</p>
                @endif
            </div>
            <div class="panel-card p-6 lg:col-span-5 flex flex-col justify-between">
                <h3 class="panel-title">Resource Type by Borrowing Activity</h3>
                <div class="text-center mt-4">
                    <svg viewBox="0 0 100 100" class="w-24 h-24 inline-block transform hover:scale-105 transition-transform duration-300">
                        <circle cx="50" cy="50" r="25" fill="none" stroke="#e2eee2" stroke-width="14" />
                        @foreach($resStats as $stat)
                            <circle cx="50" cy="50" r="25" fill="none" stroke="{{ $stat['color'] }}" stroke-width="14" 
                                    stroke-dasharray="{{ $stat['strokeLength'] }} 157" 
                                    stroke-dashoffset="{{ $stat['strokeOffset'] }}"
                                    transform="rotate(-90 50 50)" />
                        @endforeach
                    </svg>
                    <div class="mt-4 flex flex-wrap justify-center gap-3">
                        @foreach($resStats as $stat)
                            <span class="inline-flex items-center text-xs font-bold" style="color: {{ $stat['color'] }};">
                                <span class="w-2.5 h-2.5 rounded-full mr-1.5" style="background-color: {{ $stat['color'] }};"></span>
                                {{ $stat['category'] }} ({{ number_format($stat['percent'], 0) }}%)
                            </span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- ROW 4: Top Student Borrowers & Daily Library activity timeline -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-6">
            <div class="panel-card p-6 lg:col-span-7 flex flex-col justify-between">
                <h3 class="panel-title mb-4">Top Student Borrowers</h3>
                <div class="overflow-x-auto">
                    <table class="infographic-table w-full">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Course</th>
                                <th class="text-center">Borrows</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($data['top_students']->take(4) as $student)
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td><strong>{{ $student['name'] }}</strong><br><small class="text-gray-500 font-bold">ID: {{ $student['library_id'] }}</small></td>
                                    <td>{{ $student['course'] }}</td>
                                    <td class="text-center font-extrabold text-[var(--report-green)]">{{ $student['borrow_count'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-gray-500 py-6">No active student activity.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="panel-card p-6 lg:col-span-5 flex flex-col justify-between">
                <h3 class="panel-title">Daily Library Activity Timeline</h3>
                <div class="text-center mt-6 w-full overflow-hidden">
                    <svg viewBox="0 0 320 120" class="w-full max-h-[120px] mx-auto">
                        <line x1="20" y1="20" x2="300" y2="20" stroke="#e6eee6" stroke-width="1" />
                        <line x1="20" y1="55" x2="300" y2="55" stroke="#e6eee6" stroke-width="1" />
                        <line x1="20" y1="90" x2="300" y2="90" stroke="#e6eee6" stroke-width="1" />
                        
                        <polyline points="{{ trim($dailyPointsBorrows) }}" fill="none" stroke="#16a085" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                        <polyline points="{{ trim($dailyPointsReturns) }}" fill="none" stroke="#e67e22" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                        
                        @foreach($dailyCoordinates as $pt)
                            <g class="cursor-pointer">
                                <circle cx="{{ $pt['x'] }}" cy="{{ $pt['yB'] }}" r="3" fill="#ffffff" stroke="#16a085" stroke-width="2" title="Borrows: {{ $pt['borrows'] }}" />
                                <circle cx="{{ $pt['x'] }}" cy="{{ $pt['yR'] }}" r="3" fill="#ffffff" stroke="#e67e22" stroke-width="2" title="Returns: {{ $pt['returns'] }}" />
                                <text x="{{ $pt['x'] }}" y="105" font-size="8" font-family="DejaVu Sans" font-weight="bold" fill="#1e4d3a" text-anchor="middle">Day {{ $pt['label'] }}</text>
                            </g>
                        @endforeach
                    </svg>
                    <div class="mt-2 flex justify-center gap-4 text-xs font-bold">
                        <span class="text-[#16a085]">
                            <span class="inline-block w-3 h-1 bg-[#16a085] rounded mr-1"></span> Borrows
                        </span>
                        <span class="text-[#e67e22]">
                            <span class="inline-block w-3 h-1 bg-[#e67e22] rounded mr-1"></span> Returns
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ROW 5: Most Borrowed Books (Top 10) -->
        <div class="panel-card p-6 mb-6">
            <h3 class="panel-title mb-4">Most Borrowed Books (Top 10)</h3>
            <div class="overflow-x-auto">
                <table class="infographic-table w-full">
                    <thead>
                        <tr>
                            <th width="50">Rank</th>
                            <th>Book Title</th>
                            <th>Author</th>
                            <th width="150" class="text-center">Borrow Tally</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($popularBooks->take(6) as $index => $book)
                            <tr class="hover:bg-[#fcfdfc] transition-colors">
                                <td><span class="inline-block bg-[#e2efe2] text-[#1e4d3a] font-extrabold text-xs px-2.5 py-0.5 rounded-full">#{{ $index + 1 }}</span></td>
                                <td><strong>{{ $book['title'] }}</strong></td>
                                <td>{{ $book['author'] }}</td>
                                <td class="text-center font-extrabold text-[var(--report-teal)]">{{ $book['borrow_count'] }} borrows</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-gray-500 py-6">No borrowing activity recorded this month.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Interpretational note -->
        <div class="panel-card bg-[#edf7eb] p-5 shadow-inner">
            <p class="text-sm text-gray-700 leading-relaxed">
                <strong class="text-[var(--report-title)]">Interpretation Summary:</strong>
                This digital analytics dashboard reflects live records retrieved from your system databases. All percentages, circulations, dynamic campus counts, course timelines, and Top 10 rankings are recalculated instantly as you filters months or years, supporting accurate monthly library reporting.
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
