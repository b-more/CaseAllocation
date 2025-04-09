<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Anti-Fraud Office - Case Statistics Report</title>
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
        }
        table th, table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        table th {
            background-color: #f2f2f2;
        }
        .total-row {
            font-weight: bold;
            background-color: #f2f2f2;
        }
        .text-right {
            text-align: right;
        }
        .info-box {
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #ccc;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>INGEPOL RESEARCH</h1>
        <p>POLICE SERVICE HEADQUATERS</p>
        <p>LUSAKA</p>
        <p>REF: ANNUAL RETURNS FOR THE PERIOD {{ $period }}</p>
    </div>

    <p>I am reporting to you sir that during the period {{ $period }}, this office received a total number of {{ $totalCases }} financial and Cyber Crime related cases. Below is the breakdown.</p>

    <div class="section">
        <table>
            <thead>
                <tr>
                    <th>OFFENCES</th>
                    <th class="text-right">NUMBER OF CASES</th>
                    <th class="text-right">VALUE OF PROPERTY STOLEN</th>
                    <th class="text-right">VALUE OF PROPERTY RECOVERED</th>
                    <th class="text-right">TAKEN TO COURT</th>
                    <th class="text-right">INVESTIGATION</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reportData as $data)
                <tr>
                    <td>{{ strtoupper($data['offence']) }}</td>
                    <td class="text-right">{{ $data['cases_count'] }}</td>
                    <td class="text-right">
                        @if($data['value_stolen'] > 0)
                            K{{ number_format($data['value_stolen'], 2) }}
                        @else
                            -
                        @endif
                    </td>
                    <td class="text-right">
                        @if($data['value_recovered'] > 0)
                            K{{ number_format($data['value_recovered'], 2) }}
                        @else
                            -
                        @endif
                    </td>
                    <td class="text-right">{{ $data['court_cases'] > 0 ? $data['court_cases'] : 'NIL' }}</td>
                    <td class="text-right">{{ $data['investigation_cases'] > 0 ? $data['investigation_cases'] : 'NIL' }}</td>
                </tr>
                @endforeach
                <tr class="total-row">
                    <td>TOTAL</td>
                    <td class="text-right">{{ $totalCases }}</td>
                    <td class="text-right">K{{ number_format($totalStolen, 2) }}</td>
                    <td class="text-right">K{{ number_format($totalRecovered, 2) }}</td>
                    <td class="text-right">{{ $totalCourtCases }}</td>
                    <td class="text-right">{{ $totalInvestigationCases }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="section">
        <p><strong>STAFF WELFARE:</strong> All officers and their families are reported well</p>

        <p><strong>LEAVE:</strong> NO officer is currently on leave.</p>

        <p><strong>INTERDICTION:</strong> None</p>
    </div>

    <div style="margin-top: 50px;">
        <div style="float: left; width: 60%;">
            <p>S. Namiluko (S/SUPT)</p>
            <p>Officer-In Charge</p>
            <p>Anti-Fraud and Cyber Crime Unit</p>
            <p>For/<strong>INSPECTOR GENERAL OF POLICE.</strong></p>
        </div>
    </div>

    <div class="footer" style="margin-top: 100px; font-size: 8px;">
        <p>Generated on {{ $generatedDate }} from the Anti-Fraud Office Case Management System</p>
    </div>
</body>
</html>
