<?php

namespace App\Filament\Widgets;

use App\Models\InquiryFile;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class InvestigatorStatsOverviewWidget extends BaseWidget
{
    protected static ?string $pollingInterval = null;

    // Only show on the investigator stats page
    public static function canView(): bool
    {
        return request()->routeIs('filament.admin.resources.investigator-stats.*');
    }

    protected function getStats(): array
    {
        // Apply date filters if set
        $fromDate = session('stats_filter_from');
        $untilDate = session('stats_filter_until');

        // Base queries with date filters
        $baseQuery = InquiryFile::query();
        if ($fromDate) {
            $baseQuery->whereDate('created_at', '>=', $fromDate);
        }
        if ($untilDate) {
            $baseQuery->whereDate('created_at', '<=', $untilDate);
        }

        // Calculate totals with date filters applied
        $totalCases = (clone $baseQuery)->count();
        $activeCases = (clone $baseQuery)->whereNotIn('if_status_id', [5])->count();
        $underInvestigation = (clone $baseQuery)->where('if_status_id', 2)->count();
        $takenToCourt = (clone $baseQuery)->where('if_status_id', 4)->count();
        $casesClosed = (clone $baseQuery)->where('if_status_id', 5)->count();

        // Get investigator with most active cases
        $mostAssignedQuery = DB::table('users')
            ->join('inquiry_files', 'users.id', '=', 'inquiry_files.dealing_officer')
            ->select('users.name', DB::raw('count(*) as total'))
            ->where('users.role_id', 2) // Investigator role
            ->whereNotIn('inquiry_files.if_status_id', [5]); // Not closed

        // Apply date filters
        if ($fromDate) {
            $mostAssignedQuery->whereDate('inquiry_files.created_at', '>=', $fromDate);
        }
        if ($untilDate) {
            $mostAssignedQuery->whereDate('inquiry_files.created_at', '<=', $untilDate);
        }

        $mostAssigned = $mostAssignedQuery->groupBy('users.id', 'users.name')
            ->orderBy('total', 'desc')
            ->first();

        // Get investigator with fewest active cases
        $leastAssignedQuery = DB::table('users')
            ->join('inquiry_files', 'users.id', '=', 'inquiry_files.dealing_officer')
            ->select('users.name', DB::raw('count(*) as total'))
            ->where('users.role_id', 2) // Investigator role
            ->whereNotIn('inquiry_files.if_status_id', [5]); // Not closed

        // Apply date filters
        if ($fromDate) {
            $leastAssignedQuery->whereDate('inquiry_files.created_at', '>=', $fromDate);
        }
        if ($untilDate) {
            $leastAssignedQuery->whereDate('inquiry_files.created_at', '<=', $untilDate);
        }

        $leastAssigned = $leastAssignedQuery->groupBy('users.id', 'users.name')
            ->orderBy('total', 'asc')
            ->first();

        // Calculate average cases per investigator
        $investigatorCount = User::where('role_id', 2)->where('is_active', true)->count();
        $avgCasesPerInvestigator = $investigatorCount > 0 ? round($activeCases / $investigatorCount, 1) : 0;

        // Determine if filters are active
        $isFiltered = $fromDate || $untilDate;
        $filterDescription = '';
        if ($isFiltered) {
            $from = $fromDate ? Carbon::parse($fromDate)->format('M d, Y') : 'earliest';
            $until = $untilDate ? Carbon::parse($untilDate)->format('M d, Y') : 'latest';
            $filterDescription = "From $from to $until";
        }

        return [
            Stat::make('Total Active Cases', $activeCases)
                ->description($isFiltered ? $filterDescription : 'Cases currently not closed')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary'),

            Stat::make('Under Investigation', $underInvestigation)
                ->description($totalCases > 0 ? round(($underInvestigation / $totalCases) * 100) . '% of all cases' : '0% of all cases')
                ->descriptionIcon('heroicon-m-magnifying-glass')
                ->color('warning'),

            Stat::make('Taken to Court', $takenToCourt)
                ->description($totalCases > 0 ? round(($takenToCourt / $totalCases) * 100) . '% of all cases' : '0% of all cases')
                ->descriptionIcon('heroicon-m-building-library')
                ->color('info'),

            Stat::make('Cases Closed', $casesClosed)
                ->description($totalCases > 0 ? round(($casesClosed / $totalCases) * 100) . '% of all cases' : '0% of all cases')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Average Per Investigator', $avgCasesPerInvestigator)
                ->description('Active cases per investigator')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('gray'),

            Stat::make(
                'Distribution Status',
                $mostAssigned ? $mostAssigned->name . ' (' . $mostAssigned->total . ')' : 'N/A'
            )
                ->description(
                    $leastAssigned ?
                    'Least assigned: ' . $leastAssigned->name . ' (' . $leastAssigned->total . ')' :
                    'No data available'
                )
                ->descriptionIcon('heroicon-m-scale')
                ->color($mostAssigned && $leastAssigned && ($mostAssigned->total > $leastAssigned->total * 2) ? 'danger' : 'success'),
        ];
    }
}
