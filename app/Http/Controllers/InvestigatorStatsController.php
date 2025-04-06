<?php

namespace App\Http\Controllers;

use App\Models\InquiryFile;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvestigatorStatsController extends Controller
{
    /**
     * Export a single investigator's stats to PDF
     */
    public function exportPdf($id)
    {
        // Authorization check
        if (!in_array(auth()->user()->role_id, [1, 3])) { // OIC or Admin
            abort(403);
        }

        $pdf = $this->generateInvestigatorPDF([$id]);
        $investigator = User::find($id);
        $filename = 'investigator-stats-' . ($investigator ? $investigator->name : $id) . '-' . now()->format('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Export multiple investigators' stats to PDF
     */
    public function exportBulkPdf(Request $request)
    {
        // Authorization check
        if (!in_array(auth()->user()->role_id, [1, 3])) { // OIC or Admin
            abort(403);
        }

        // Get IDs from the comma-separated string
        $ids = explode(',', $request->ids);

        $pdf = $this->generateInvestigatorPDF($ids);
        $filename = 'investigator-stats-bulk-' . now()->format('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Export all investigators' stats to PDF
     */
    public function exportAllPdf()
    {
        // Authorization check
        if (!in_array(auth()->user()->role_id, [1, 3])) { // OIC or Admin
            abort(403);
        }

        // Get all active investigator IDs
        $ids = User::where('role_id', 2)
            ->where('is_active', true)
            ->pluck('id')
            ->toArray();

        $pdf = $this->generateInvestigatorPDF($ids);
        $filename = 'all-investigators-stats-' . now()->format('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Generate a PDF report for one or more investigators
     *
     * @param array $investigatorIds
     * @return \Barryvdh\DomPDF\PDF
     */
    protected function generateInvestigatorPDF(array $investigatorIds)
    {
        // Get the investigators
        $investigators = User::whereIn('id', $investigatorIds)->get();

        // Prepare data for each investigator
        $investigatorsData = [];
        foreach ($investigators as $investigator) {
            // Get case counts
            $totalAssigned = InquiryFile::where('dealing_officer', $investigator->id)->count();
            $underInvestigation = InquiryFile::where('dealing_officer', $investigator->id)
                ->where('if_status_id', 2) // Under Investigation status
                ->count();
            $takenToCourt = InquiryFile::where('dealing_officer', $investigator->id)
                ->where('if_status_id', 4) // Taken to Court status
                ->count();
            $casesClosed = InquiryFile::where('dealing_officer', $investigator->id)
                ->where('if_status_id', 5) // Case Closed status
                ->count();
            $activeCases = InquiryFile::where('dealing_officer', $investigator->id)
                ->whereNotIn('if_status_id', [5]) // Excluding closed cases
                ->count();

            // Get recent cases (up to 5)
            $recentCases = InquiryFile::where('dealing_officer', $investigator->id)
                ->latest('created_at')
                ->limit(5)
                ->get();

            // Get case distribution by status
            $casesByStatus = InquiryFile::where('dealing_officer', $investigator->id)
                ->select('if_status_id', DB::raw('count(*) as total'))
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
        $pdf = PDF::loadView('pdf.investigator-stats', [
            'investigatorsData' => $investigatorsData,
            'generatedDate' => now()->format('d M Y H:i'),
        ]);

        return $pdf;
    }
}
