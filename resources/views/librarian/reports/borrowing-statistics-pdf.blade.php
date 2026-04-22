<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Borrowing Statistics Report</title>
    <style>
        @page { size: A4; margin: 12mm; }
        body { font-family: Arial, sans-serif; margin: 0; color: #224438; background: #ffffff; font-size: 12px; }
        .report-shell { border: 2px solid #aac9ab; background: #eef6e8; padding: 20px; }
        .report-topbar { border-bottom: 1px solid rgba(31, 91, 69, 0.18); padding-bottom: 14px; margin-bottom: 18px; }
        .report-brand { color: #1f5b45; font-size: 28px; font-weight: 800; text-transform: uppercase; margin: 0 0 6px; }
        .report-subtitle { color: #2d6f55; font-size: 22px; font-weight: 700; text-transform: uppercase; margin: 0; }
        .report-period { margin-top: 6px; }
        .summary-grid { width: 100%; border-collapse: separate; border-spacing: 12px 0; margin: 0 0 18px; }
        .summary-card { width: 33.33%; background: #f9f8ef; border: 1px solid #aac9ab; border-radius: 14px; padding: 14px; }
        .summary-label { color: #2d6f55; font-size: 11px; text-transform: uppercase; }
        .summary-value { font-size: 28px; font-weight: 700; margin-top: 6px; }
        .section-grid { width: 100%; border-collapse: separate; border-spacing: 14px 0; margin: 0; }
        .section-cell { width: 50%; vertical-align: top; }
        .panel { background: #f9f8ef; border: 1px solid #aac9ab; border-radius: 14px; overflow: hidden; }
        .panel-header { background: #ddeeda; padding: 12px 14px; border-bottom: 1px solid rgba(31, 91, 69, 0.15); }
        .panel-header h2 { margin: 0; color: #1f5b45; font-size: 18px; text-transform: uppercase; }
        .panel-header p { margin: 4px 0 0; color: #2d6f55; font-size: 11px; }
        .panel-body { padding: 14px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px 10px; text-align: left; vertical-align: top; word-break: break-word; }
        th { color: #1f5b45; background: rgba(185, 214, 190, 0.45); font-size: 11px; text-transform: uppercase; }
        td { border-bottom: 1px solid #d7e7d5; }
        tr:last-child td { border-bottom: none; }
        .muted { color: #6b7280; }
    </style>
</head>
<body>
    <div class="report-shell">
        <div class="report-topbar">
            <h1 class="report-brand">Knowly</h1>
            <p class="report-subtitle">Borrowing Statistics Report</p>
            <p class="report-period">{{ $data['period']['from'] }} to {{ $data['period']['to'] }}</p>
        </div>

        <table class="summary-grid">
            <tr>
                <td class="summary-card">
                    <div class="summary-label">Total Borrows</div>
                    <div class="summary-value">{{ $data['summary']['total_borrows'] }}</div>
                </td>
                <td class="summary-card">
                    <div class="summary-label">Total Returns</div>
                    <div class="summary-value">{{ $data['summary']['total_returns'] }}</div>
                </td>
                <td class="summary-card">
                    <div class="summary-label">Average Per Day</div>
                    <div class="summary-value">{{ $data['summary']['average_per_day'] }}</div>
                </td>
            </tr>
        </table>

        <table class="section-grid">
            <tr>
                <td class="section-cell">
                    <div class="panel">
                        <div class="panel-header">
                            <h2>Monthly Breakdown</h2>
                            <p>Monthly borrowing activity summary</p>
                        </div>
                        <div class="panel-body">
                            <table>
                                <thead><tr><th>Period</th><th>Count</th></tr></thead>
                                <tbody>
                                    @forelse($data['monthly_data'] as $m)
                                        <tr><td>{{ $m['period'] }}</td><td>{{ $m['count'] }}</td></tr>
                                    @empty
                                        <tr><td colspan="2" class="muted">No data.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </td>
                <td class="section-cell">
                    <div class="panel">
                        <div class="panel-header">
                            <h2>Category Breakdown</h2>
                            <p>Borrowing grouped by category</p>
                        </div>
                        <div class="panel-body">
                            <table>
                                <thead><tr><th>Category</th><th>Count</th></tr></thead>
                                <tbody>
                                    @forelse($data['category_data'] as $c)
                                        <tr><td>{{ $c->category ?? '-' }}</td><td>{{ $c->count }}</td></tr>
                                    @empty
                                        <tr><td colspan="2" class="muted">No data.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
