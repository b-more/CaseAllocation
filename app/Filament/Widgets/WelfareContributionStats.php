<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Models\WelfareContribution;
use App\Models\WelfareMonth;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class WelfareContributionStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = null;

    // Only show on the welfare contributions page
    public static function canView(): bool
    {
        return request()->routeIs('filament.admin.resources.welfare-contributions.*');
    }

    protected function getStats(): array
    {
        $year = session('welfare_year', Carbon::now()->year);
        $month = Carbon::now()->month;
        $monthName = Carbon::create()->month($month)->format('F');

        // Total officers count
        $totalOfficers = User::where('is_active', true)->count();

        // Current month stats
        $currentMonthPaid = WelfareContribution::where('month_id', $month)
            ->where('year', $year)
            ->where('status', 'paid')
            ->count();

        $currentMonthPercentage = $totalOfficers > 0
            ? round(($currentMonthPaid / $totalOfficers) * 100)
            : 0;

        // Total contributions for the year
        $totalPaid = WelfareContribution::where('year', $year)
            ->where('status', 'paid')
            ->count();

        $totalPossible = $totalOfficers * 12;
        $yearPercentage = $totalPossible > 0
            ? round(($totalPaid / $totalPossible) * 100)
            : 0;

        // Total amount collected
        $totalAmount = $totalPaid * 100; // K100 per contribution

        // Officers with perfect payment record
        $perfectPaymentOfficers = DB::table('users')
            ->join('welfare_contributions', 'users.id', '=', 'welfare_contributions.user_id')
            ->select('users.id', 'users.name', DB::raw('COUNT(*) as payment_count'))
            ->where('welfare_contributions.year', $year)
            ->where('welfare_contributions.status', 'paid')
            ->groupBy('users.id', 'users.name')
            ->having('payment_count', '=', $month) // Perfect record would have payments equal to current month
            ->get()
            ->count();

        $perfectPercentage = $totalOfficers > 0
            ? round(($perfectPaymentOfficers / $totalOfficers) * 100)
            : 0;

        return [
            Stat::make($monthName . ' ' . $year, $currentMonthPaid . ' of ' . $totalOfficers . ' officers')
                ->description($currentMonthPercentage . '% paid for current month')
                ->descriptionIcon($currentMonthPercentage >= 75 ? 'heroicon-m-check-circle' : 'heroicon-m-clock')
                ->color($currentMonthPercentage >= 75 ? 'success' : ($currentMonthPercentage >= 50 ? 'warning' : 'danger')),

            Stat::make('Year-to-date (' . $year . ')', $totalPaid . ' of ' . $totalPossible . ' payments')
                ->description($yearPercentage . '% of annual contributions collected')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('primary'),

            Stat::make('Total Collected', 'K' . number_format($totalAmount, 2))
                ->description('K100 per officer per month')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Perfect Payment Record', $perfectPaymentOfficers . ' officers')
                ->description($perfectPercentage . '% have paid all months to date')
                ->descriptionIcon('heroicon-m-star')
                ->color('warning'),
        ];
    }
}
