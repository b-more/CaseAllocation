<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Inquiry File {{ $inquiryFile->if_number }}</title>
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
        .info-box {
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .info-item {
            margin-bottom: 10px;
        }
        .info-item strong {
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
        .badge {
            display: inline-block;
            padding: 3px 7px;
            font-size: 12px;
            font-weight: bold;
            border-radius: 4px;
            color: #fff;
            text-align: center;
        }
        .badge-gray { background-color: #6c757d; }
        .badge-warning { background-color: #ffc107; color: #212529; }
        .badge-info { background-color: #17a2b8; }
        .badge-primary { background-color: #007bff; }
        .badge-success { background-color: #28a745; }
        .comment-box {
            background-color: #e6f7ff;
            border: 1px solid #91d5ff;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Anti-Fraud Office Case Report</h1>
        <p>Inquiry File: {{ $inquiryFile->if_number }}</p>
        <p>Generated on: {{ $generatedDate }}</p>
    </div>

    <div class="section">
        <h2>File Information</h2>
        <div class="info-box">
            <div class="info-item">
                <strong>Inquiry File Number:</strong> {{ $inquiryFile->if_number }}
            </div>
            <div class="info-item">
                <strong>Date:</strong> {{ $inquiryFile->date ? $inquiryFile->date->format('d M Y') : 'N/A' }}
            </div>
            <div class="info-item">
                <strong>Time:</strong> {{ $inquiryFile->time ? $inquiryFile->time->format('H:i') : 'N/A' }}
            </div>
            <div class="info-item">
                <strong>CR Number:</strong> {{ $inquiryFile->cr_number ?? 'N/A' }}
            </div>
            <div class="info-item">
                <strong>Police Station:</strong> {{ $inquiryFile->police_station ?? 'N/A' }}
            </div>
            <div class="info-item">
                <strong>Status:</strong>
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
                    N/A
                @endif
            </div>
            <div class="info-item">
                <strong>Dealing Officer:</strong> {{ $inquiryFile->officer->name ?? 'N/A' }}
            </div>
            <div class="info-item">
                <strong>Acknowledged:</strong> {{ $inquiryFile->acknowledged_at ? 'Yes (' . $inquiryFile->acknowledged_at->format('d M Y H:i') . ')' : 'No' }}
            </div>
        </div>
    </div>

    <div class="section">
        <h2>Case Details</h2>
        <div class="info-box">
            <div class="info-item">
                <strong>Complainant:</strong> {{ $inquiryFile->complainant }}
            </div>
            <div class="info-item">
                <strong>Offence:</strong> {{ $inquiryFile->offence }}
            </div>
            <div class="info-item">
                <strong>Value of Property Stolen:</strong> ZMW {{ number_format($inquiryFile->value_of_property_stolen ?? 0, 2) }}
            </div>
            <div class="info-item">
                <strong>Value of Property Recovered:</strong> ZMW {{ number_format($inquiryFile->value_of_property_recovered ?? 0, 2) }}
            </div>
            @if($inquiryFile->value_of_property_stolen > 0)
            <div class="info-item">
                <strong>Recovery Rate:</strong>
                {{ number_format(($inquiryFile->value_of_property_recovered / $inquiryFile->value_of_property_stolen) * 100, 2) }}%
            </div>
            @endif
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

    <div class="section">
        <h2>Accused Persons</h2>
        @if(count($accusedPersons) > 0)
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>ID/Passport</th>
                    <th>Contact</th>
                    <th>Address</th>
                </tr>
            </thead>
            <tbody>
                @foreach($accusedPersons as $accused)
                <tr>
                    <td>{{ $accused->name }}</td>
                    <td>{{ $accused->identification ?? 'N/A' }}</td>
                    <td>{{ $accused->contact ?? 'N/A' }}</td>
                    <td>{{ $accused->address ?? 'N/A' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p>No accused persons recorded</p>
        @endif
    </div>

    <div class="section">
        <h2>OIC Comments</h2>
        @php
            $oicComments = $statusHistory->filter(function($status) {
                return !empty($status->oic_comment);
            });
        @endphp

        @if(count($oicComments) > 0)
            @foreach($oicComments as $status)
            <div class="comment-box">
                <p><strong>{{ $status->created_at->format('d M Y H:i') }}</strong> - By {{ $status->user->name }}</p>
                <p>{{ $status->oic_comment }}</p>
            </div>
            @endforeach
        @else
        <p>No OIC comments recorded</p>
        @endif
    </div>

    <div class="section">
        <h2>Remarks</h2>
        <div class="info-box">
            {{ $inquiryFile->remarks ?? 'No remarks' }}
        </div>
    </div>

    <div class="section">
        <h2>Status History</h2>
        @if(count($statusHistory) > 0)
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>User</th>
                    <th>Old Status</th>
                    <th>New Status</th>
                    <th>Reason</th>
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
                            Initial Status
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
        <p>No status changes recorded</p>
        @endif
    </div>

    <div class="footer">
        <p>Anti-Fraud Office Case Management System &copy; {{ date('Y') }}</p>
        <p>This report is confidential and for internal use only.</p>
    </div>
</body>
</html>
