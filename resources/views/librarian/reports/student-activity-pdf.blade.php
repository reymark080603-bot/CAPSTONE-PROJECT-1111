<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Activity Report</title>
    <style>
        @page { size: A4 landscape; margin: 12mm; }
        body { font-family: Arial, sans-serif; margin: 0; color: #224438; background: #ffffff; font-size: 10px; }
        .report-shell { border: 2px solid #aac9ab; background: #eef6e8; padding: 18px; }
        .report-topbar { border-bottom: 1px solid rgba(31, 91, 69, 0.18); padding-bottom: 14px; margin-bottom: 18px; }
        .report-brand { color: #1f5b45; font-size: 28px; font-weight: 800; text-transform: uppercase; margin: 0 0 6px; }
        .report-subtitle { color: #2d6f55; font-size: 22px; font-weight: 700; text-transform: uppercase; margin: 0; }
        .report-period { margin-top: 6px; }
        .summary-grid { width: 100%; border-collapse: separate; border-spacing: 12px 0; margin: 0 0 18px; }
        .summary-card { width: 33.33%; background: #f9f8ef; border: 1px solid #aac9ab; border-radius: 14px; padding: 12px; }
        .summary-label { color: #2d6f55; font-size: 10px; text-transform: uppercase; }
        .summary-value { font-size: 20px; font-weight: 700; margin-top: 6px; }
        .panel { background: #f9f8ef; border: 1px solid #aac9ab; border-radius: 14px; overflow: hidden; }
        .panel-header { background: #ddeeda; padding: 12px 14px; border-bottom: 1px solid rgba(31, 91, 69, 0.15); }
        .panel-header h2 { margin: 0; color: #1f5b45; font-size: 18px; text-transform: uppercase; }
        .panel-header p { margin: 4px 0 0; color: #2d6f55; font-size: 10px; }
        .panel-body { padding: 14px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 6px 8px; text-align: left; vertical-align: top; word-break: break-word; }
        th { color: #1f5b45; background: rgba(185, 214, 190, 0.45); font-size: 9px; text-transform: uppercase; }
        td { border-bottom: 1px solid #d7e7d5; }
        tr:last-child td { border-bottom: none; }
        .muted { color: #6b7280; }
    </style>
</head>
<body>
    <div class="report-shell">
        <div class="report-topbar">
            <h1 class="report-brand">Knowly</h1>
            <p class="report-subtitle">Student Activity Report</p>
            <p class="report-period">{{ $data['period']['from'] }} to {{ $data['period']['to'] }}</p>
        </div>

        <table class="summary-grid">
            <tr>
                <td class="summary-card"><div class="summary-label">Active Students</div><div class="summary-value">{{ $data['summary']['total_active_students'] }}</div></td>
                <td class="summary-card"><div class="summary-label">Avg. Borrows / Active Student</div><div class="summary-value">{{ $data['summary']['average_borrows_per_student'] }}</div></td>
                <td class="summary-card"><div class="summary-label">Most Active Student</div><div style="font-size: 14px; font-weight: 700; margin-top: 6px;">{{ isset($data['summary']['most_active_student']['name']) ? $data['summary']['most_active_student']['name'] : '-' }}</div></td>
            </tr>
        </table>

        <div class="panel">
            <div class="panel-header"><h2>Students</h2><p>Student borrowing patterns and activity levels</p></div>
            <div class="panel-body">
                <table>
                    <thead>
                        <tr><th>Name</th><th>Library ID</th><th>Course</th><th>Year</th><th>Total Borrowed</th><th>Returned</th><th>Currently Borrowed</th><th>Overdue</th><th>Activity</th></tr>
                    </thead>
                    <tbody>
                        @forelse($data['students'] as $s)
                            <tr><td>{{ $s['name'] }}</td><td>{{ $s['library_id'] }}</td><td>{{ $s['course'] }}</td><td>{{ $s['year'] }}</td><td>{{ $s['total_borrowed'] }}</td><td>{{ $s['total_returned'] }}</td><td>{{ $s['currently_borrowed'] }}</td><td>{{ $s['overdue_books'] }}</td><td>{{ $s['activity_level'] }}</td></tr>
                        @empty
                            <tr><td colspan="9" class="muted">No student activity for this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
