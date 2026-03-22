<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monthly Summary Report - {{ $data['period']['month_name'] }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            margin: 0;
            padding: 20px;
            line-height: 1.6;
            color: #2c3e50;
            background: #ffffff;
        }
        .header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 3px solid #3498db;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 20px;
            border-radius: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 32px;
            font-weight: bold;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        .header p {
            margin: 8px 0 0 0;
            font-size: 16px;
            opacity: 0.9;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 25px;
            margin-bottom: 40px;
        }
        .stat-card {
            background: linear-gradient(145deg, #f8f9fa, #e9ecef);
            border: none;
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        .stat-card h3 {
            margin: 0 0 15px 0;
            color: #495057;
            font-size: 16px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .stat-card .number {
            font-size: 42px;
            font-weight: bold;
            color: #2c3e50;
            margin: 10px 0;
        }
        .section {
            margin-bottom: 40px;
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }
        .section h2 {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 25px;
            margin: -25px -25px 20px -25px;
            font-size: 20px;
            font-weight: 600;
            border-radius: 12px 12px 0 0;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        th {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            font-weight: 600;
            padding: 15px 12px;
            text-align: left;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #ecf0f1;
            text-align: left;
            font-size: 14px;
        }
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        tr:hover {
            background-color: #e3f2fd;
            transition: background-color 0.3s ease;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            color: #7f8c8d;
            font-size: 12px;
            padding: 20px;
            border-top: 2px solid #bdc3c7;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .no-data {
            text-align: center;
            color: #7f8c8d;
            font-style: italic;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        @media print {
            body { margin: 5px; padding: 10px; }
            .header { 
                page-break-after: avoid; 
                background: #3498db !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .section { 
                page-break-inside: avoid; 
                box-shadow: none;
                border: 1px solid #ddd;
            }
            .stat-card { 
                page-break-inside: avoid; 
                box-shadow: none;
                border: 1px solid #ddd;
                background: #f8f9fa !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            table { page-break-inside: auto; }
            .section h2 {
                background: #3498db !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            th {
                background: #3498db !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Monthly Summary Report</h1>
        <p>{{ $data['period']['month_name'] }}</p>
        <p>Period: {{ $data['period']['start_date'] }} to {{ $data['period']['end_date'] }}</p>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <h3>Books Borrowed</h3>
            <div class="number">{{ $data['monthly_stats']['books_borrowed'] }}</div>
        </div>
        <div class="stat-card">
            <h3>Books Returned</h3>
            <div class="number">{{ $data['monthly_stats']['books_returned'] }}</div>
        </div>
        <div class="stat-card">
            <h3>New Students</h3>
            <div class="number">{{ $data['monthly_stats']['new_students'] }}</div>
        </div>
        <div class="stat-card">
            <h3>Active Students</h3>
            <div class="number">{{ $data['monthly_stats']['active_students'] }}</div>
        </div>
    </div>

    <div class="section">
        <h2>Daily Activity</h2>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Day</th>
                    <th>Borrows</th>
                    <th>Returns</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['daily_stats'] as $row)
                <tr>
                    <td>{{ $row['date'] }}</td>
                    <td>{{ $row['day_name'] }}</td>
                    <td>{{ $row['borrows'] }}</td>
                    <td>{{ $row['returns'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2>Top Categories</h2>
        <table>
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Borrow Count</th>
                </tr>
            </thead>
            <tbody>
                @forelse($data['top_categories'] as $c)
                <tr>
                    <td>{{ $c->category ?? '—' }}</td>
                    <td><strong>{{ $c->borrow_count }}</strong></td>
                </tr>
                @empty
                <tr>
                    <td colspan="2" class="no-data">No category data for this period.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2>Top Students</h2>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Library ID</th>
                    <th>Course</th>
                    <th>Borrow Count</th>
                </tr>
            </thead>
            <tbody>
                @forelse($data['top_students'] as $s)
                <tr>
                    <td><strong>{{ $s['name'] }}</strong></td>
                    <td>{{ $s['library_id'] }}</td>
                    <td>{{ $s['course'] }}</td>
                    <td><strong>{{ $s['borrow_count'] }}</strong></td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="no-data">No student activity this month.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="footer">
        <p>Generated on {{ now()->format('Y-m-d H:i:s') }} | Knowly Library Management System</p>
    </div>
</body>
</html>
