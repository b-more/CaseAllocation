<x-filament-panels::page>
    <div class="mb-6">
        {{ $this->form }}

        <div class="mt-4 flex justify-end">
            <x-filament::button wire:click="$refresh" color="primary">
                Generate Report
            </x-filament::button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-4 mb-6">
        @php
            $statistics = $this->getStatistics();
            $totals = $statistics['totals'] ?? [];
            $systemStats = $statistics['system_stats'] ?? [];
        @endphp

        <x-filament::section>
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-xl font-bold text-gray-900">Total Cases</h3>
                    <p class="text-3xl font-bold text-primary-600">{{ number_format($totals['total_cases'] ?? 0) }}</p>
                </div>
                <div class="bg-primary-100 p-3 rounded-full">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-xl font-bold text-gray-900">Property Stolen</h3>
                    <p class="text-3xl font-bold text-danger-600">ZMW {{ number_format($totals['total_stolen'] ?? 0, 2) }}</p>
                </div>
                <div class="bg-danger-100 p-3 rounded-full">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-danger-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-xl font-bold text-gray-900">Property Recovered</h3>
                    <p class="text-3xl font-bold text-success-600">ZMW {{ number_format($totals['total_recovered'] ?? 0, 2) }}</p>
                </div>
                <div class="bg-success-100 p-3 rounded-full">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-success-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-xl font-bold text-gray-900">Recovery Rate</h3>
                    <p class="text-3xl font-bold text-gray-800">{{ number_format($totals['recovery_percentage'] ?? 0, 2) }}%</p>
                </div>
                <div class="bg-gray-100 p-3 rounded-full">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
            </div>
        </x-filament::section>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <x-filament::section>
            <x-slot name="heading">Case Status Distribution</x-slot>

            <div class="space-y-4">
                @foreach($statistics['status_distribution'] ?? [] as $status)
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm font-medium text-gray-700">{{ $status['status'] }}</span>
                            <span class="text-sm font-medium text-gray-700">{{ $status['count'] }}</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                            @php
                                $percentage = $totals['total_cases'] > 0 ? ($status['count'] / $totals['total_cases']) * 100 : 0;
                                $color = match($status['status']) {
                                    'Inquiry File Opened' => 'bg-gray-600',
                                    'Under Investigation' => 'bg-amber-500',
                                    'Taken to NPA' => 'bg-blue-500',
                                    'Taken to Court' => 'bg-indigo-500',
                                    'Case Closed' => 'bg-green-500',
                                    default => 'bg-gray-600',
                                };
                            @endphp
                            <div class="{{ $color }} h-2.5 rounded-full" style="width: {{ $percentage }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Monthly Case Trend</x-slot>

            <div class="space-y-4">
                @foreach($statistics['monthly_trend'] ?? [] as $trend)
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm font-medium text-gray-700">{{ $trend['month'] }}</span>
                            <span class="text-sm font-medium text-gray-700">{{ $trend['count'] }} cases</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                            @php
                                $maxCount = collect($statistics['monthly_trend'])->max('count');
                                $percentage = $maxCount > 0 ? ($trend['count'] / $maxCount) * 100 : 0;
                            @endphp
                            <div class="bg-primary-500 h-2.5 rounded-full" style="width: {{ $percentage }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <x-filament::section>
            <x-slot name="heading">Top Officers by Case Count</x-slot>

            <div class="space-y-4">
                @foreach($statistics['top_officers'] ?? [] as $officer)
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm font-medium text-gray-700">{{ $officer['name'] }}</span>
                            <span class="text-sm font-medium text-gray-700">{{ $officer['count'] }} cases</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                            @php
                                $maxCount = collect($statistics['top_officers'])->max('count');
                                $percentage = $maxCount > 0 ? ($officer['count'] / $maxCount) * 100 : 0;
                            @endphp
                            <div class="bg-indigo-500 h-2.5 rounded-full" style="width: {{ $percentage }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Statistics by Offence Type</x-slot>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Offence</th>
                            <th class="px-6 py-3 bg-gray-50 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Cases</th>
                            <th class="px-6 py-3 bg-gray-50 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Court</th>
                            <th class="px-6 py-3 bg-gray-50 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Investigation</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($statistics['offences'] ?? [] as $index => $offence)
                            <tr class="{{ $index % 2 === 0 ? 'bg-white' : 'bg-gray-50' }}">
                                <td class="px-6 py-2 text-sm text-gray-900">{{ $offence['offence'] }}</td>
                                <td class="px-6 py-2 text-sm text-gray-900 text-right">{{ $offence['cases_count'] }}</td>
                                <td class="px-6 py-2 text-sm text-gray-900 text-right">{{ $offence['court_cases'] }}</td>
                                <td class="px-6 py-2 text-sm text-gray-900 text-right">{{ $offence['investigation_cases'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>

    <x-filament::section>
        <x-slot name="heading">Detailed Case Report</x-slot>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Offence</th>
                        <th class="px-6 py-3 bg-gray-50 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Number of Cases</th>
                        <th class="px-6 py-3 bg-gray-50 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Value of Property Stolen</th>
                        <th class="px-6 py-3 bg-gray-50 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Value of Property Recovered</th>
                        <th class="px-6 py-3 bg-gray-50 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Taken to Court</th>
                        <th class="px-6 py-3 bg-gray-50 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Under Investigation</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($statistics['offences'] ?? [] as $index => $offence)
                        <tr class="{{ $index % 2 === 0 ? 'bg-white' : 'bg-gray-50' }}">
                            <td class="px-6 py-2 text-sm text-gray-900">{{ $offence['offence'] }}</td>
                            <td class="px-6 py-2 text-sm text-gray-900 text-right">{{ $offence['cases_count'] }}</td>
                            <td class="px-6 py-2 text-sm text-gray-900 text-right">ZMW {{ number_format($offence['value_stolen'], 2) }}</td>
                            <td class="px-6 py-2 text-sm text-gray-900 text-right">ZMW {{ number_format($offence['value_recovered'], 2) }}</td>
                            <td class="px-6 py-2 text-sm text-gray-900 text-right">{{ $offence['court_cases'] }}</td>
                            <td class="px-6 py-2 text-sm text-gray-900 text-right">{{ $offence['investigation_cases'] }}</td>
                        </tr>
                    @endforeach
                    <tr class="bg-gray-100 font-bold">
                        <td class="px-6 py-2 text-sm text-gray-900">TOTAL</td>
                        <td class="px-6 py-2 text-sm text-gray-900 text-right">{{ $totals['total_cases'] ?? 0 }}</td>
                        <td class="px-6 py-2 text-sm text-gray-900 text-right">ZMW {{ number_format($totals['total_stolen'] ?? 0, 2) }}</td>
                        <td class="px-6 py-2 text-sm text-gray-900 text-right">ZMW {{ number_format($totals['total_recovered'] ?? 0, 2) }}</td>
                        <td class="px-6 py-2 text-sm text-gray-900 text-right">{{ $totals['total_court_cases'] ?? 0 }}</td>
                        <td class="px-6 py-2 text-sm text-gray-900 text-right">{{ $totals['total_investigation_cases'] ?? 0 }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </x-filament::section>

    <div class="text-center text-gray-500 text-sm mt-6">
        <p>Report generated for: {{ $this->getPeriodLabel() }}</p>
        <p>Generated on: {{ \Carbon\Carbon::now()->format('d M Y H:i') }}</p>
    </div>
</x-filament-panels::page>
