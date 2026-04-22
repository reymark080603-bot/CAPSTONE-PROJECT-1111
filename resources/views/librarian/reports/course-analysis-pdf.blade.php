<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Course Analysis Report</title>
    <style>
        @page { size: A4 landscape; margin: 12mm; }
        body { font-family: Arial, sans-serif; margin: 0; color: #224438; background: #ffffff; font-size: 10px; }
        .report-shell { border: 2px solid #aac9ab; background: #eef6e8; padding: 18px; }
        .report-topbar { border-bottom: 1px solid rgba(31, 91, 69, 0.18); padding-bottom: 14px; margin-bottom: 18px; }
        .report-brand { color: #1f5b45; font-size: 28px; font-weight: 800; text-transform: uppercase; margin: 0 0 6px; }
        .report-subtitle { color: #2d6f55; font-size: 22px; font-weight: 700; text-transform: uppercase; margin: 0; }
        .report-period { margin-top: 6px; }
        .panel { background: #f9f8ef; border: 1px solid #aac9ab; border-radius: 14px; overflow: hidden; margin-bottom: 18px; }
        .panel-header { background: #ddeeda; padding: 12px 14px; border-bottom: 1px solid rgba(31, 91, 69, 0.15); }
        .panel-header h2 { margin: 0; color: #1f5b45; font-size: 18px; text-transform: uppercase; }
        .panel-header p { margin: 4px 0 0; color: #2d6f55; font-size: 10px; }
        .panel-body { padding: 14px; }
        .course-block { margin-bottom: 14px; border: 1px solid #c9ddc8; background: #ffffff; border-radius: 12px; padding: 10px; }
        .course-name { font-weight: 700; color: #1f5b45; text-transform: uppercase; margin-bottom: 8px; }
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
            <p class="report-subtitle">Course Analysis Report</p>
            <p class="report-period">{{ $data['period']['from'] }} to {{ $data['period']['to'] }}</p>
        </div>

        <div class="panel">
            <div class="panel-header"><h2>Course Statistics</h2><p>Borrowing patterns by course and department</p></div>
            <div class="panel-body">
                <table>
                    <thead><tr><th>Course</th><th>Total Students</th><th>Total Borrows</th><th>Active Borrows</th><th>Overdue Borrows</th><th>Borrow Rate / Student</th><th>Overdue Rate %</th></tr></thead>
                    <tbody>
                        @forelse($data['course_statistics'] as $c)
                            <tr><td>{{ $c['course'] ?? '-' }}</td><td>{{ $c['total_students'] }}</td><td>{{ $c['total_borrows'] }}</td><td>{{ $c['active_borrows'] }}</td><td>{{ $c['overdue_borrows'] }}</td><td>{{ $c['borrow_rate_per_student'] }}</td><td>{{ $c['overdue_rate_percent'] }}%</td></tr>
                        @empty
                            <tr><td colspan="7" class="muted">No data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="panel">
            <div class="panel-header"><h2>Top Books by Course</h2><p>Most borrowed books inside each course group</p></div>
            <div class="panel-body">
                @forelse($data['books_by_course'] as $course => $books)
                    <div class="course-block">
                        <div class="course-name">{{ $course ?: '-' }}</div>
                        <table>
                            <thead><tr><th>Title</th><th>Author</th><th>Category</th><th>Borrow Count</th></tr></thead>
                            <tbody>
                                @forelse($books as $b)
                                    <tr><td>{{ $b->title }}</td><td>{{ $b->author }}</td><td>{{ $b->category }}</td><td>{{ $b->borrow_count }}</td></tr>
                                @empty
                                    <tr><td colspan="4" class="muted">No books.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @empty
                    <div class="muted">No course data.</div>
                @endforelse
            </div>
        </div>
    </div>
</body>
</html>
