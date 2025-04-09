<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReportResource\Pages;
use App\Models\InquiryFile;
use App\Models\IfStatus;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Columns\Summarizers\Sum;
use Illuminate\Support\Facades\Log;

class ReportResource extends Resource
{
    protected static ?string $model = InquiryFile::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Reports & Statistics';

    protected static ?int $navigationSort = 5;

    public static function canViewAny(): bool
    {
        // Only OIC (role_id 1) and Admin (role_id 3) can view reports
        return in_array(Auth::user()->role_id, [1, 3]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // This resource is just for reporting, so we don't need form fields
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('offence')
                    ->label('Offence')
                    ->searchable(),

                Tables\Columns\TextColumn::make('cases_count')
                    ->label('Number of Cases')
                    ->getStateUsing(function ($record) {
                        // This will be overridden in the filtered query
                        return 0;
                    })
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('value_stolen')
                    ->label('Value of Property Stolen')
                    ->money('ZMW')
                    ->getStateUsing(function ($record) {
                        // This will be overridden in the filtered query
                        return 0;
                    })
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('value_recovered')
                    ->label('Value of Property Recovered')
                    ->money('ZMW')
                    ->getStateUsing(function ($record) {
                        // This will be overridden in the filtered query
                        return 0;
                    })
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('taken_to_court')
                    ->label('Taken to Court')
                    ->getStateUsing(function ($record) {
                        // This will be overridden in the filtered query
                        return 0;
                    })
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('under_investigation')
                    ->label('Under Investigation')
                    ->getStateUsing(function ($record) {
                        // This will be overridden in the filtered query
                        return 0;
                    })
                    ->alignCenter(),
            ])
            ->filters([
                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('from_date')
                            ->label('From Date')
                            ->default(function () {
                                return Carbon::now()->startOfMonth();
                            }),

                        Forms\Components\DatePicker::make('to_date')
                            ->label('To Date')
                            ->default(function () {
                                return Carbon::now();
                            }),
                    ])
                    ->query(function ($query, array $data) {
                        return $query->when(
                            $data['from_date'] || $data['to_date'],
                            function ($query) use ($data) {
                                return $query
                                    ->when(
                                        $data['from_date'],
                                        fn ($query) => $query->where('date', '>=', $data['from_date'])
                                    )
                                    ->when(
                                        $data['to_date'],
                                        fn ($query) => $query->where('date', '<=', $data['to_date'])
                                    );
                            }
                        );
                    }),

                Tables\Filters\SelectFilter::make('period')
                    ->label('Predefined Period')
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
                    ])
                    ->default('all_time')
                    ->query(function ($query, $state) {
                        if (!$state || $state === 'all_time') {
                            return $query;
                        }

                        $now = Carbon::now();
                        $year = $now->year;

                        return match ($state) {
                            'this_week' => $query->whereBetween('date', [$now->startOfWeek(), $now->endOfWeek()]),
                            'last_week' => $query->whereBetween('date', [$now->subWeek()->startOfWeek(), $now->endOfWeek()]),
                            'this_month' => $query->whereBetween('date', [$now->startOfMonth(), $now->endOfMonth()]),
                            'last_month' => $query->whereBetween('date', [$now->subMonth()->startOfMonth(), $now->endOfMonth()]),
                            'this_quarter' => $query->whereBetween('date', [$now->startOfQuarter(), $now->endOfQuarter()]),
                            'last_quarter' => $query->whereBetween('date', [$now->subQuarter()->startOfQuarter(), $now->endOfQuarter()]),
                            'q1' => $query->whereBetween('date', [Carbon::createFromDate($year, 1, 1), Carbon::createFromDate($year, 3, 31)]),
                            'q2' => $query->whereBetween('date', [Carbon::createFromDate($year, 4, 1), Carbon::createFromDate($year, 6, 30)]),
                            'q3' => $query->whereBetween('date', [Carbon::createFromDate($year, 7, 1), Carbon::createFromDate($year, 9, 30)]),
                            'q4' => $query->whereBetween('date', [Carbon::createFromDate($year, 10, 1), Carbon::createFromDate($year, 12, 31)]),
                            'this_year' => $query->whereBetween('date', [$now->startOfYear(), $now->endOfYear()]),
                            'last_year' => $query->whereBetween('date', [$now->subYear()->startOfYear(), $now->endOfYear()]),
                            default => $query,
                        };
                    }),
            ])
            ->actions([
                // No actions needed
            ])
            ->bulkActions([
                // No bulk actions needed
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReports::route('/'),
            'statistics' => Pages\ViewStatistics::route('/statistics'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        // Get all inquiry files without any filtering
        $query = parent::getEloquentQuery();

        // Add logging to debug
        Log::info('Report Resource query count: ' . $query->count());

        // Return only distinct offences
        return $query->select('offence')
            ->whereNotNull('offence')
            ->where('offence', '!=', '')
            ->groupBy('offence')
            ->orderBy('offence');
    }
}
