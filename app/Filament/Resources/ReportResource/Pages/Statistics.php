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

class Statistics extends Page
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
                            ->default('this_month')
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
                ->action(function () {
                    // Generate PDF from the current statistics
                    $statistics = $this->getStatistics();

                    $periodLabel = $this->getPeriodLabel();

                    $pdf = PDF::loadView('pdf.statistics', [
                        'reportData' => $statistics['offences'],
                        'totalStolen' => $statistics['totals']['total_stolen'],
                        'totalRecovered' => $statistics['totals']['total_recovered'],
                        'totalCases' => $statistics['totals']['total_cases'],
                        'totalCourtCases' => $statistics['totals']['total_court_cases'],
                        'totalInvestigationCases' => $statistics['totals']['total_investigation_cases'],
                        'period' => $periodLabel,
                        'generatedDate' => Carbon::now()->format('d M Y H:i'),
                    ]);

                    return response()->streamDownload(function () use ($pdf) {
                        echo $pdf->output();
                    }, 'case-statistics-report-' . Carbon::now()->format('Y-m-d') . '.pdf');
                })
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
        $queryBuilder = InquiryFile::query();

        // Apply date filters based on form
        $period = $this->data['period'] ?? 'this_month';
        $fromDate = $this->data['from_date'] ?? null;
        $toDate = $this->data['to_date'] ?? null;

        if ($period === 'custom' && $fromDate && $toDate) {
            $queryBuilder->whereBetween('date', [$fromDate, $toDate]);
        } else {
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

        // Get distinct offences
        $offences = $queryBuilder->distinct()->pluck('offence')->filter()->toArray();

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
                'offence' => $offence,
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

            $filteredQuery = clone $queryBuilder;
            // We override the date range only for this monthly trend calculation
            $filteredQuery->whereBetween('date', [$startDate, $endDate]);

            $casesCount = $filteredQuery->count();
            $valueStolen = $filteredQuery->sum('value_of_property_stolen');
            $valueRecovered = $filteredQuery->sum('value_of_property_recovered');

            $monthlyTrend[] = [
                'month' => $monthName,
                'count' => $casesCount,
                'value_stolen' => $valueStolen,
                'value_recovered' => $valueRecovered,
            ];
        }

        // Top officers by case count
        $topOfficers = InquiryFile::select('dealing_officer', DB::raw('count(*) as total'))
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
        $period = $this->data['period'] ?? 'this_month';

        if ($period === 'custom') {
            $fromDate = $this->data['from_date'] ? Carbon::parse($this->data['from_date'])->format('d M Y') : '';
            $toDate = $this->data['to_date'] ? Carbon::parse($this->data['to_date'])->format('d M Y') : '';

            if ($fromDate && $toDate) {
                return "From $fromDate to $toDate";
            }

            return 'Custom Period';
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
