<?php

namespace App\Filament\Widgets;

use App\Models\InquiryFile;
use App\Models\PinkFile;
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

        // Get counts of pink files assigned to this investigator
        $assignedPinkFiles = PinkFile::where('assigned_to', $user->id)->count();

        // Get counts of inquiry files where this user is the dealing officer
        $assignedInquiryFiles = InquiryFile::where('dealing_officer', $user->id)->count();

        // Get count of unacknowledged inquiry files
        $unacknowledgedFiles = InquiryFile::where('dealing_officer', $user->id)
            ->whereNull('acknowledged_at')
            ->count();

        // Get inquiry files by status
        $investigationCount = InquiryFile::where('dealing_officer', $user->id)
            ->where('if_status_id', 2) // Under Investigation status
            ->count();

        $courtCount = InquiryFile::where('dealing_officer', $user->id)
            ->where('if_status_id', 4) // Taken to Court status
            ->count();

        $closedCount = InquiryFile::where('dealing_officer', $user->id)
            ->where('if_status_id', 5) // Case Closed status
            ->count();

        return [
            Stat::make('Assigned Pink Files', $assignedPinkFiles)
                ->description('Total cases assigned to you')
                ->color('primary'),

            Stat::make('Assigned Inquiry Files', $assignedInquiryFiles)
                ->description('Total inquiry files you are handling')
                ->color('success'),

            Stat::make('Pending Acknowledgment', $unacknowledgedFiles)
                ->description('Files you need to acknowledge')
                ->color($unacknowledgedFiles > 0 ? 'warning' : 'success'),

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
}
