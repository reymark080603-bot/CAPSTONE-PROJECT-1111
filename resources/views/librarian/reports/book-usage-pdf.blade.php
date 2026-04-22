<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Book Usage Report</title>
    <style>
        @page { size: A4 landscape; margin: 12mm; }
        body { font-family: Arial, sans-serif; margin: 0; color: #224438; background: #ffffff; font-size: 11px; }
        .report-shell { border: 2px solid #aac9ab; background: #eef6e8; padding: 18px; }
        .report-topbar { border-bottom: 1px solid rgba(31, 91, 69, 0.18); padding-bottom: 14px; margin-bottom: 18px; }
        .report-brand { color: #1f5b45; font-size: 28px; font-weight: 800; text-transform: uppercase; margin: 0 0 6px; }
        .report-subtitle { color: #2d6f55; font-size: 22px; font-weight: 700; text-transform: uppercase; margin: 0; }
        .report-period { margin-top: 6px; }
        .section-grid { width: 100%; border-collapse: separate; border-spacing: 14px 0; margin: 0 0 18px; }
        .section-cell { width: 50%; vertical-align: top; }
        .panel { background: #f9f8ef; border: 1px solid #aac9ab; border-radius: 14px; overflow: hidden; }
        .panel-header { background: #ddeeda; padding: 12px 14px; border-bottom: 1px solid rgba(31, 91, 69, 0.15); }
        .panel-header h2 { margin: 0; color: #1f5b45; font-size: 18px; text-transform: uppercase; }
        .panel-header p { margin: 4px 0 0; color: #2d6f55; font-size: 11px; }
        .panel-body { padding: 14px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 7px 9px; text-align: left; vertical-align: top; word-break: break-word; }
        th { color: #1f5b45; background: rgba(185, 214, 190, 0.45); font-size: 10px; text-transform: uppercase; }
        td { border-bottom: 1px solid #d7e7d5; }
        tr:last-child td { border-bottom: none; }
        .muted { color: #6b7280; }
    </style>
</head>
<body>
    <div class="report-shell">
        <div class="report-topbar">
            <h1 class="report-brand">Knowly</h1>
            <p class="report-subtitle">Book Usage Report</p>
            <p class="report-period">{{ $data['period']['from'] }} to {{ $data['period']['to'] }}</p>
        </div>

        <table class="section-grid">
            <tr>
                <td class="section-cell">
                    <div class="panel">
                        <div class="panel-header"><h2>Most Borrowed Books</h2><p>Top borrowed titles for the selected period</p></div>
                        <div class="panel-body">
                            <table>
                                <thead><tr><th>Title</th><th>Author</th><th>Category</th><th>Course</th><th>Borrow Count</th></tr></thead>
                                <tbody>
                                    @forelse($data['most_borrowed_books'] as $b)
                                        <tr><td>{{ $b->title }}</td><td>{{ $b->author }}</td><td>{{ $b->category }}</td><td>{{ $b->course }}</td><td>{{ $b->borrow_count }}</td></tr>
                                    @empty
                                        <tr><td colspan="5" class="muted">No data.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </td>
                <td class="section-cell">
                    <div class="panel">
                        <div class="panel-header"><h2>Least Borrowed Books</h2><p>Titles with the lowest borrowing activity</p></div>
                        <div class="panel-body">
                            <table>
                                <thead><tr><th>Title</th><th>Author</th><th>Category</th><th>Course</th><th>Borrow Count</th></tr></thead>
                                <tbody>
                                    @forelse($data['least_borrowed_books'] as $b)
                                        <tr><td>{{ $b->title }}</td><td>{{ $b->author }}</td><td>{{ $b->category }}</td><td>{{ $b->course }}</td><td>{{ $b->borrow_count }}</td></tr>
                                    @empty
                                        <tr><td colspan="5" class="muted">No data.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </td>
            </tr>
        </table>

        <div class="panel">
            <div class="panel-header"><h2>Category Usage</h2><p>Borrowing totals and unique books by category</p></div>
            <div class="panel-body">
                <table>
                    <thead><tr><th>Category</th><th>Borrows</th><th>Unique Books</th></tr></thead>
                    <tbody>
                        @forelse($data['category_usage'] as $c)
                            <tr><td>{{ $c->category ?? '-' }}</td><td>{{ $c->borrow_count }}</td><td>{{ $c->unique_books }}</td></tr>
                        @empty
                            <tr><td colspan="3" class="muted">No data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
