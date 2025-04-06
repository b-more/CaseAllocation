<?php

namespace App\Filament\Resources\InvestigatorStatsResource\Pages;

use App\Filament\Resources\InvestigatorStatsResource;
use App\Models\InquiryFile;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\DB;

class ListInvestigatorStats extends ListRecords
{
    protected static string $resource = InvestigatorStatsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Add export all investigators to PDF
            \Filament\Actions\Action::make('export_all_pdf')
                ->label('Export All to PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->url(fn () => route('investigator.export-all-pdf'))
                ->openUrlInNewTab()
                ->color('success'),
        ];
    }

    /**
     * Generate a PDF report for one or more investigators
     *
     * @param array $investigatorIds
     * @return \Barryvdh\DomPDF\PDF
     */
    protected function generatePDF(array $investigatorIds)
    {
        // Get the investigators
        $investigators = \App\Models\User::whereIn('id', $investigatorIds)->get();

        // Prepare data for each investigator
        $investigatorsData = [];
        foreach ($investigators as $investigator) {
            // Get case counts
            $totalAssigned = \App\Models\InquiryFile::where('dealing_officer', $investigator->id)->count();
            $underInvestigation = \App\Models\InquiryFile::where('dealing_officer', $investigator->id)
                ->where('if_status_id', 2) // Under Investigation status
                ->count();
            $takenToCourt = \App\Models\InquiryFile::where('dealing_officer', $investigator->id)
                ->where('if_status_id', 4) // Taken to Court status
                ->count();
            $casesClosed = \App\Models\InquiryFile::where('dealing_officer', $investigator->id)
                ->where('if_status_id', 5) // Case Closed status
                ->count();
            $activeCases = \App\Models\InquiryFile::where('dealing_officer', $investigator->id)
                ->whereNotIn('if_status_id', [5]) // Excluding closed cases
                ->count();

            // Get recent cases (up to 5)
            $recentCases = \App\Models\InquiryFile::where('dealing_officer', $investigator->id)
                ->latest('created_at')
                ->limit(5)
                ->get();

            // Get case distribution by status
            $casesByStatus = \App\Models\InquiryFile::where('dealing_officer', $investigator->id)
                ->select('if_status_id', \Illuminate\Support\Facades\DB::raw('count(*) as total'))
                ->groupBy('if_status_id')
                ->with('status')
                ->get();

            // Add to investigator data array
            $investigatorsData[] = [
                'investigator' => $investigator,
                'totalAssigned' => $totalAssigned,
                'underInvestigation' => $underInvestigation,
                'takenToCourt' => $takenToCourt,
                'casesClosed' => $casesClosed,
                'activeCases' => $activeCases,
                'recentCases' => $recentCases,
                'casesByStatus' => $casesByStatus,
            ];
        }

        // Generate PDF
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.investigator-stats', [
            'investigatorsData' => $investigatorsData,
            'generatedDate' => now()->format('d M Y H:i'),
        ]);

        return $pdf;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\InvestigatorStatsOverviewWidget::class,
        ];
    }

    // Add summary statistics at the top of the page
    public function getTitle(): string
    {
        // Calculate totals
        $totalCases = InquiryFile::count();
        $activeCases = InquiryFile::whereNotIn('if_status_id', [5])->count();
        $underInvestigation = InquiryFile::where('if_status_id', 2)->count();
        $takenToCourt = InquiryFile::where('if_status_id', 4)->count();
        $casesClosed = InquiryFile::where('if_status_id', 5)->count();

        // Get investigator with most active cases
        $mostAssigned = DB::table('users')
            ->join('inquiry_files', 'users.id', '=', 'inquiry_files.dealing_officer')
            ->select('users.name', DB::raw('count(*) as total'))
            ->where('users.role_id', 2) // Investigator role
            ->whereNotIn('inquiry_files.if_status_id', [5]) // Not closed
            ->groupBy('users.id', 'users.name')
            ->orderBy('total', 'desc')
            ->first();

        // Add title with summary statistics
        return "Investigator Case Assignment Statistics";
    }
}
