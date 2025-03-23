<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\InvestigatorDashboardWidget;
use App\Filament\Widgets\OicCommentsWidget;
//use App\Filament\Widgets\PendingAcknowledgmentsWidget;
use App\Filament\Widgets\PendingPinkFilesWidget;
use App\Filament\Widgets\RecentAssignmentsWidget;
use App\Models\InquiryFile;
use App\Models\PinkFile;
use App\Models\Role;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Pages\Dashboard as BasePage;
use Illuminate\Support\Facades\Auth;
use Filament\Support\Facades\FilamentView;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

class Dashboard extends BasePage
{
    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?int $navigationSort = -2;

    // Explicitly specify which widgets to use - must be public to match parent class
    public function getWidgets(): array
    {
        return [
            InvestigatorDashboardWidget::class,
            PendingPinkFilesWidget::class,
            //PendingAcknowledgmentsWidget::class,
            OicCommentsWidget::class,
            RecentAssignmentsWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        // Only show download report action for OIC and Admin
        $user = Auth::user();
        if ($user->role_id == 1 || $user->role_id == 3) { // OIC or Admin
            return [
                Action::make('download_report')
                    ->label('Download Statistics PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->action(function () {
                        return response()->streamDownload(function () {
                            $pdf = $this->generateStatisticsPDF();
                            echo $pdf->output();
                        }, 'case-statistics-' . Carbon::now()->format('Y-m-d') . '.pdf');
                    })
                    ->color('success'),
            ];
        }

        return [];
    }

    // Do not use header widgets to prevent duplication
    protected function getHeaderWidgets(): array
    {
        return [];
    }

    // Do not use footer widgets to prevent duplication
    protected function getFooterWidgets(): array
    {
        return [];
    }

    protected function generateStatisticsPDF()
    {
        // Get all the statistics data we need for the report

        // File type statistics
        $fileTypeStats = PinkFile::select('pink_file_type_id', DB::raw('count(*) as total'))
            ->groupBy('pink_file_type_id')
            ->with('fileType')
            ->get();

        // Case status statistics
        $statusStats = InquiryFile::select('if_status_id', DB::raw('count(*) as total'))
            ->groupBy('if_status_id')
            ->with('status')
            ->get();

        // Officer performance
        $officerStats = InquiryFile::select('dealing_officer', DB::raw('count(*) as total'))
            ->groupBy('dealing_officer')
            ->with('officer')
            ->orderBy('total', 'desc')
            ->limit(10)
            ->get();

        // Financial statistics
        $financialStats = InquiryFile::select(
            DB::raw('SUM(value_of_property_stolen) as total_stolen'),
            DB::raw('SUM(value_of_property_recovered) as total_recovered')
        )->first();

        // Calculate recovery percentage
        $recoveryPercentage = 0;
        if ($financialStats->total_stolen > 0) {
            $recoveryPercentage = round(($financialStats->total_recovered / $financialStats->total_stolen) * 100, 2);
        }

        // Monthly trends
        $monthlyStats = collect();
        for ($i = 5; $i >= 0; $i--) {
            $startDate = Carbon::now()->subMonths($i)->startOfMonth();
            $endDate = Carbon::now()->subMonths($i)->endOfMonth();

            $monthName = Carbon::now()->subMonths($i)->format('M Y');
            $count = InquiryFile::whereBetween('created_at', [$startDate, $endDate])->count();

            $monthlyStats->push([
                'month' => $monthName,
                'count' => $count
            ]);
        }

        // Generate the PDF
        $pdf = PDF::loadView('pdf.statistics', [
            'fileTypeStats' => $fileTypeStats,
            'statusStats' => $statusStats,
            'officerStats' => $officerStats,
            'financialStats' => $financialStats,
            'recoveryPercentage' => $recoveryPercentage,
            'monthlyStats' => $monthlyStats,
            'generatedDate' => Carbon::now()->format('d M Y H:i'),
        ]);

        return $pdf;
    }
}
