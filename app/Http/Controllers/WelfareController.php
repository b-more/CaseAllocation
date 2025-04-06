<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\WelfareContribution;
use App\Models\WelfareMonth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class WelfareController extends Controller
{
    /**
     * Display payment history for a specific user
     */
    public function history(Request $request, $userId)
    {
        // Authorization check
        if (!Auth::check()) {
            abort(403);
        }

        // Only allow users to view their own history unless they are OIC or Admin
        if (Auth::id() != $userId && !in_array(Auth::user()->role_id, [1, 3])) {
            abort(403, 'You are not authorized to view this payment history.');
        }

        $user = User::findOrFail($userId);
        $years = WelfareContribution::where('user_id', $userId)
            ->select('year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();

        // If no years found, use current year
        if (empty($years)) {
            $years = [Carbon::now()->year];
        }

        $selectedYear = $request->input('year', $years[0]);

        $months = WelfareMonth::orderBy('id')->get();
        $contributions = WelfareContribution::where('user_id', $userId)
            ->where('year', $selectedYear)
            ->get()
            ->keyBy('month_id');

        return view('welfare.history', compact('user', 'months', 'contributions', 'years', 'selectedYear'));
    }

    /**
     * Export welfare contribution report as PDF
     */
    public function exportReport(Request $request)
    {
        // Authorization check
        if (!in_array(Auth::user()->role_id, [1, 3])) { // OIC or Admin
            abort(403);
        }

        $year = $request->input('year', Carbon::now()->year);
        $months = WelfareMonth::orderBy('id')->get();
        $users = User::where('is_active', true)->orderBy('name')->get();

        // Get all contributions for the year
        $contributions = WelfareContribution::where('year', $year)
            ->get()
            ->groupBy(function ($item) {
                return $item->user_id . '-' . $item->month_id;
            });

        // Calculate statistics
        $totalOfficers = $users->count();
        $totalPossible = $totalOfficers * 12;
        $totalPaid = WelfareContribution::where('year', $year)
            ->where('status', 'paid')
            ->count();

        $totalAmount = $totalPaid * 100; // K100 per contribution
        $yearPercentage = $totalPossible > 0
            ? round(($totalPaid / $totalPossible) * 100)
            : 0;

        // Generate PDF
        $pdf = PDF::loadView('pdf.welfare-report', [
            'year' => $year,
            'months' => $months,
            'users' => $users,
            'contributions' => $contributions,
            'totalOfficers' => $totalOfficers,
            'totalPossible' => $totalPossible,
            'totalPaid' => $totalPaid,
            'totalAmount' => $totalAmount,
            'yearPercentage' => $yearPercentage,
            'generatedDate' => Carbon::now()->format('d M Y H:i'),
        ]);

        return $pdf->download('welfare-contributions-' . $year . '.pdf');
    }
}
