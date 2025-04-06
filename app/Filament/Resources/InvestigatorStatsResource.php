<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvestigatorStatsResource\Pages;
use App\Models\InquiryFile;
use App\Models\User;
use App\Models\Role;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class InvestigatorStatsResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Investigator Stats';

    protected static ?int $navigationSort = 4; // After main resources

    public static function getEloquentQuery(): Builder
    {
        // Only show investigator users
        return static::getModel()::query()
            ->where('role_id', Role::INVESTIGATOR)
            ->where('is_active', true);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Investigator')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_assigned')
                    ->label('Total Assigned')
                    ->getStateUsing(function (User $record) {
                        $query = InquiryFile::where('dealing_officer', $record->id);

                        // Apply date filters if set
                        $fromDate = session('stats_filter_from');
                        $untilDate = session('stats_filter_until');

                        if ($fromDate) {
                            $query->whereDate('created_at', '>=', $fromDate);
                        }

                        if ($untilDate) {
                            $query->whereDate('created_at', '<=', $untilDate);
                        }

                        return $query->count();
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        $fromDate = session('stats_filter_from');
                        $untilDate = session('stats_filter_until');

                        $countQuery = $query->withCount(['inquiryFiles as assigned_count' => function ($q) use ($fromDate, $untilDate) {
                            if ($fromDate) {
                                $q->whereDate('created_at', '>=', $fromDate);
                            }

                            if ($untilDate) {
                                $q->whereDate('created_at', '<=', $untilDate);
                            }
                        }]);

                        return $countQuery->orderBy('assigned_count', $direction);
                    })
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('under_investigation')
                    ->label('Under Investigation')
                    ->getStateUsing(function (User $record) {
                        $query = InquiryFile::where('dealing_officer', $record->id)
                            ->where('if_status_id', 2); // Under Investigation status

                        // Apply date filters if set
                        $fromDate = session('stats_filter_from');
                        $untilDate = session('stats_filter_until');

                        if ($fromDate) {
                            $query->whereDate('created_at', '>=', $fromDate);
                        }

                        if ($untilDate) {
                            $query->whereDate('created_at', '<=', $untilDate);
                        }

                        return $query->count();
                    })
                    ->alignCenter()
                    ->color('warning'),

                Tables\Columns\TextColumn::make('taken_to_court')
                    ->label('Taken to Court')
                    ->getStateUsing(function (User $record) {
                        $query = InquiryFile::where('dealing_officer', $record->id)
                            ->where('if_status_id', 4); // Taken to Court status

                        // Apply date filters if set
                        $fromDate = session('stats_filter_from');
                        $untilDate = session('stats_filter_until');

                        if ($fromDate) {
                            $query->whereDate('created_at', '>=', $fromDate);
                        }

                        if ($untilDate) {
                            $query->whereDate('created_at', '<=', $untilDate);
                        }

                        return $query->count();
                    })
                    ->alignCenter()
                    ->color('info'),

                Tables\Columns\TextColumn::make('cases_closed')
                    ->label('Cases Closed')
                    ->getStateUsing(function (User $record) {
                        $query = InquiryFile::where('dealing_officer', $record->id)
                            ->where('if_status_id', 5); // Case Closed status

                        // Apply date filters if set
                        $fromDate = session('stats_filter_from');
                        $untilDate = session('stats_filter_until');

                        if ($fromDate) {
                            $query->whereDate('created_at', '>=', $fromDate);
                        }

                        if ($untilDate) {
                            $query->whereDate('created_at', '<=', $untilDate);
                        }

                        return $query->count();
                    })
                    ->alignCenter()
                    ->color('success'),

                Tables\Columns\TextColumn::make('active_cases')
                    ->label('Active Cases')
                    ->getStateUsing(function (User $record) {
                        $query = InquiryFile::where('dealing_officer', $record->id)
                            ->whereNotIn('if_status_id', [5]); // Excluding closed cases

                        // Apply date filters if set
                        $fromDate = session('stats_filter_from');
                        $untilDate = session('stats_filter_until');

                        if ($fromDate) {
                            $query->whereDate('created_at', '>=', $fromDate);
                        }

                        if ($untilDate) {
                            $query->whereDate('created_at', '<=', $untilDate);
                        }

                        return $query->count();
                    })
                    ->alignCenter()
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        $fromDate = session('stats_filter_from');
                        $untilDate = session('stats_filter_until');

                        return $query->withCount(['inquiryFiles as active_count' => function ($q) use ($fromDate, $untilDate) {
                            $q->whereNotIn('if_status_id', [5]);

                            if ($fromDate) {
                                $q->whereDate('created_at', '>=', $fromDate);
                            }

                            if ($untilDate) {
                                $q->whereDate('created_at', '<=', $untilDate);
                            }
                        }])
                        ->orderBy('active_count', $direction);
                    })
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('last_assigned')
                    ->label('Last Assigned')
                    ->getStateUsing(function (User $record) {
                        $query = InquiryFile::where('dealing_officer', $record->id);

                        // Apply date filters if set
                        $fromDate = session('stats_filter_from');
                        $untilDate = session('stats_filter_until');

                        if ($fromDate) {
                            $query->whereDate('created_at', '>=', $fromDate);
                        }

                        if ($untilDate) {
                            $query->whereDate('created_at', '<=', $untilDate);
                        }

                        $lastAssigned = $query->latest('created_at')->first();

                        if (!$lastAssigned) {
                            return 'No cases in period';
                        }

                        return $lastAssigned->created_at->format('d M Y');
                    })
                    ->alignCenter(),
            ])
            ->filters([
                // Add date range filter
                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\Select::make('preset')
                            ->label('Preset Period')
                            ->options([
                                'current_month' => 'Current Month',
                                'previous_month' => 'Previous Month',
                                'current_quarter' => 'Current Quarter',
                                'previous_quarter' => 'Previous Quarter',
                                'current_year' => 'Current Year',
                                'previous_year' => 'Previous Year',
                                'last_30_days' => 'Last 30 Days',
                                'last_60_days' => 'Last 60 Days',
                                'last_90_days' => 'Last 90 Days',
                                'custom' => 'Custom Range',
                            ])
                            ->default('current_month')
                            ->reactive()
                            ->afterStateUpdated(function (callable $set, $state) {
                                $now = Carbon::now();

                                switch ($state) {
                                    case 'current_month':
                                        $set('from', $now->copy()->startOfMonth()->format('Y-m-d'));
                                        $set('until', $now->copy()->endOfMonth()->format('Y-m-d'));
                                        break;
                                    case 'previous_month':
                                        $set('from', $now->copy()->subMonth()->startOfMonth()->format('Y-m-d'));
                                        $set('until', $now->copy()->subMonth()->endOfMonth()->format('Y-m-d'));
                                        break;
                                    case 'current_quarter':
                                        $set('from', $now->copy()->startOfQuarter()->format('Y-m-d'));
                                        $set('until', $now->copy()->endOfQuarter()->format('Y-m-d'));
                                        break;
                                    case 'previous_quarter':
                                        $set('from', $now->copy()->subQuarter()->startOfQuarter()->format('Y-m-d'));
                                        $set('until', $now->copy()->subQuarter()->endOfQuarter()->format('Y-m-d'));
                                        break;
                                    case 'current_year':
                                        $set('from', $now->copy()->startOfYear()->format('Y-m-d'));
                                        $set('until', $now->copy()->endOfYear()->format('Y-m-d'));
                                        break;
                                    case 'previous_year':
                                        $set('from', $now->copy()->subYear()->startOfYear()->format('Y-m-d'));
                                        $set('until', $now->copy()->subYear()->endOfYear()->format('Y-m-d'));
                                        break;
                                    case 'last_30_days':
                                        $set('from', $now->copy()->subDays(30)->format('Y-m-d'));
                                        $set('until', $now->format('Y-m-d'));
                                        break;
                                    case 'last_60_days':
                                        $set('from', $now->copy()->subDays(60)->format('Y-m-d'));
                                        $set('until', $now->format('Y-m-d'));
                                        break;
                                    case 'last_90_days':
                                        $set('from', $now->copy()->subDays(90)->format('Y-m-d'));
                                        $set('until', $now->format('Y-m-d'));
                                        break;
                                }
                            }),

                        Forms\Components\DatePicker::make('from')
                            ->label('From Date')
                            ->placeholder('Start date')
                            ->default(now()->startOfMonth()),

                        Forms\Components\DatePicker::make('until')
                            ->label('To Date')
                            ->placeholder('End date')
                            ->default(now()),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        // Store the date range in the session for calculated columns
                        if (isset($data['from'])) {
                            session(['stats_filter_from' => $data['from']]);
                        }

                        if (isset($data['until'])) {
                            session(['stats_filter_until' => $data['until']]);
                        }

                        // Return the query unmodified - we'll apply the filter in the column calculations
                        return $query;
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['from'] ?? null) {
                            $indicators['from'] = 'From ' . Carbon::parse($data['from'])->toFormattedDateString();
                        }

                        if ($data['until'] ?? null) {
                            $indicators['until'] = 'Until ' . Carbon::parse($data['until'])->toFormattedDateString();
                        }

                        return $indicators;
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('view_cases')
                    ->label('View Cases')
                    ->url(fn (User $record) => url("/admin/inquiry-files?tableFilters[dealing_officer][value]={$record->id}"))
                    ->icon('heroicon-o-document-magnifying-glass')
                    ->openUrlInNewTab(),

                // Add Export to PDF action for single officer
                Tables\Actions\Action::make('export_pdf')
                    ->label('Export PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->url(fn (User $record) => route('investigator.export-pdf', ['id' => $record->id]))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                ExportBulkAction::make('export Excel'),
                // Add Bulk Export to PDF action
                Tables\Actions\BulkAction::make('export_bulk_pdf')
                    ->label('Export PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                        // Convert the IDs to a comma-separated string
                        $ids = $records->pluck('id')->join(',');

                        // Redirect to a route that will generate the PDF
                        return redirect()->route('investigator.export-bulk-pdf', ['ids' => $ids]);
                    })
                    ->deselectRecordsAfterCompletion(),
            ])
            ->defaultSort('active_cases', 'desc'); // Sort by active cases by default
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvestigatorStats::route('/'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        // Only show to OIC and Admin
        $user = Auth::user();
        return in_array($user->role_id, [1, 3]); // OIC (1) or Admin (3)
    }

    /**
     * Generate a PDF report for one or more investigators
     *
     * @param array $investigatorIds
     * @return \Barryvdh\DomPDF\PDF
     */
    public static function generateInvestigatorPDF(array $investigatorIds)
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
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.investigator-stats', [
            'investigatorsData' => $investigatorsData,
            'generatedDate' => now()->format('d M Y H:i'),
        ]);

        return $pdf;
    }
}
