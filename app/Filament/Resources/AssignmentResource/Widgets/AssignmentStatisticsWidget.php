<?php

namespace App\Filament\Widgets;

use App\Models\Assignment;
use App\Models\InquiryFile;
use App\Models\PinkFile;
use App\Models\User;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AssignmentStatisticsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $user = Auth::user();

        // For investigators, show their personal stats
        if ($user->role_id == 2) {
            return $this->getInvestigatorStats($user->id);
        }

        // For OIC and Admin, show overall stats
        return $this->getOICStats();
    }

    private function getInvestigatorStats($userId)
    {
        // Count total assigned cases for this investigator
        $totalAssigned = Assignment::where('assigned_to', $userId)->count();

        // Count cases by status
        $casesByStatus = InquiryFile::where('dealing_officer', $userId)
            ->select('if_status_id', DB::raw('count(*) as total'))
            ->groupBy('if_status_id')
            ->pluck('total', 'if_status_id')
            ->toArray();

        // Pending acknowledgment
        $pendingAcknowledgment = InquiryFile::where('dealing_officer', $userId)
            ->whereNull('acknowledged_at')
            ->count();

        // Recent assignments (last 7 days)
        $recentAssignments = Assignment::where('assigned_to', $userId)
            ->where('assigned_at', '>=', Carbon::now()->subDays(7))
            ->count();

        // Calculate case closure rate
        $closedCases = $casesByStatus[5] ?? 0; // Status 5 is "Case Closed"
        $closureRate = $totalAssigned > 0 ? round(($closedCases / $totalAssigned) * 100, 1) : 0;

        return [
            Stat::make('Total Assigned Cases', $totalAssigned)
                ->description('All cases assigned to you')
                ->color('primary'),

            Stat::make('Pending Acknowledgment', $pendingAcknowledgment)
                ->description('Cases that need your acknowledgment')
                ->color($pendingAcknowledgment > 0 ? 'danger' : 'success'),

            Stat::make('Case Closure Rate', $closureRate . '%')
                ->description($closedCases . ' of ' . $totalAssigned . ' cases closed')
                ->color('success'),

            Stat::make('Recent Assignments', $recentAssignments)
                ->description('New cases in last 7 days')
                ->color('info'),
        ];
    }

    private function getOICStats()
    {
        // Total cases in the system
        $totalCases = PinkFile::count();

        // Cases with inquiry files
        $casesWithInquiryFiles = PinkFile::whereHas('inquiryFile')->count();

        // Conversion rate (Pink files -> Inquiry files)
        $conversionRate = $totalCases > 0 ? round(($casesWithInquiryFiles / $totalCases) * 100, 1) : 0;

        // Cases by priority
        $highPriorityCases = PinkFile::whereIn('priority', ['high', 'very_high'])->count();

        // Cases by status
        $casesByStatus = InquiryFile::select('if_status_id', DB::raw('count(*) as total'))
            ->groupBy('if_status_id')
            ->pluck('total', 'if_status_id')
            ->toArray();

        // Unacknowledged cases
        $unacknowledgedCases = InquiryFile::whereNull('acknowledged_at')->count();

        // Case closure rate
        $closedCases = $casesByStatus[5] ?? 0; // Status 5 is "Case Closed"
        $totalInquiryCases = InquiryFile::count();
        $closureRate = $totalInquiryCases > 0 ? round(($closedCases / $totalInquiryCases) * 100, 1) : 0;

        // Officers with most active cases
        $topOfficers = User::where('role_id', 2) // Investigators
            ->withCount(['inquiryFiles' => function ($query) {
                $query->whereNotIn('if_status_id', [5]); // Exclude closed cases
            }])
            ->orderBy('inquiry_files_count', 'desc')
            ->limit(5)
            ->get();

        $officerStats = '';
        foreach ($topOfficers as $index => $officer) {
            $officerStats .= ($index + 1) . '. ' . $officer->name . ': ' . $officer->inquiry_files_count . ' cases';
            if ($index < count($topOfficers) - 1) {
                $officerStats .= ' • ';
            }
        }

        return [
            Stat::make('Total Cases', $totalCases)
                ->description('Total cases in the system')
                ->color('primary'),

            Stat::make('Inquiry Files Created', $casesWithInquiryFiles)
                ->description('Conversion rate: ' . $conversionRate . '%')
                ->color('success'),

            Stat::make('High Priority Cases', $highPriorityCases)
                ->description('Cases marked as high or very high priority')
                ->color($highPriorityCases > 10 ? 'danger' : 'warning'),

            Stat::make('Unacknowledged Cases', $unacknowledgedCases)
                ->description('Files pending acknowledgment')
                ->color($unacknowledgedCases > 0 ? 'danger' : 'success'),

            Stat::make('Case Closure Rate', $closureRate . '%')
                ->description($closedCases . ' of ' . $totalInquiryCases . ' inquiry files closed')
                ->color('success'),

            Stat::make('Top Officers by Active Cases', '')
                ->description($officerStats)
                ->color('info'),
        ];
    }
}
