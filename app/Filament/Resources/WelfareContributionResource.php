<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WelfareContributionResource\Pages;
use App\Models\User;
use App\Models\WelfareContribution;
use App\Models\WelfareMonth;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;
use Carbon\Carbon;

class WelfareContributionResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Welfare Contributions';

    protected static ?string $navigationGroup = 'Offence Management';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // No need for form fields as we'll use table editing
            ]);
    }

    public static function table(Table $table): Table
    {
        // Get all months
        $months = WelfareMonth::orderBy('id')->get();
        $year = request()->query('year', Carbon::now()->year);

        // Create columns dynamically
        $columns = [
            Tables\Columns\TextColumn::make('name')
                ->label('Officer')
                ->searchable()
                ->sortable(),
        ];

        // Add a column for each month
        foreach ($months as $month) {
            $columns[] = Tables\Columns\TextColumn::make('month_' . $month->id)
                ->label($month->name)
                ->getStateUsing(function ($record) use ($month, $year) {
                    $contribution = WelfareContribution::where('user_id', $record->id)
                        ->where('month_id', $month->id)
                        ->where('year', $year)
                        ->first();

                    return $contribution ? ucfirst($contribution->status) : 'Unpaid';
                })
                ->alignCenter()
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'Paid' => 'success',
                    'Excused' => 'info',
                    default => 'danger', // Unpaid
                });
        }

        // Add total contributions column
        $columns[] = Tables\Columns\TextColumn::make('total_contributions')
            ->label('Total Paid')
            ->getStateUsing(function ($record) use ($year) {
                return WelfareContribution::where('user_id', $record->id)
                    ->where('year', $year)
                    ->where('status', 'paid')
                    ->count();
            })
            ->alignCenter()
            ->sortable(query: function (Builder $query, string $direction): Builder {
                $year = request()->query('year', Carbon::now()->year);

                return $query->withCount(['welfareContributions as paid_count' => function ($query) use ($year) {
                    $query->where('year', $year)->where('status', 'paid');
                }])
                ->orderBy('paid_count', $direction);
            })
            ->weight('bold');

        // Add amount paid column
        $columns[] = Tables\Columns\TextColumn::make('amount_paid')
            ->label('Amount Paid')
            ->getStateUsing(function ($record) use ($year) {
                $count = WelfareContribution::where('user_id', $record->id)
                    ->where('year', $year)
                    ->where('status', 'paid')
                    ->count();

                return 'K' . number_format($count * 100, 2);
            })
            ->alignEnd()
            ->sortable()
            ->weight('bold');

        return $table
            ->columns($columns)
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->relationship('role', 'name')
                    ->label('Role'),

                Tables\Filters\Filter::make('year')
                    ->form([
                        Forms\Components\Select::make('year')
                            ->label('Year')
                            ->options(function () {
                                $years = [];
                                $currentYear = Carbon::now()->year;

                                // Provide options for 2 years back and 2 years forward
                                for ($i = $currentYear - 2; $i <= $currentYear + 2; $i++) {
                                    $years[$i] = $i;
                                }

                                return $years;
                            })
                            ->default(Carbon::now()->year),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        // Store the year in the session for the column states
                        if (isset($data['year'])) {
                            session(['welfare_year' => $data['year']]);
                        }

                        return $query;
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if ($data['year'] ?? null) {
                            return 'Year: ' . $data['year'];
                        }

                        return null;
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('updateMonthStatus')
                    ->label('Update Payment')
                    ->icon('heroicon-o-currency-dollar')
                    ->form([
                        Forms\Components\Select::make('month_id')
                            ->label('Month')
                            ->options(WelfareMonth::pluck('name', 'id'))
                            ->required(),

                        Forms\Components\Select::make('status')
                            ->label('Payment Status')
                            ->options([
                                'paid' => 'Paid',
                                'unpaid' => 'Unpaid',
                                'excused' => 'Excused',
                            ])
                            ->required(),

                        Forms\Components\Select::make('year')
                            ->label('Year')
                            ->options(function () {
                                $years = [];
                                $currentYear = Carbon::now()->year;

                                // Provide options for 2 years back and 2 years forward
                                for ($i = $currentYear - 2; $i <= $currentYear + 2; $i++) {
                                    $years[$i] = $i;
                                }

                                return $years;
                            })
                            ->default(Carbon::now()->year)
                            ->required(),
                    ])
                    ->action(function (User $record, array $data) {
                        $month = WelfareMonth::find($data['month_id']);
                        $monthName = $month ? $month->name : 'Selected month';
                        $year = $data['year'];
                        $status = $data['status'];

                        // Get or create contribution record
                        $contribution = WelfareContribution::firstOrNew([
                            'user_id' => $record->id,
                            'month_id' => $data['month_id'],
                            'year' => $year,
                        ]);

                        // Set payment date if status is changed to paid
                        if ($status === 'paid' && $contribution->status !== 'paid') {
                            $contribution->payment_date = now();
                            $contribution->recorded_by = Auth::id();
                        }

                        // Update status
                        $contribution->status = $status;
                        $contribution->save();

                        // Send notification if paid
                        if ($status === 'paid') {
                            Notification::make()
                                ->title('Welfare Contribution Recorded')
                                ->body("Your welfare contribution for {$monthName} {$year} has been recorded.")
                                ->success()
                                ->sendToDatabase($record);

                            // If the user has a phone number, send an SMS notification
                            if ($record->phone) {
                                try {
                                    $message = "Your welfare contribution of K100 for {$monthName} {$year} has been recorded. Thank you for your contribution.";
                                    \App\Services\SmsService::sendMessage($message, $record->phone);
                                } catch (\Exception $e) {
                                    // Log error but continue execution
                                    \Illuminate\Support\Facades\Log::error('SMS sending failed: ' . $e->getMessage());
                                }
                            }
                        }

                        Notification::make()
                            ->title('Payment Status Updated')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (): bool => auth()->user()->role_id === 1 || auth()->user()->role_id === 3) // Only visible to OIC and Admin
                    ->modalWidth('md'),

                Tables\Actions\Action::make('viewPaymentHistory')
                    ->label('Payment History')
                    ->icon('heroicon-o-clock')
                    ->url(fn (User $record): string => route('welfare.history', ['user' => $record->id]))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('markPaid')
                    ->label('Mark Paid for Month')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Select::make('month_id')
                            ->label('Month')
                            ->options(WelfareMonth::pluck('name', 'id'))
                            ->required(),

                        Forms\Components\Select::make('year')
                            ->label('Year')
                            ->options(function () {
                                $years = [];
                                $currentYear = Carbon::now()->year;

                                // Provide options for 2 years back and 2 years forward
                                for ($i = $currentYear - 2; $i <= $currentYear + 2; $i++) {
                                    $years[$i] = $i;
                                }

                                return $years;
                            })
                            ->default(Carbon::now()->year)
                            ->required(),
                    ])
                    ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data) {
                        $month = WelfareMonth::find($data['month_id']);
                        $monthName = $month ? $month->name : 'Selected month';
                        $year = $data['year'];

                        foreach ($records as $record) {
                            // Create or update contribution record
                            $contribution = WelfareContribution::firstOrNew([
                                'user_id' => $record->id,
                                'month_id' => $data['month_id'],
                                'year' => $year,
                            ]);

                            // Set payment date if status is changing to paid
                            if ($contribution->status !== 'paid') {
                                $contribution->payment_date = now();
                                $contribution->recorded_by = Auth::id();
                            }

                            $contribution->status = 'paid';
                            $contribution->save();

                            // Send notification
                            Notification::make()
                                ->title('Welfare Contribution Recorded')
                                ->body("Your welfare contribution for {$monthName} {$year} has been recorded.")
                                ->success()
                                ->sendToDatabase($record);

                            // Send SMS if user has phone number
                            if ($record->phone) {
                                try {
                                    $message = "Your welfare contribution of K100 for {$monthName} {$year} has been recorded. Thank you for your contribution.";
                                    \App\Services\SmsService::sendMessage($message, $record->phone);
                                } catch (\Exception $e) {
                                    \Illuminate\Support\Facades\Log::error('SMS sending failed: ' . $e->getMessage());
                                }
                            }
                        }

                        Notification::make()
                            ->title('Contributions Recorded')
                            ->body("Successfully recorded payments for {$records->count()} officers.")
                            ->success()
                            ->send();
                    })
                    ->visible(fn (): bool => auth()->user()->role_id === 1 || auth()->user()->role_id === 3), // Only visible to OIC and Admin

                Tables\Actions\BulkAction::make('markUnpaid')
                    ->label('Mark Unpaid for Month')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Select::make('month_id')
                            ->label('Month')
                            ->options(WelfareMonth::pluck('name', 'id'))
                            ->required(),

                        Forms\Components\Select::make('year')
                            ->label('Year')
                            ->options(function () {
                                $years = [];
                                $currentYear = Carbon::now()->year;

                                // Provide options for 2 years back and 2 years forward
                                for ($i = $currentYear - 2; $i <= $currentYear + 2; $i++) {
                                    $years[$i] = $i;
                                }

                                return $years;
                            })
                            ->default(Carbon::now()->year)
                            ->required(),
                    ])
                    ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data) {
                        foreach ($records as $record) {
                            // Find and update contribution record if it exists
                            $contribution = WelfareContribution::where([
                                'user_id' => $record->id,
                                'month_id' => $data['month_id'],
                                'year' => $data['year'],
                            ])->first();

                            if ($contribution) {
                                $contribution->status = 'unpaid';
                                $contribution->save();
                            }
                        }

                        Notification::make()
                            ->title('Contributions Updated')
                            ->body("Successfully marked {$records->count()} officers as unpaid.")
                            ->success()
                            ->send();
                    })
                    ->visible(fn (): bool => auth()->user()->role_id === 1 || auth()->user()->role_id === 3), // Only visible to OIC and Admin
            ])
            ->defaultSort('name');
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
            'index' => Pages\ListWelfareContributions::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        // Only show active users
        return parent::getEloquentQuery()->where('is_active', true);
    }

    public static function shouldRegisterNavigation(): bool
    {
        // Show for all users, not just OIC and Admin
        return true;
    }
}
