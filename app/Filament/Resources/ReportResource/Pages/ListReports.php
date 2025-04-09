<?php

namespace App\Filament\Resources\ReportResource\Pages;

use App\Filament\Resources\ReportResource;
use App\Models\InquiryFile;
use App\Models\IfStatus;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Pagination\Paginator;

class ListReports extends ListRecords
{
    protected static string $resource = ReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('download_report')
                ->label('Download PDF Report')
                ->icon('heroicon-o-document-arrow-down')
                ->url(function () {
                    // Get filter parameters
                    $tableFilters = $this->getTableFiltersForm()->getRawState();

                    // Build query parameters for the controller
                    $params = [];
                    if (!empty($tableFilters['period'])) {
                        $params['period'] = $tableFilters['period'];
                    }

                    if (!empty($tableFilters['date_range'])) {
                        if (!empty($tableFilters['date_range']['from_date'])) {
                            $params['from_date'] = $tableFilters['date_range']['from_date'];
                        }

                        if (!empty($tableFilters['date_range']['to_date'])) {
                            $params['to_date'] = $tableFilters['date_range']['to_date'];
                        }
                    }

                    return route('reports.generate-pdf', $params);
                })
                ->openUrlInNewTab()
                ->color('success'),

            Actions\Action::make('view_statistics')
                ->label('View Dashboard')
                ->icon('heroicon-o-presentation-chart-line')
                ->url(fn () => ReportResource::getUrl('statistics'))
                ->color('primary'),
        ];
    }

    protected function getTableQuery(): Builder
    {
        $query = static::getResource()::getEloquentQuery();

        $query->getQuery()->orders = [];

        return $query->orderBy('offence');
    }

    protected function paginateTableQuery(Builder $query): Paginator
    {
        // Use simple pagination for this report table
        return $query->simplePaginate($this->getTableRecordsPerPage());
    }

    protected function getTableRecordsPerPageSelectOptions(): array
    {
        return [10, 25, 50, 100];
    }

    protected function mutateTableDataBeforeMount(array $data): array
    {
        // Apply filters
        $queryBuilder = InquiryFile::query();

        // Apply date filters if present in the table
        $tableFilters = $this->getTableFiltersForm()->getRawState();

        // Apply date range filter if set
        if (!empty($tableFilters['date_range'])) {
            $fromDate = $tableFilters['date_range']['from_date'] ?? null;
            $toDate = $tableFilters['date_range']['to_date'] ?? null;

            if ($fromDate) {
                $queryBuilder->where('date', '>=', $fromDate);
            }

            if ($toDate) {
                $queryBuilder->where('date', '<=', $toDate);
            }
        }

        // Apply period filter if set
        if (!empty($tableFilters['period'])) {
            $period = $tableFilters['period'];
            $now = Carbon::now();
            $year = $now->year;

            switch ($period) {
                case 'this_week':
                    $queryBuilder->whereBetween('date', [$now->startOfWeek(), $now->endOfWeek()]);
                    break;
                case 'last_week':
                    $queryBuilder->whereBetween('date', [$now->subWeek()->startOfWeek(), $now->endOfWeek()]);
                    break;
                case 'this_month':
                    $queryBuilder->whereBetween('date', [$now->startOfMonth(), $now->endOfMonth()]);
                    break;
                case 'last_month':
                    $queryBuilder->whereBetween('date', [$now->subMonth()->startOfMonth(), $now->endOfMonth()]);
                    break;
                case 'this_quarter':
                    $queryBuilder->whereBetween('date', [$now->startOfQuarter(), $now->endOfQuarter()]);
                    break;
                case 'last_quarter':
                    $queryBuilder->whereBetween('date', [$now->subQuarter()->startOfQuarter(), $now->endOfQuarter()]);
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
                    $queryBuilder->whereBetween('date', [$now->subYear()->startOfYear(), $now->endOfYear()]);
                    break;
            }
        }

        // Calculate statistics for each offence
        foreach ($data as $key => $record) {
            $offence = $record['offence'];

            $filteredQuery = clone $queryBuilder;
            $filteredQuery->where('offence', $offence);

            $data[$key]['cases_count'] = $filteredQuery->count();
            $data[$key]['value_stolen'] = $filteredQuery->sum('value_of_property_stolen');
            $data[$key]['value_recovered'] = $filteredQuery->sum('value_of_property_recovered');
            $data[$key]['taken_to_court'] = $filteredQuery->where('if_status_id', 4)->count(); // Taken to Court status
            $data[$key]['under_investigation'] = $filteredQuery->where('if_status_id', 2)->count(); // Under Investigation status
        }

        return $data;
    }
}
