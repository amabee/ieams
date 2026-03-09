<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #1e2a3a; padding-bottom: 10px; }
        .header h2 { margin: 5px 0; color: #1e2a3a; }
        .header p { margin: 3px 0; color: #666; }
        .info-table { width: 100%; margin-bottom: 15px; }
        .info-table td { padding: 4px 8px; }
        .info-table td:first-child { font-weight: bold; width: 120px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background-color: #1e2a3a; color: white; padding: 8px; text-align: left; font-size: 10px; }
        td { padding: 6px 8px; border-bottom: 1px solid #ddd; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .status-badge { padding: 3px 8px; border-radius: 3px; font-size: 9px; font-weight: bold; text-transform: uppercase; }
        .status-present { background-color: #d4edda; color: #155724; }
        .status-late { background-color: #fff3cd; color: #856404; }
        .status-absent { background-color: #f8d7da; color: #721c24; }
        .status-on_leave { background-color: #d1ecf1; color: #0c5460; }
        .summary-box { background: #f8f9fa; padding: 10px; margin: 10px 0; border-left: 4px solid #1e2a3a; }
        .summary-box h4 { margin: 0 0 8px 0; color: #1e2a3a; font-size: 12px; }
        .summary-grid { display: table; width: 100%; }
        .summary-item { display: table-cell; padding: 5px 10px; text-align: center; border-right: 1px solid #ddd; }
        .summary-item:last-child { border-right: none; }
        .summary-item .label { font-size: 9px; color: #666; text-transform: uppercase; }
        .summary-item .value { font-size: 16px; font-weight: bold; color: #1e2a3a; }
        .footer { margin-top: 30px; padding-top: 10px; border-top: 1px solid #ddd; text-align: center; font-size: 9px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h2>{{ $organizationName ?? 'IEAMS' }}</h2>
        <p>{{ $title }}</p>
        <p>Generated: {{ now()->format('F d, Y h:i A') }}</p>
    </div>

    <table class="info-table">
        <tr>
            <td>Report Type:</td>
            <td>{{ $reportType }}</td>
            <td>Date Range:</td>
            <td>{{ $dateFrom }} to {{ $dateTo }}</td>
        </tr>
        @if($branch)
        <tr>
            <td>Branch:</td>
            <td colspan="3">{{ $branch }}</td>
        </tr>
        @endif
    </table>

    @if(isset($summary))
    <div class="summary-box">
        <h4>Summary Statistics</h4>
        <div class="summary-grid">
            <div class="summary-item">
                <div class="label">Total Records</div>
                <div class="value">{{ $summary['total'] ?? 0 }}</div>
            </div>
            <div class="summary-item">
                <div class="label">Present</div>
                <div class="value" style="color:#28a745">{{ $summary['present'] ?? 0 }}</div>
            </div>
            <div class="summary-item">
                <div class="label">Late</div>
                <div class="value" style="color:#ffc107">{{ $summary['late'] ?? 0 }}</div>
            </div>
            <div class="summary-item">
                <div class="label">Absent</div>
                <div class="value" style="color:#dc3545">{{ $summary['absent'] ?? 0 }}</div>
            </div>
            <div class="summary-item">
                <div class="label">On Leave</div>
                <div class="value" style="color:#17a2b8">{{ $summary['on_leave'] ?? 0 }}</div>
            </div>
        </div>
    </div>
    @endif

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Employee No</th>
                <th>Employee Name</th>
                <th>Branch</th>
                <th>Time In</th>
                <th>Time Out</th>
                <th>Hours</th>
                <th>Status</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            @forelse($records as $record)
            <tr>
                <td>{{ $record->date->format('M d, Y') }}</td>
                <td>{{ $record->employee->employee_no }}</td>
                <td>{{ $record->employee->full_name }}</td>
                <td>{{ $record->branch->name }}</td>
                <td>{{ $record->time_in ?? '—' }}</td>
                <td>{{ $record->time_out ?? '—' }}</td>
                <td>{{ $record->hours_worked ?? '—' }}</td>
                <td><span class="status-badge status-{{ $record->status }}">{{ ucfirst(str_replace('_', ' ', $record->status)) }}</span></td>
                <td>{{ $record->notes ?? '—' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="9" style="text-align:center; padding:20px; color:#999;">No records found for the selected criteria.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>This is a system-generated report from the Integrated Employee Attendance Monitoring System (IEAMS)</p>
        <p>Report generated on {{ now()->format('F d, Y \a\t h:i A') }}</p>
    </div>
</body>
</html>
