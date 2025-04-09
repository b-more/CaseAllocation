<?php

namespace App\Http\Controllers;

use App\Models\InquiryFile;
use App\Models\IfStatus;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    /**
     * Generate a PDF report for case statistics
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function generatePdf(Request $request)
    {
        // Validate request
        $request->validate([
            'period' => 'nullable|string',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date',
        ]);

        // Build query
        $queryBuilder = InquiryFile::query();

        // Apply filters
        if ($request->has('period') && $request->period != 'custom') {
            $period = $request->period;
            $now = Carbon::now();
            $year = $now->year;

            switch ($period) {
                case 'this_week':
                    $queryBuilder->whereBetween('date', [$now->startOfWeek(), $now->endOfWeek()]);
                    $periodLabel = 'This Week';
                    break;
                case 'last_week':
                    $queryBuilder->whereBetween('date', [$now->copy()->subWeek()->startOfWeek(), $now->copy()->subWeek()->endOfWeek()]);
                    $periodLabel = 'Last Week';
                    break;
                case 'this_month':
                    $queryBuilder->whereBetween('date', [$now->startOfMonth(), $now->endOfMonth()]);
                    $periodLabel = 'This Month (' . $now->format('F Y') . ')';
                    break;
                case 'last_month':
                    $queryBuilder->whereBetween('date', [$now->copy()->subMonth()->startOfMonth(), $now->copy()->subMonth()->endOfMonth()]);
                    $periodLabel = 'Last Month (' . $now->copy()->subMonth()->format('F Y') . ')';
                    break;
                case 'this_quarter':
                    $queryBuilder->whereBetween('date', [$now->startOfQuarter(), $now->endOfQuarter()]);
                    $periodLabel = 'This Quarter (Q' . ceil($now->month / 3) . ' ' . $now->year . ')';
                    break;
                case 'last_quarter':
                    $queryBuilder->whereBetween('date', [$now->copy()->subQuarter()->startOfQuarter(), $now->copy()->subQuarter()->endOfQuarter()]);
                    $periodLabel = 'Last Quarter (Q' . ceil($now->copy()->subQuarter()->month / 3) . ' ' . ($now->month < 4 ? $now->year - 1 : $now->year) . ')';
                    break;
                case 'q1':
                    $queryBuilder->whereBetween('date', [Carbon::createFromDate($year, 1, 1), Carbon::createFromDate($year, 3, 31)]);
                    $periodLabel = '1st Quarter ' . $year;
                    break;
                case 'q2':
                    $queryBuilder->whereBetween('date', [Carbon::createFromDate($year, 4, 1), Carbon::createFromDate($year, 6, 30)]);
                    $periodLabel = '2nd Quarter ' . $year;
                    break;
                case 'q3':
                    $queryBuilder->whereBetween('date', [Carbon::createFromDate($year, 7, 1), Carbon::createFromDate($year, 9, 30)]);
                    $periodLabel = '3rd Quarter ' . $year;
                    break;
                case 'q4':
                    $queryBuilder->whereBetween('date', [Carbon::createFromDate($year, 10, 1), Carbon::createFromDate($year, 12, 31)]);
                    $periodLabel = '4th Quarter ' . $year;
                    break;
                case 'this_year':
                    $queryBuilder->whereBetween('date', [$now->startOfYear(), $now->endOfYear()]);
                    $periodLabel = 'This Year (' . $now->year . ')';
                    break;
                case 'last_year':
                    $queryBuilder->whereBetween('date', [$now->copy()->subYear()->startOfYear(), $now->copy()->subYear()->endOfYear()]);
                    $periodLabel = 'Last Year (' . $now->copy()->subYear()->year . ')';
                    break;
                default:
                    $periodLabel = 'All Time';
            }
        } else if ($request->has('from_date') || $request->has('to_date')) {
            // Custom date range
            if ($request->has('from_date')) {
                $queryBuilder->where('date', '>=', $request->from_date);
            }

            if ($request->has('to_date')) {
                $queryBuilder->where('date', '<=', $request->to_date);
            }

            $fromDate = $request->from_date ? Carbon::parse($request->from_date)->format('d M Y') : 'beginning';
            $toDate = $request->to_date ? Carbon::parse($request->to_date)->format('d M Y') : 'now';
            $periodLabel = "Period: $fromDate to $toDate";
        } else {
            // Default to current year if no period specified
            $queryBuilder->whereBetween('date', [Carbon::now()->startOfYear(), Carbon::now()->endOfYear()]);
            $periodLabel = 'This Year (' . Carbon::now()->year . ')';
        }

        // Get unique offences
        $offences = $queryBuilder->distinct()->pluck('offence')->filter()->toArray();

        // Prepare data for each offence
        $reportData = [];
        $totalStolen = 0;
        $totalRecovered = 0;
        $totalCases = 0;
        $totalCourtCases = 0;
        $totalInvestigationCases = 0;

        foreach ($offences as $offence) {
            $filteredQuery = clone $queryBuilder;
            $offenceData = $filteredQuery->where('offence', $offence);

            $casesCount = $offenceData->count();
            $valueStolen = $offenceData->sum('value_of_property_stolen');
            $valueRecovered = $offenceData->sum('value_of_property_recovered');

            // Count cases with "Taken to Court" status (status ID 4)
            $courtCasesCount = $offenceData->where('if_status_id', 4)->count();

            // Count cases with "Under Investigation" status (status ID 2)
            $investigationCasesCount = $offenceData->where('if_status_id', 2)->count();

            $reportData[] = [
                'offence' => $offence,
                'cases_count' => $casesCount,
                'value_stolen' => $valueStolen,
                'value_recovered' => $valueRecovered,
                'court_cases' => $courtCasesCount,
                'investigation_cases' => $investigationCasesCount,
            ];

            $totalStolen += $valueStolen;
            $totalRecovered += $valueRecovered;
            $totalCases += $casesCount;
            $totalCourtCases += $courtCasesCount;
            $totalInvestigationCases += $investigationCasesCount;
        }

        // Generate PDF
        $pdf = PDF::loadView('pdf.report_statistics', [
            'reportData' => $reportData,
            'totalStolen' => $totalStolen,
            'totalRecovered' => $totalRecovered,
            'totalCases' => $totalCases,
            'totalCourtCases' => $totalCourtCases,
            'totalInvestigationCases' => $totalInvestigationCases,
            'period' => $periodLabel,
            'generatedDate' => Carbon::now()->format('d M Y H:i'),
        ]);

        return $pdf->download('case-statistics-' . Carbon::now()->format('Y-m-d') . '.pdf');
    }
}
