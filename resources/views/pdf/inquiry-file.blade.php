<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>ZPS Anti-Fraud Unit Case Report - {{ $inquiryFile->if_number }}</title>
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
            position: relative;
        }

        .logo-container {
            margin-bottom: 10px;
        }

        .logo-container img {
            height: 80px;
            max-width: 100%;
        }

        .header-text {
            margin-bottom: 5px;
        }

        .organization-name {
            color: #003366;
            font-size: 20px;
            font-weight: bold;
            margin: 0;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .unit-name {
            color: #003366;
            font-size: 18px;
            font-weight: bold;
            margin: 5px 0;
        }

        .report-title {
            color: #003366;
            font-size: 22px;
            font-weight: bold;
            margin: 10px 0 5px;
        }

        .report-subtitle {
            font-size: 16px;
            color: #555;
            margin: 5px 0 0;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 30px;
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
            color: #fff;
            font-size: 16px;
            font-weight: bold;
            margin-top: 5px;
            margin-bottom: 15px;
            padding: 7px 10px;
            background-color: #003366;
            border-radius: 4px;
            text-transform: uppercase;
        }

        /* Info Box Styles */
        .info-box {
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .info-item {
            margin-bottom: 10px;
            display: flex;
            border-bottom: 1px dotted #eee;
            padding-bottom: 5px;
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-item strong {
            display: inline-block;
            width: 200px;
            font-weight: bold;
            color: #003366;
        }

        /* Status Badge Styles */
        .badge {
            display: inline-block;
            padding: 4px 8px;
            font-size: 12px;
            font-weight: bold;
            border-radius: 4px;
            color: #fff;
            text-align: center;
        }

        .badge-gray { background-color: #5c636a; }
        .badge-warning { background-color: #fd7e14; color: #fff; }
        .badge-info { background-color: #0dcaf0; color: #000; }
        .badge-primary { background-color: #0d6efd; }
        .badge-success { background-color: #198754; }

        /* Financial Info Styling */
        .financial-info .info-item strong {
            width: 250px;
        }

        .financial-value-positive {
            color: #198754;
            font-weight: bold;
        }

        .financial-value-negative {
            color: #dc3545;
            font-weight: bold;
        }

        /* Comment Box Styles */
        .comment-box {
            background-color: #e7f5ff;
            border: 1px solid #a8d7ff;
            border-left: 4px solid #0d6efd;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 5px;
        }

        .comment-box .comment-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            color: #0d6efd;
            font-weight: bold;
            border-bottom: 1px solid #cce5ff;
            padding-bottom: 5px;
        }

        .comment-box .comment-body {
            color: #333;
        }

        /* Table Styles */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
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

        /* Investigation Progress */
        .progress-item {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
        }

        .progress-status {
            margin-right: 10px;
            color: #28a745;
            font-size: 16px;
        }

        .progress-status.incomplete {
            color: #dc3545;
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

        .signature-section {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
        }

        .signature-box {
            width: 45%;
        }

        .signature-line {
            border-top: 1px solid #000;
            padding-top: 5px;
            margin-top: 40px;
            text-align: center;
        }

        /* Page Numbers */
        .page-number {
            text-align: center;
            font-size: 11px;
            color: #666;
            margin-top: 20px;
        }

        /* Case file specific styles */
        .case-status-box {
            border-left: 5px solid #003366;
            background-color: #f0f7ff;
            padding: 10px 15px;
            margin-bottom: 15px;
        }

        .file-number {
            font-size: 18px;
            font-weight: bold;
            color: #003366;
            border: 2px solid #003366;
            display: inline-block;
            padding: 5px 10px;
            border-radius: 5px;
            background-color: #f0f7ff;
        }

        /* Print-specific styles */
        @media print {
            body {
                font-size: 12pt;
            }

            .page-break {
                page-break-after: always;
            }

            .section-title {
                background-color: #003366 !important;
                color: white !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            table th {
                background-color: #003366 !important;
                color: white !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
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
            <p class="report-title">OFFICIAL CASE REPORT</p>
            <div class="report-subtitle">
                <span class="file-number">{{ $inquiryFile->if_number }}</span>
                <span>Generated on: {{ $generatedDate }}</span>
            </div>
        </div>
    </div>

    <div class="content">
        <!-- Case Status Overview -->
        <div class="case-status-box">
            <div class="info-item">
                <strong>Current Status:</strong>
                @if($inquiryFile->status)
                    @php
                        $statusColors = [
                            'Inquiry File Opened' => 'badge-gray',
                            'Under Investigation' => 'badge-warning',
                            'Taken to NPA' => 'badge-info',
                            'Taken to Court' => 'badge-primary',
                            'Case Closed' => 'badge-success'
                        ];
                        $badgeClass = $statusColors[$inquiryFile->status->name] ?? 'badge-gray';
                    @endphp
                    <span class="badge {{ $badgeClass }}">{{ $inquiryFile->status->name }}</span>
                @else
                    <span class="badge badge-gray">Not Specified</span>
                @endif
            </div>
            <div class="info-item">
                <strong>Dealing Officer:</strong> {{ $inquiryFile->officer->name ?? 'Not Assigned' }}
            </div>
        </div>

        <!-- File Information Section -->
        <div class="section">
            <h2 class="section-title">File Information</h2>
            <div class="info-box">
                <div class="info-item">
                    <strong>Date Opened:</strong> {{ $inquiryFile->date ? $inquiryFile->date->format('d M Y') : 'N/A' }}
                </div>
                <div class="info-item">
                    <strong>Time Opened:</strong> {{ $inquiryFile->time ? $inquiryFile->time->format('H:i') : 'N/A' }}
                </div>
                <div class="info-item">
                    <strong>CR Number:</strong> {{ $inquiryFile->cr_number ?? 'N/A' }}
                </div>
                <div class="info-item">
                    <strong>Police Station:</strong> {{ $inquiryFile->police_station ?? 'N/A' }}
                </div>
                <div class="info-item">
                    <strong>Acknowledgment Status:</strong>
                    @if($inquiryFile->acknowledged_at)
                        <span style="color: #198754;">Acknowledged on {{ $inquiryFile->acknowledged_at->format('d M Y H:i') }}</span>
                    @else
                        <span style="color: #dc3545;">Not Acknowledged</span>
                    @endif
                </div>
                @if($inquiryFile->pinkFile)
                <div class="info-item">
                    <strong>Original Case Reference:</strong> Pink File #{{ $inquiryFile->pinkFile->id }} ({{ $inquiryFile->pinkFile->complainant_name }})
                </div>
                @endif
            </div>
        </div>

        <!-- Case Details Section -->
        <div class="section">
            <h2 class="section-title">Case Details</h2>
            <div class="info-box">
                <div class="info-item">
                    <strong>Complainant:</strong> {{ $inquiryFile->complainant }}
                </div>
                <div class="info-item">
                    <strong>Offence:</strong> {{ $inquiryFile->offence }}
                </div>
                @if($inquiryFile->court_type)
                <div class="info-item">
                    <strong>Court Type:</strong> {{ $inquiryFile->courtType->name ?? 'N/A' }}
                </div>
                @endif
                @if($inquiryFile->court_stage)
                <div class="info-item">
                    <strong>Court Stage:</strong> {{ $inquiryFile->courtStage->name ?? 'N/A' }}
                </div>
                @endif
                @if($inquiryFile->case_close_reason)
                <div class="info-item">
                    <strong>Case Close Reason:</strong> {{ $inquiryFile->case_close_reason }}
                </div>
                @endif
            </div>
        </div>

        <!-- Financial Information -->
        <div class="section">
            <h2 class="section-title">Financial Information</h2>
            <div class="info-box financial-info">
                <div class="info-item">
                    <strong>Value of Property Stolen:</strong>
                    <span class="financial-value-negative">ZMW {{ number_format($inquiryFile->value_of_property_stolen ?? 0, 2) }}</span>
                </div>
                <div class="info-item">
                    <strong>Value of Property Recovered:</strong>
                    <span class="financial-value-positive">ZMW {{ number_format($inquiryFile->value_of_property_recovered ?? 0, 2) }}</span>
                </div>
                @if($inquiryFile->value_of_property_stolen > 0)
                <div class="info-item">
                    <strong>Recovery Rate:</strong>
                    <span class="financial-value-positive">
                        {{ number_format(($inquiryFile->value_of_property_recovered / $inquiryFile->value_of_property_stolen) * 100, 2) }}%
                    </span>
                </div>
                <div class="info-item">
                    <strong>Amount Still Unrecovered:</strong>
                    <span class="financial-value-negative">
                        ZMW {{ number_format(($inquiryFile->value_of_property_stolen - $inquiryFile->value_of_property_recovered), 2) }}
                    </span>
                </div>
                @endif
            </div>
        </div>

        <!-- Investigation Progress -->
        <div class="section">
            <h2 class="section-title">Investigation Progress</h2>
            <div class="info-box">
                <div class="progress-item">
                    <div class="progress-status {{ $inquiryFile->contacted_complainant ? '' : 'incomplete' }}">
                        {{ $inquiryFile->contacted_complainant ? '✓' : '✗' }}
                    </div>
                    <div>Contacted Complainant</div>
                </div>
                <div class="progress-item">
                    <div class="progress-status {{ $inquiryFile->recorded_statement ? '' : 'incomplete' }}">
                        {{ $inquiryFile->recorded_statement ? '✓' : '✗' }}
                    </div>
                    <div>Recorded Complainant Statement</div>
                </div>
                <div class="progress-item">
                    <div class="progress-status {{ $inquiryFile->apprehended_suspects ? '' : 'incomplete' }}">
                        {{ $inquiryFile->apprehended_suspects ? '✓' : '✗' }}
                    </div>
                    <div>Apprehended Suspect(s)</div>
                </div>
                <div class="progress-item">
                    <div class="progress-status {{ $inquiryFile->warned_cautioned ? '' : 'incomplete' }}">
                        {{ $inquiryFile->warned_cautioned ? '✓' : '✗' }}
                    </div>
                    <div>Warned & Cautioned Suspect(s)</div>
                </div>
                <div class="progress-item">
                    <div class="progress-status {{ $inquiryFile->released_on_bond ? '' : 'incomplete' }}">
                        {{ $inquiryFile->released_on_bond ? '✓' : '✗' }}
                    </div>
                    <div>Released Suspect(s) on Bond</div>
                </div>
            </div>
        </div>

        <!-- Accused Persons Section -->
        <div class="section">
            <h2 class="section-title">Accused Persons</h2>
            @if(count($accusedPersons) > 0)
            <table>
                <thead>
                    <tr>
                        <th width="5%">#</th>
                        <th width="35%">Name</th>
                        <th width="20%">ID/Passport</th>
                        <th width="20%">Contact</th>
                        <th width="20%">Address</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($accusedPersons as $index => $accused)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $accused->name }}</td>
                        <td>{{ $accused->identification ?? 'N/A' }}</td>
                        <td>{{ $accused->contact ?? 'N/A' }}</td>
                        <td>{{ $accused->address ?? 'N/A' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <div class="info-box" style="text-align: center; font-style: italic; color: #666;">
                No accused persons have been recorded for this case
            </div>
            @endif
        </div>

        <!-- OIC Comments Section -->
        <div class="section">
            <h2 class="section-title">OIC Comments & Directions</h2>
            @php
                $oicComments = $statusHistory->filter(function($status) {
                    return !empty($status->oic_comment);
                });
            @endphp

            @if(count($oicComments) > 0)
                @foreach($oicComments as $status)
                <div class="comment-box">
                    <div class="comment-header">
                        <span>{{ $status->user->name }}</span>
                        <span>{{ $status->created_at->format('d M Y H:i') }}</span>
                    </div>
                    <div class="comment-body">
                        {{ $status->oic_comment }}
                    </div>
                </div>
                @endforeach
            @else
            <div class="info-box" style="text-align: center; font-style: italic; color: #666;">
                No OIC comments have been recorded for this case
            </div>
            @endif
        </div>

        <!-- Remarks Section -->
        <div class="section">
            <h2 class="section-title">Investigator Remarks</h2>
            <div class="info-box">
                @if($inquiryFile->remarks)
                    {{ $inquiryFile->remarks }}
                @else
                    <div style="text-align: center; font-style: italic; color: #666;">
                        No remarks have been recorded for this case
                    </div>
                @endif
            </div>
        </div>

        <!-- Status History Section -->
        <div class="section">
            <h2 class="section-title">Case Status History</h2>
            @if(count($statusHistory) > 0)
            <table>
                <thead>
                    <tr>
                        <th width="20%">Date & Time</th>
                        <th width="15%">Updated By</th>
                        <th width="20%">Previous Status</th>
                        <th width="20%">New Status</th>
                        <th width="25%">Reason For Change</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($statusHistory as $status)
                    <tr>
                        <td>{{ $status->created_at->format('d M Y H:i') }}</td>
                        <td>{{ $status->user->name }}</td>
                        <td>
                            @if($status->old_status)
                                @php
                                    $oldStatus = \App\Models\IfStatus::find($status->old_status);
                                    $oldStatusName = $oldStatus ? $oldStatus->name : 'N/A';
                                    $oldStatusColor = match($oldStatusName) {
                                        'Inquiry File Opened' => 'badge-gray',
                                        'Under Investigation' => 'badge-warning',
                                        'Taken to NPA' => 'badge-info',
                                        'Taken to Court' => 'badge-primary',
                                        'Case Closed' => 'badge-success',
                                        default => 'badge-gray',
                                    };
                                @endphp
                                <span class="badge {{ $oldStatusColor }}">{{ $oldStatusName }}</span>
                            @else
                                <span class="badge badge-gray">Initial Status</span>
                            @endif
                        </td>
                        <td>
                            @php
                                $newStatus = \App\Models\IfStatus::find($status->new_status);
                                $newStatusName = $newStatus ? $newStatus->name : 'N/A';
                                $newStatusColor = match($newStatusName) {
                                    'Inquiry File Opened' => 'badge-gray',
                                    'Under Investigation' => 'badge-warning',
                                    'Taken to NPA' => 'badge-info',
                                    'Taken to Court' => 'badge-primary',
                                    'Case Closed' => 'badge-success',
                                    default => 'badge-gray',
                                };
                            @endphp
                            <span class="badge {{ $newStatusColor }}">{{ $newStatusName }}</span>
                        </td>
                        <td>{{ $status->reason ?: 'N/A' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <div class="info-box" style="text-align: center; font-style: italic; color: #666;">
                No status changes have been recorded for this case
            </div>
            @endif
        </div>

        <!-- Signature Section -->
        <div class="signature-section">
            <div class="signature-box">
                <div class="signature-line">
                    Investigating Officer
                </div>
            </div>
            <div class="signature-box">
                <div class="signature-line">
                    Officer in Charge
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>Anti-Fraud & Cyber Crime Unit • Zambia Police Service</p>
            <p>This report is confidential and for official use only.</p>
            <p>&copy; {{ date('Y') }} ZPS Case Management System</p>
        </div>

        <div class="page-number">
            Page 1 of 1 • Case Reference: {{ $inquiryFile->if_number }}
        </div>
    </div>
</body>
</html>
