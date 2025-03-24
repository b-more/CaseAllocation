<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>ZPS Anti-Fraud Office Official Statistics Report</title>
    <style>
        /* Global Styles */
        body {
            font-family: 'Calibri', 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #fff;
        }

        /* Header Styles */
        .report-header {
            text-align: center;
            padding: 20px 0;
            border-bottom: 3px solid #003366;
            background-color: #f9f9f9;
        }

        .logo-container {
            margin-bottom: 10px;
        }

        .logo-container img {
            height: 100px;
            max-width: 100%;
        }

        .header-text {
            margin-bottom: 5px;
        }

        .organization-name {
            color: #003366;
            font-size: 22px;
            font-weight: bold;
            margin: 0;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .unit-name {
            color: #003366;
            font-size: 20px;
            font-weight: bold;
            margin: 5px 0;
        }

        .report-title {
            color: #003366;
            font-size: 24px;
            font-weight: bold;
            margin: 10px 0 5px;
            text-transform: uppercase;
        }

        .report-subtitle {
            font-size: 16px;
            color: #555;
            margin: 5px 0 0;
        }

        .confidential-stamp {
            color: #D32F2F;
            border: 2px solid #D32F2F;
            display: inline-block;
            padding: 2px 8px;
            font-weight: bold;
            font-size: 14px;
            transform: rotate(-5deg);
            position: absolute;
            top: 20px;
            right: 20px;
        }

        /* Content Container */
        .content {
            padding: 20px 30px;
        }

        /* Section Styles */
        .section {
            margin-bottom: 25px;
            page-break-inside: avoid;
        }

        .section-title {
            color: #003366;
            font-size: 18px;
            font-weight: bold;
            margin-top: 5px;
            margin-bottom: 15px;
            padding-bottom: 5px;
            border-bottom: 2px solid #003366;
            text-transform: uppercase;
        }

        /* Key Metrics Box */
        .key-metrics {
            background-color: #f0f7ff;
            border: 1px solid #d0e3ff;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .key-metrics-title {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #003366;
        }

        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .metric-item {
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 10px;
            text-align: center;
        }

        .metric-value {
            font-size: 22px;
            font-weight: bold;
            color: #003366;
            margin: 5px 0;
        }

        .metric-label {
            font-size: 14px;
            color: #555;
        }

        .recovery-metric .metric-value {
            color: #2E7D32; /* Green for positive financial indicators */
        }

        /* Tables */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            background-color: #fff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        table th {
            background-color: #003366;
            color: white;
            font-weight: bold;
            text-align: left;
            padding: 10px;
            border: 1px solid #ddd;
        }

        table td {
            padding: 8px 10px;
            border: 1px solid #ddd;
        }

        table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        table tr:hover {
            background-color: #f1f1f1;
        }

        /* Two Column Layout */
        .two-column {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        .column {
            flex: 1;
            min-width: 0;
        }

        /* Chart Placeholders */
        .chart-container {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            height: 250px;
            text-align: center;
            position: relative;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .chart-placeholder-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: #888;
            font-style: italic;
        }

        /* Financial Analysis */
        .financial-metrics {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
        }

        .financial-item {
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
        }

        .financial-label {
            font-weight: bold;
            color: #333;
        }

        .financial-value {
            font-weight: bold;
            color: #003366;
        }

        .financial-value.positive {
            color: #2E7D32;
        }

        .financial-value.negative {
            color: #D32F2F;
        }

        /* Performance Indicators */
        .performance-indicator {
            margin-bottom: 5px;
        }

        .rank-1 td, .rank-2 td, .rank-3 td {
            font-weight: bold;
        }

        .rank-1 td {
            background-color: #fff8e1; /* Gold tint for 1st */
        }

        /* Footer */
        .footer {
            margin-top: 30px;
            border-top: 2px solid #003366;
            padding-top: 15px;
            text-align: center;
            font-size: 12px;
            color: #666;
            page-break-inside: avoid;
        }

        .footer-seal {
            margin-bottom: 10px;
        }

        .footer-text {
            margin: 5px 0;
        }

        /* Page Numbers */
        .page-number {
            position: absolute;
            bottom: 20px;
            right: 20px;
            font-size: 12px;
            color: #666;
        }

        /* Print-specific styles */
        @media print {
            body {
                font-size: 12pt;
            }

            .page-break {
                page-break-after: always;
            }
        }
    </style>
</head>
<body>
    <!-- Report Header -->
    <div class="report-header">
        <div class="confidential-stamp">CONFIDENTIAL</div>
        <div class="logo-container">
            <img src="{{ public_path('imgs/logo.png') }}" alt="Zambia Police Service Logo">
        </div>
        <div class="header-text">
            <p class="organization-name">ZAMBIA POLICE SERVICE</p>
            <p class="unit-name">ANTI-FRAUD & CYBER CRIME UNIT</p>
            <p class="report-title">Statistical Analysis Report</p>
            <p class="report-subtitle">For the period ending {{ date('d F Y', strtotime($generatedDate)) }}</p>
        </div>
    </div>

    <div class="content">
        <!-- Executive Summary -->
        <div class="section">
            <h2 class="section-title">Executive Summary</h2>
            <p>This report provides a comprehensive analysis of case management statistics for the Anti-Fraud & Cyber Crime Unit. The data presented herein highlights key performance indicators, case distributions, financial recovery metrics, and officer performance statistics as requested by High Command.</p>
        </div>

        <!-- Key Performance Metrics -->
        <div class="section">
            <h2 class="section-title">Key Performance Indicators</h2>
            <div class="key-metrics">
                <div class="metrics-grid">
                    <div class="metric-item">
                        <div class="metric-label">Total Cases</div>
                        <div class="metric-value">{{ number_format($fileTypeStats->sum('total')) }}</div>
                    </div>
                    <div class="metric-item recovery-metric">
                        <div class="metric-label">Recovery Rate</div>
                        <div class="metric-value">{{ number_format($recoveryPercentage, 1) }}%</div>
                    </div>
                    <div class="metric-item">
                        <div class="metric-label">Value Stolen (ZMW)</div>
                        <div class="metric-value">{{ number_format($financialStats->total_stolen, 2) }}</div>
                    </div>
                    <div class="metric-item recovery-metric">
                        <div class="metric-label">Value Recovered (ZMW)</div>
                        <div class="metric-value">{{ number_format($financialStats->total_recovered, 2) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Two Column Section -->
        <div class="two-column">
            <!-- File Type Analysis -->
            <div class="column">
                <div class="section">
                    <h2 class="section-title">Case Distribution by Type</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>File Type</th>
                                <th>Count</th>
                                <th>Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $total = $fileTypeStats->sum('total'); @endphp
                            @foreach($fileTypeStats as $stat)
                            <tr>
                                <td>{{ $stat->fileType ? $stat->fileType->name : 'Unknown' }}</td>
                                <td>{{ number_format($stat->total) }}</td>
                                <td>{{ number_format(($stat->total / $total) * 100, 1) }}%</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="chart-container">
                        <div class="chart-placeholder-text">[Case Distribution Pie Chart]</div>
                    </div>
                </div>
            </div>

            <!-- Case Status Analysis -->
            <div class="column">
                <div class="section">
                    <h2 class="section-title">Case Status Analysis</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th>Count</th>
                                <th>Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $totalStatus = $statusStats->sum('total'); @endphp
                            @foreach($statusStats as $stat)
                            <tr>
                                <td>{{ $stat->status ? $stat->status->name : 'Unknown' }}</td>
                                <td>{{ number_format($stat->total) }}</td>
                                <td>{{ number_format(($stat->total / $totalStatus) * 100, 1) }}%</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="chart-container">
                        <div class="chart-placeholder-text">[Case Status Distribution Chart]</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Officer Performance Analysis -->
        <div class="section">
            <h2 class="section-title">Officer Performance Analysis</h2>
            <table>
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Officer Name</th>
                        <th>Cases Assigned</th>
                        <th>Cases Handled</th>
                        <th>Percentage of Total</th>
                    </tr>
                </thead>
                <tbody>
                    @php $totalCases = $officerStats->sum('total'); @endphp
                    @foreach($officerStats as $index => $stat)
                    <tr class="rank-{{ $index < 3 ? $index + 1 : '' }}">
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $stat->officer ? $stat->officer->name : 'Unknown' }}</td>
                        <td>{{ number_format($stat->total) }}</td>
                        <td>{{ number_format($stat->total) }}</td>
                        <td>{{ number_format(($stat->total / $totalCases) * 100, 1) }}%</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Case Trend Analysis -->
        <div class="section">
            <h2 class="section-title">Case Trend Analysis</h2>
            <div class="two-column">
                <div class="column">
                    <table>
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>New Cases</th>
                                <th>% Change</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $prevCount = null; @endphp
                            @foreach($monthlyStats as $index => $stat)
                            <tr>
                                <td>{{ $stat['month'] }}</td>
                                <td>{{ number_format($stat['count']) }}</td>
                                <td>
                                    @if($index > 0 && $prevCount > 0)
                                        @php
                                            $percentChange = (($stat['count'] - $prevCount) / $prevCount) * 100;
                                            $changeClass = $percentChange >= 0 ? 'positive' : 'negative';
                                            $changeSymbol = $percentChange >= 0 ? '▲' : '▼';
                                        @endphp
                                        <span class="financial-value {{ $changeClass }}">
                                            {{ $changeSymbol }} {{ number_format(abs($percentChange), 1) }}%
                                        </span>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                            @php $prevCount = $stat['count']; @endphp
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="column">
                    <div class="chart-container">
                        <div class="chart-placeholder-text">[Monthly Case Trend Line Chart]</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Financial Recovery Analysis -->
        <div class="section">
            <h2 class="section-title">Financial Recovery Analysis</h2>
            <div class="two-column">
                <div class="column">
                    <div class="financial-metrics">
                        <div class="financial-item">
                            <span class="financial-label">Total Value Stolen:</span>
                            <span class="financial-value negative">ZMW {{ number_format($financialStats->total_stolen, 2) }}</span>
                        </div>
                        <div class="financial-item">
                            <span class="financial-label">Total Value Recovered:</span>
                            <span class="financial-value positive">ZMW {{ number_format($financialStats->total_recovered, 2) }}</span>
                        </div>
                        <div class="financial-item">
                            <span class="financial-label">Recovery Rate:</span>
                            <span class="financial-value positive">{{ number_format($recoveryPercentage, 1) }}%</span>
                        </div>
                        <div class="financial-item">
                            <span class="financial-label">Unrecovered Amount:</span>
                            <span class="financial-value negative">ZMW {{ number_format($financialStats->total_stolen - $financialStats->total_recovered, 2) }}</span>
                        </div>
                    </div>
                </div>
                <div class="column">
                    <div class="chart-container">
                        <div class="chart-placeholder-text">[Financial Recovery Gauge Chart]</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Conclusion -->
        <div class="section">
            <h2 class="section-title">Conclusion & Recommendations</h2>
            <p>Based on the statistical analysis presented in this report, the Anti-Fraud & Cyber Crime Unit demonstrates significant effectiveness in handling and resolving cases. The recovery rate of {{ number_format($recoveryPercentage, 1) }}% indicates efficient investigation and resolution processes. Continued focus on officer training and resource allocation is recommended to further improve case clearance rates and financial recovery metrics.</p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p class="footer-text"><strong>Prepared by:</strong> Anti-Fraud & Cyber Crime Unit</p>
            <p class="footer-text"><strong>Authorized by:</strong> ________________________</p>
            <p class="footer-text">ZAMBIA POLICE SERVICE</p>
            <p class="footer-text">CONFIDENTIAL DOCUMENT</p>
            <p class="footer-text">&copy; {{ date('Y') }} Official Police Use Only</p>
        </div>

        <div class="page-number">Page 1 of 1</div>
    </div>
</body>
</html>
