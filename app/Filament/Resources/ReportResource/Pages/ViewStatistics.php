<?php

namespace App\Filament\Resources\ReportResource\Pages;

use App\Filament\Resources\ReportResource;
use App\Models\InquiryFile;
use App\Models\PinkFile;
use App\Models\IfStatus;
use Filament\Resources\Pages\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Log;

class ViewStatistics extends Page
{
    protected static string $resource = ReportResource::class;

    protected static string $view = 'filament.resources.report-resource.pages.statistics';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make()
                    ->schema([
                        Forms\Components\Select::make('period')
                            ->label('Reporting Period')
                            ->options([
                                'all_time' => 'All Time',
                                'this_week' => 'This Week',
                                'last_week' => 'Last Week',
                                'this_month' => 'This Month',
                                'last_month' => 'Last Month',
                                'this_quarter' => 'This Quarter',
                                'last_quarter' => 'Last Quarter',
                                'q1' => '1st Quarter',
                                'q2' => '2nd Quarter',
                                'q3' => '3rd Quarter',
                                'q4' => '4th Quarter',
                                'this_year' => 'This Year',
                                'last_year' => 'Last Year',
                                'custom' => 'Custom Date Range',
                            ])
                            ->default('all_time')
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $set('show_date_range', $state === 'custom');
                            }),

                        Forms\Components\DatePicker::make('from_date')
                            ->label('From Date')
                            ->default(Carbon::now()->startOfMonth())
                            ->visible(fn (callable $get) => $get('show_date_range') || $get('period') === 'custom'),

                        Forms\Components\DatePicker::make('to_date')
                            ->label('To Date')
                            ->default(Carbon::now())
                            ->visible(fn (callable $get) => $get('show_date_range') || $get('period') === 'custom'),

                        Forms\Components\Hidden::make('show_date_range')
                            ->default(false),
                    ])
                    ->columns(4),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('download_report')
                ->label('Download PDF Report')
                ->icon('heroicon-o-document-arrow-down')
                ->url(function () {
                    // Get filter parameters
                    $params = [];

                    if (!empty($this->data['period'])) {
                        $params['period'] = $this->data['period'];
                    }

                    if ($this->data['period'] === 'custom') {
                        if (!empty($this->data['from_date'])) {
                            $params['from_date'] = $this->data['from_date'];
                        }

                        if (!empty($this->data['to_date'])) {
                            $params['to_date'] = $this->data['to_date'];
                        }
                    }

                    return route('reports.generate-pdf', $params);
                })
                ->openUrlInNewTab()
                ->color('success'),

            Action::make('view_table')
                ->label('View Table Report')
                ->icon('heroicon-o-table-cells')
                ->url(fn () => ReportResource::getUrl('index'))
                ->color('gray'),
        ];
    }

    public function getStatistics(): array
    {
        // Log total count of inquiry files for debugging
        $totalCount = InquiryFile::count();
        Log::info('Total inquiry files in database: ' . $totalCount);

        $queryBuilder = InquiryFile::query();

        // Apply date filters based on form
        $period = $this->data['period'] ?? 'all_time';
        $fromDate = $this->data['from_date'] ?? null;
        $toDate = $this->data['to_date'] ?? null;

        if ($period === 'custom' && $fromDate && $toDate) {
            $queryBuilder->whereBetween('date', [$fromDate, $toDate]);
        } elseif ($period !== 'all_time') {
            $now = Carbon::now();
            $year = $now->year;

            switch ($period) {
                case 'this_week':
                    $queryBuilder->whereBetween('date', [$now->startOfWeek(), $now->endOfWeek()]);
                    break;
                case 'last_week':
                    $queryBuilder->whereBetween('date', [$now->copy()->subWeek()->startOfWeek(), $now->copy()->subWeek()->endOfWeek()]);
                    break;
                case 'this_month':
                    $queryBuilder->whereBetween('date', [$now->startOfMonth(), $now->endOfMonth()]);
                    break;
                case 'last_month':
                    $queryBuilder->whereBetween('date', [$now->copy()->subMonth()->startOfMonth(), $now->copy()->subMonth()->endOfMonth()]);
                    break;
                case 'this_quarter':
                    $queryBuilder->whereBetween('date', [$now->startOfQuarter(), $now->endOfQuarter()]);
                    break;
                case 'last_quarter':
                    $queryBuilder->whereBetween('date', [$now->copy()->subQuarter()->startOfQuarter(), $now->copy()->subQuarter()->endOfQuarter()]);
                    break;
                case 'q1':
                    $queryBuilder->whereBetween('date', [Carbon::createFromDate($year, 1, 1), Carbon::createFromDate($year, 3, 31)]);
                    break;
                case 'q2':
                    $queryBuilder->whereBetween('date', [Carbon::createFromDate($year, 4, 1), Carbon::createFromDate($year, 6, 30)]);
                    break;
                case 'q3':
                    $queryBuilder->whereBetween('date', [Carbon::createFromDate($year, 7, 1), Carbon::createFromDate($year, 9, 30)]);
                    break;
                case 'q4':
                    $queryBuilder->whereBetween('date', [Carbon::createFromDate($year, 10, 1), Carbon::createFromDate($year, 12, 31)]);
                    break;
                case 'this_year':
                    $queryBuilder->whereBetween('date', [$now->startOfYear(), $now->endOfYear()]);
                    break;
                case 'last_year':
                    $queryBuilder->whereBetween('date', [$now->copy()->subYear()->startOfYear(), $now->copy()->subYear()->endOfYear()]);
                    break;
            }
        }

        // Log count after applying date filters for debugging
        $filteredCount = $queryBuilder->count();
        Log::info('Filtered inquiry files count: ' . $filteredCount);

        // Get distinct offences
        $offences = $queryBuilder->distinct()->pluck('offence')->filter()->values()->toArray();
        Log::info('Distinct offences found: ' . count($offences));

        // Prepare statistics data
        $offenceData = [];
        $totalCases = 0;
        $totalStolen = 0;
        $totalRecovered = 0;
        $totalCourtCases = 0;
        $totalInvestigationCases = 0;

        foreach ($offences as $offence) {
            $filteredQuery = clone $queryBuilder;
            $filteredQuery->where('offence', $offence);

            $casesCount = $filteredQuery->count();
            $valueStolen = $filteredQuery->sum('value_of_property_stolen');
            $valueRecovered = $filteredQuery->sum('value_of_property_recovered');

            // Count cases with specific statuses
            $courtCasesCount = $filteredQuery->where('if_status_id', 4)->count(); // Taken to Court
            $investigationCasesCount = $filteredQuery->where('if_status_id', 2)->count(); // Under Investigation

            $offenceData[] = [
                'offence' => $offence ?: 'Unspecified',
                'cases_count' => $casesCount,
                'value_stolen' => $valueStolen,
                'value_recovered' => $valueRecovered,
                'court_cases' => $courtCasesCount,
                'investigation_cases' => $investigationCasesCount,
            ];

            $totalCases += $casesCount;
            $totalStolen += $valueStolen;
            $totalRecovered += $valueRecovered;
            $totalCourtCases += $courtCasesCount;
            $totalInvestigationCases += $investigationCasesCount;
        }

        // If no offences were found but we have inquiry files, create a fallback entry
        if (empty($offenceData) && $totalCount > 0) {
            // Get overall statistics without offence grouping
            $totalCases = $queryBuilder->count();
            $totalStolen = $queryBuilder->sum('value_of_property_stolen');
            $totalRecovered = $queryBuilder->sum('value_of_property_recovered');
            $totalCourtCases = $queryBuilder->where('if_status_id', 4)->count();
            $totalInvestigationCases = $queryBuilder->where('if_status_id', 2)->count();

            $offenceData[] = [
                'offence' => 'All Cases',
                'cases_count' => $totalCases,
                'value_stolen' => $totalStolen,
                'value_recovered' => $totalRecovered,
                'court_cases' => $totalCourtCases,
                'investigation_cases' => $totalInvestigationCases,
            ];
        }

        // Additional statistics
        $totalFiles = InquiryFile::count();
        $totalPinkFiles = PinkFile::count();

        // Status distribution
        $statusDistribution = [];
        $statuses = IfStatus::all();

        foreach ($statuses as $status) {
            $filteredQuery = clone $queryBuilder;
            $count = $filteredQuery->where('if_status_id', $status->id)->count();

            $statusDistribution[] = [
                'status' => $status->name,
                'count' => $count,
            ];
        }

        // Monthly trend data (last 6 months)
        $monthlyTrend = [];
        $now = Carbon::now();

        for ($i = 5; $i >= 0; $i--) {
            $startDate = Carbon::now()->subMonths($i)->startOfMonth();
            $endDate = Carbon::now()->subMonths($i)->endOfMonth();
            $monthName = Carbon::now()->subMonths($i)->format('M Y');

            $monthQuery = InquiryFile::query();
            $monthQuery->whereBetween('created_at', [$startDate, $endDate]);

            $casesCount = $monthQuery->count();
            $valueStolen = $monthQuery->sum('value_of_property_stolen');
            $valueRecovered = $monthQuery->sum('value_of_property_recovered');

            $monthlyTrend[] = [
                'month' => $monthName,
                'count' => $casesCount,
                'value_stolen' => $valueStolen,
                'value_recovered' => $valueRecovered,
            ];
        }

        // Top officers by case count
        $topOfficers = InquiryFile::select('dealing_officer', DB::raw('count(*) as total'))
            ->whereNotNull('dealing_officer')
            ->groupBy('dealing_officer')
            ->with('officer')
            ->orderBy('total', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->officer ? $item->officer->name : 'Unknown',
                    'count' => $item->total,
                ];
            });

        return [
            'offences' => $offenceData,
            'totals' => [
                'total_cases' => $totalCases,
                'total_stolen' => $totalStolen,
                'total_recovered' => $totalRecovered,
                'total_court_cases' => $totalCourtCases,
                'total_investigation_cases' => $totalInvestigationCases,
                'recovery_percentage' => $totalStolen > 0 ? round(($totalRecovered / $totalStolen) * 100, 2) : 0,
            ],
            'system_stats' => [
                'total_files' => $totalFiles,
                'total_pink_files' => $totalPinkFiles,
            ],
            'status_distribution' => $statusDistribution,
            'monthly_trend' => $monthlyTrend,
            'top_officers' => $topOfficers,
        ];
    }

    public function getPeriodLabel(): string
    {
        $period = $this->data['period'] ?? 'all_time';

        if ($period === 'custom') {
            $fromDate = $this->data['from_date'] ? Carbon::parse($this->data['from_date'])->format('d M Y') : '';
            $toDate = $this->data['to_date'] ? Carbon::parse($this->data['to_date'])->format('d M Y') : '';

            if ($fromDate && $toDate) {
                return "From $fromDate to $toDate";
            }

            return 'Custom Period';
        }

        if ($period === 'all_time') {
            return 'All Time';
        }

        $periodLabels = [
            'this_week' => 'This Week',
            'last_week' => 'Last Week',
            'this_month' => 'This Month',
            'last_month' => 'Last Month',
            'this_quarter' => 'This Quarter',
            'last_quarter' => 'Last Quarter',
            'q1' => '1st Quarter ' . Carbon::now()->year,
            'q2' => '2nd Quarter ' . Carbon::now()->year,
            'q3' => '3rd Quarter ' . Carbon::now()->year,
            'q4' => '4th Quarter ' . Carbon::now()->year,
            'this_year' => 'This Year (' . Carbon::now()->year . ')',
            'last_year' => 'Last Year (' . Carbon::now()->subYear()->year . ')',
        ];

        return $periodLabels[$period] ?? 'Custom Period';
    }
}
