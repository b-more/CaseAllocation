<!-- resources/views/welfare/history.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welfare Payment History - {{ $user->name }}</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Welfare Payment History</h1>
            <a href="{{ url()->previous() }}" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">Back</a>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex flex-wrap justify-between items-center mb-4">
                <div>
                    <h2 class="text-xl font-semibold">{{ $user->name }}</h2>
                    <p class="text-gray-600">{{ $user->email }}</p>
                </div>

                <div>
                    <form action="{{ route('welfare.history', ['user' => $user->id]) }}" method="GET" class="flex items-center">
                        <label for="year" class="mr-2 font-medium">Year:</label>
                        <select name="year" id="year" class="form-select border rounded py-1 px-3" onchange="this.form.submit()">
                            @foreach($years as $year)
                                <option value="{{ $year }}" {{ $selectedYear == $year ? 'selected' : '' }}>
                                    {{ $year }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full bg-white">
                    <thead>
                        <tr>
                            <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                Month
                            </th>
                            <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                Status
                            </th>
                            <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                Payment Date
                            </th>
                            <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                Amount
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($months as $month)
                            @php
                                $contribution = $contributions[$month->id] ?? null;
                                $statusColors = [
                                    'paid' => 'bg-green-100 text-green-800',
                                    'unpaid' => 'bg-red-100 text-red-800',
                                    'excused' => 'bg-blue-100 text-blue-800',
                                ];
                                $status = $contribution ? $contribution->status : 'unpaid';
                                $statusColor = $statusColors[$status] ?? 'bg-gray-100 text-gray-800';
                            @endphp
                            <tr>
                                <td class="py-3 px-4 border-b border-gray-200">
                                    {{ $month->name }}
                                </td>
                                <td class="py-3 px-4 border-b border-gray-200">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusColor }}">
                                        {{ ucfirst($status) }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 border-b border-gray-200">
                                    {{ $contribution && $contribution->payment_date ? $contribution->payment_date->format('d M Y') : 'N/A' }}
                                </td>
                                <td class="py-3 px-4 border-b border-gray-200">
                                    {{ $contribution && $contribution->status == 'paid' ? 'K' . number_format($contribution->amount ?? 100, 2) : 'N/A' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-6">
                <div class="bg-gray-50 px-4 py-3 sm:px-6 rounded-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-sm font-medium text-gray-700">
                                Total Paid:
                                <span class="font-bold text-green-600">
                                    {{ $contributions->where('status', 'paid')->count() }} months
                                </span>
                            </span>
                        </div>
                        <div>
                            <span class="text-sm font-medium text-gray-700">
                                Total Amount:
                                <span class="font-bold text-green-600">
                                    K{{ number_format($contributions->where('status', 'paid')->count() * 100, 2) }}
                                </span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center text-gray-500 text-sm mt-8">
            &copy; {{ date('Y') }} Anti-Fraud Office - Welfare Contribution System
        </div>
    </div>
</body>
</html>
