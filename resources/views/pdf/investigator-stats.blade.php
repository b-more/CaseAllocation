<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Investigator Case Statistics Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            color: #003366;
            font-size: 24px;
        }
        .header p {
            margin: 5px 0 0;
            color: #666;
            font-size: 14px;
        }
        .investigator-section {
            margin-bottom: 40px;
            page-break-inside: avoid;
        }
        .investigator-header {
            background-color: #f2f2f2;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .investigator-header h2 {
            margin: 0;
            color: #003366;
        }
        .stats-grid {
            display: table;
            width: 100%;
            margin-bottom: 20px;
            border-collapse: separate;
            border-spacing: 10px;
        }
        .stats-card {
            display: table-cell;
            width: 25%;
            background-color: #f9f9f9;
            border-radius: 5px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .stats-card h3 {
            margin: 0 0 10px 0;
            font-size: 16px;
            color: #666;
        }
        .stats-card p {
            margin: 0;
            font-size: 24px;
            font-weight: bold;
            color: #003366;
        }
        .stats-card.warning p {
            color: #f39c12;
        }
        .stats-card.info p {
            color: #3498db;
        }
        .stats-card.success p {
            color: #2ecc71;
        }
        .stats-card.primary p {
            color: #3498db;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table th, table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        table th {
            background-color: #f2f2f2;
        }
        .section-title {
            color: #003366;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
            margin-top: 20px;
            margin-bottom: 15px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #ccc;
            padding-top: 10px;
        }
        .progress-bar {
            background-color: #e9ecef;
            height: 15px;
            width: 100%;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 5px;
        }
        .progress-bar-fill {
            height: 100%;
            background-color: #007bff;
        }
        .badge {
            display: inline-block;
            padding: 3px 7px;
            font-size: 12px;
            font-weight: bold;
            border-radius: 4px;
            color: #fff;
        }
        .badge-gray { background-color: #6c757d; }
        .badge-warning { background-color: #ffc107; color: #212529; }
        .badge-info { background-color: #17a2b8; }
        .badge-primary { background-color: #007bff; }
        .badge-success { background-color: #28a745; }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Anti-Fraud Office Investigator Statistics Report</h1>
        <p>Generated on: {{ $generatedDate }}</p>
    </div>

    @foreach($investigatorsData as $data)
        <div class="investigator-section">
            <div class="investigator-header">
                <h2>{{ $data['investigator']->name }}</h2>
                <p>Email: {{ $data['investigator']->email }} | Phone: {{ $data['investigator']->phone ?? 'N/A' }}</p>
            </div>

            <div class="stats-grid">
                <div class="stats-card">
                    <h3>Total Assigned</h3>
                    <p>{{ $data['totalAssigned'] }}</p>
                </div>
                <div class="stats-card warning">
                    <h3>Under Investigation</h3>
                    <p>{{ $data['underInvestigation'] }}</p>
                </div>
                <div class="stats-card info">
                    <h3>Taken to Court</h3>
                    <p>{{ $data['takenToCourt'] }}</p>
                </div>
                <div class="stats-card success">
                    <h3>Cases Closed</h3>
                    <p>{{ $data['casesClosed'] }}</p>
                </div>
            </div>

            <h3 class="section-title">Case Distribution by Status</h3>
            <table>
                <tr>
                    <th>Status</th>
                    <th>Count</th>
                    <th>Percentage</th>
                </tr>
                @foreach($data['casesByStatus'] as $statusData)
                    <tr>
                        <td>
                            @php
                                $statusColors = [
                                    'Inquiry File Opened' => 'badge-gray',
                                    'Under Investigation' => 'badge-warning',
                                    'Taken to NPA' => 'badge-info',
                                    'Taken to Court' => 'badge-primary',
                                    'Case Closed' => 'badge-success'
                                ];
                                $statusName = $statusData->status->name ?? 'Unknown';
                                $badgeClass = $statusColors[$statusName] ?? 'badge-gray';
                            @endphp
                            <span class="badge {{ $badgeClass }}">{{ $statusName }}</span>
                        </td>
                        <td>{{ $statusData->total }}</td>
                        <td>{{ $data['totalAssigned'] > 0 ? round(($statusData->total / $data['totalAssigned']) * 100, 1) : 0 }}%</td>
                    </tr>
                @endforeach
            </table>

            <h3 class="section-title">Recent Cases (Last 5)</h3>
            @if(count($data['recentCases']) > 0)
                <table>
                    <tr>
                        <th>IF Number</th>
                        <th>Complainant</th>
                        <th>Offense</th>
                        <th>Status</th>
                        <th>Date Assigned</th>
                    </tr>
                    @foreach($data['recentCases'] as $case)
                        <tr>
                            <td>{{ $case->if_number }}</td>
                            <td>{{ $case->complainant }}</td>
                            <td>{{ $case->offence }}</td>
                            <td>
                                @php
                                    $statusColors = [
                                        'Inquiry File Opened' => 'badge-gray',
                                        'Under Investigation' => 'badge-warning',
                                        'Taken to NPA' => 'badge-info',
                                        'Taken to Court' => 'badge-primary',
                                        'Case Closed' => 'badge-success'
                                    ];
                                    $statusName = $case->status->name ?? 'Unknown';
                                    $badgeClass = $statusColors[$statusName] ?? 'badge-gray';
                                @endphp
                                <span class="badge {{ $badgeClass }}">{{ $statusName }}</span>
                            </td>
                            <td>{{ $case->created_at->format('d M Y') }}</td>
                        </tr>
                    @endforeach
                </table>
            @else
                <p>No cases assigned yet.</p>
            @endif
        </div>

        @if(!$loop->last)
            <div class="page-break"></div>
        @endif
    @endforeach

    <div class="footer">
        <p>Anti-Fraud Office Case Management System &copy; {{ date('Y') }}</p>
        <p>This report is confidential and for internal use only.</p>
    </div>
</body>
</html>
