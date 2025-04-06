<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Welfare Contributions Report {{ $year }}</title>
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
        .section {
            margin-bottom: 30px;
        }
        .section h2 {
            color: #003366;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
            margin-top: 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 12px;
        }
        table th, table td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: center;
        }
        table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .summary-box {
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .summary-item {
            margin-bottom: 10px;
        }
        .summary-item strong {
            display: inline-block;
            width: 180px;
            font-weight: bold;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #ccc;
            padding-top: 10px;
        }
        .paid {
            background-color: #d4edda;
        }
        .unpaid {
            background-color: #f8d7da;
        }
        .excused {
            background-color: #cce5ff;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Anti-Fraud Office - Welfare Contributions Report</h1>
        <p>Year: {{ $year }}</p>
        <p>Generated on: {{ $generatedDate }}</p>
    </div>

    <div class="section">
        <h2>Summary</h2>
        <div class="summary-box">
            <div class="summary-item">
                <strong>Total Officers:</strong> {{ $totalOfficers }}
            </div>
            <div class="summary-item">
                <strong>Total Possible Payments:</strong> {{ $totalPossible }} ({{ $totalOfficers }} officers × 12 months)
            </div>
            <div class="summary-item">
                <strong>Total Payments Collected:</strong> {{ $totalPaid }} ({{ $yearPercentage }}% of possible payments)
            </div>
            <div class="summary-item">
                <strong>Total Amount Collected:</strong> K{{ number_format($totalAmount, 2) }}
            </div>
        </div>
    </div>

    <div class="section">
        <h2>Contributions by Officer</h2>

        <table>
            <thead>
                <tr>
                    <th>Officer</th>
                    @foreach($months as $month)
                        <th>{{ substr($month->name, 0, 3) }}</th>
                    @endforeach
                    <th>Total</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                    <tr>
                        <td>{{ $user->name }}</td>

                        @php
                            $userTotal = 0;
                        @endphp

                        @foreach($months as $month)
                            @php
                                $key = $user->id . '-' . $month->id;
                                $contribution = $contributions[$key][0] ?? null;
                                $status = $contribution ? $contribution->status : 'unpaid';

                                if ($status === 'paid') {
                                    $userTotal++;
                                }
                            @endphp

                            <td class="{{ $status }}">
                                {{ ucfirst(substr($status, 0, 1)) }}
                            </td>
                        @endforeach

                        <td>{{ $userTotal }}/12</td>
                        <td>K{{ number_format($userTotal * 100, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2>Monthly Collection Summary</h2>

        <table>
            <thead>
                <tr>
                    <th>Month</th>
                    <th>Paid</th>
                    <th>Unpaid</th>
                    <th>Excused</th>
                    <th>Collection Rate</th>
                    <th>Amount Collected</th>
                </tr>
            </thead>
            <tbody>
                @foreach($months as $month)
                    @php
                        $monthPaid = 0;
                        $monthUnpaid = 0;
                        $monthExcused = 0;

                        foreach($users as $user) {
                            $key = $user->id . '-' . $month->id;
                            $contribution = $contributions[$key][0] ?? null;
                            $status = $contribution ? $contribution->status : 'unpaid';

                            if ($status === 'paid') {
                                $monthPaid++;
                            } elseif ($status === 'excused') {
                                $monthExcused++;
                            } else {
                                $monthUnpaid++;
                            }
                        }

                        $collectionRate = $totalOfficers > 0 ? round(($monthPaid / $totalOfficers) * 100) : 0;
                    @endphp

                    <tr>
                        <td>{{ $month->name }}</td>
                        <td>{{ $monthPaid }}</td>
                        <td>{{ $monthUnpaid }}</td>
                        <td>{{ $monthExcused }}</td>
                        <td>{{ $collectionRate }}%</td>
                        <td>K{{ number_format($monthPaid * 100, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="footer">
        <p>Anti-Fraud Office Case Management System &copy; {{ date('Y') }}</p>
        <p>This report is confidential and for internal use only.</p>
    </div>
</body>
</html>
