<?php

namespace App\Filament\Widgets;

use App\Models\InquiryFile;
use App\Models\PinkFile;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InvestigatorDashboardWidget extends BaseWidget
{
    protected static ?string $pollingInterval = null;

    // Show for any user
    public static function canView(): bool
    {
        return true;
    }

    protected function getStats(): array
    {
        $user = Auth::user();

        // Different stats based on role
        if ($user->role_id == 2) { // Investigator
            return $this->getInvestigatorStats($user->id);
        } else {
            return $this->getOicOrAdminStats();
        }
    }

    private function getInvestigatorStats($userId)
    {
        // Get counts of pink files assigned to this investigator
        $assignedPinkFiles = PinkFile::where('assigned_to', $userId)->count();

        // Get pending acknowledgment cases (pink files without inquiry files)
        $pendingAcknowledgmentPinkFiles = PinkFile::where('assigned_to', $userId)
            ->whereDoesntHave('inquiryFile')
            ->count();

        // Get counts of inquiry files where this user is the dealing officer
        $assignedInquiryFiles = InquiryFile::where('dealing_officer', $userId)->count();

        // Get count of unacknowledged inquiry files
        $unacknowledgedFiles = InquiryFile::where('dealing_officer', $userId)
            ->whereNull('acknowledged_at')
            ->count();

        // Total pending acknowledgments (both types)
        $totalPendingAcknowledgments = $pendingAcknowledgmentPinkFiles + $unacknowledgedFiles;

        // Get inquiry files by status
        $investigationCount = InquiryFile::where('dealing_officer', $userId)
            ->where('if_status_id', 2) // Under Investigation status
            ->count();

        $courtCount = InquiryFile::where('dealing_officer', $userId)
            ->where('if_status_id', 4) // Taken to Court status
            ->count();

        $closedCount = InquiryFile::where('dealing_officer', $userId)
            ->where('if_status_id', 5) // Case Closed status
            ->count();

        // Get recent OIC comments
        $recentOicComments = \App\Models\CaseStatus::whereHas('case', function ($query) use ($userId) {
                $query->where('dealing_officer', $userId);
            })
            ->whereNotNull('oic_comment')
            ->count();

        return [
            Stat::make('Assigned Pink Files', $assignedPinkFiles)
                ->description('Total cases assigned to you')
                ->color('primary'),

            Stat::make('Pending Acknowledgments', $totalPendingAcknowledgments)
                ->description('Files you need to acknowledge')
                ->color($totalPendingAcknowledgments > 0 ? 'warning' : 'success'),

            Stat::make('OIC Comments', $recentOicComments)
                ->description('Comments/directions from OIC')
                ->color($recentOicComments > 0 ? 'info' : 'gray'),

            Stat::make('Under Investigation', $investigationCount)
                ->description('Cases in active investigation')
                ->color('info'),

            Stat::make('In Court', $courtCount)
                ->description('Cases taken to court')
                ->color('primary'),

            Stat::make('Closed Cases', $closedCount)
                ->description('Completed cases')
                ->color('gray'),
        ];
    }

    private function getOicOrAdminStats()
    {
        // Total pink files
        $totalPinkFiles = PinkFile::count();

        // Total inquiry files
        $totalInquiryFiles = InquiryFile::count();

        // Files pending acknowledgment
        $pendingAcknowledgments = InquiryFile::whereNull('acknowledged_at')->count();

        // Pink files without inquiry files
        $pendingInquiryFiles = PinkFile::whereDoesntHave('inquiryFile')->count();

        // Inquiry files by status
        $underInvestigation = InquiryFile::where('if_status_id', 2)->count();
        $inCourt = InquiryFile::where('if_status_id', 4)->count();
        $closed = InquiryFile::where('if_status_id', 5)->count();

        // Investigators with most active cases
        $topInvestigators = \App\Models\User::where('role_id', 2)
            ->withCount(['inquiryFiles' => function ($query) {
                $query->whereNotIn('if_status_id', [5]); // Exclude closed cases
            }])
            ->orderByDesc('inquiry_files_count')
            ->limit(3)
            ->get()
            ->map(function ($user) {
                return [
                    'name' => $user->name,
                    'count' => $user->inquiry_files_count,
                ];
            });

        // Format top investigators for display
        $topInvestigatorsText = $topInvestigators->map(function ($investigator, $index) {
            return ($index + 1) . '. ' . $investigator['name'] . ' (' . $investigator['count'] . ')';
        })->join(' • ');

        return [
            Stat::make('Total Cases', $totalPinkFiles)
                ->description('Pink files in the system')
                ->color('primary'),

            Stat::make('Inquiry Files', $totalInquiryFiles)
                ->description('Total inquiry files created')
                ->color('info'),

            Stat::make('Pending Acknowledgments', $pendingAcknowledgments)
                ->description('Inquiry files not yet acknowledged')
                ->color($pendingAcknowledgments > 0 ? 'warning' : 'success'),

            Stat::make('Pending Inquiry Files', $pendingInquiryFiles)
                ->description('Pink files without inquiry files')
                ->color($pendingInquiryFiles > 0 ? 'warning' : 'success'),

            Stat::make('Cases Under Investigation', $underInvestigation)
                ->description('Active investigations')
                ->color('info'),

            Stat::make('Top Investigators', $topInvestigatorsText ?: 'None')
                ->description('By active case count')
                ->color('gray'),
        ];
    }
}
