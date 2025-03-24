<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AssignmentResource\Pages;
use App\Models\Assignment;
use App\Models\CaseStatus;
use App\Models\User;
use App\Services\SmsService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;

class AssignmentResource extends Resource
{
    protected static ?string $model = Assignment::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'Case Assignments';

    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        $user = Auth::user();

        if ($user->role_id == 2) { // Investigator
            // Count unacknowledged assignments
            return self::getEloquentQuery()
                ->whereHas('inquiryFile', fn($q) => $q->whereNull('acknowledged_at'))
                ->count();
        }

        // For OIC and Admin, show all assignments
        return self::getEloquentQuery()->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('pink_file_id')
                    ->relationship('pinkFile', 'complainant_name')
                    ->label('Case')
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set) {
                        if ($state) {
                            // Get the Pink File's assigned officer
                            $pinkFile = \App\Models\PinkFile::find($state);
                            if ($pinkFile) {
                                $set('assigned_to', $pinkFile->assigned_to);
                            }
                        }
                    }),

                Forms\Components\Select::make('assigned_by')
                    ->label('Assigned By')
                    ->options(function () {
                        return User::where('role_id', 1) // OIC role
                            ->where('is_active', true)
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->default(Auth::id())
                    ->required(),

                Forms\Components\Select::make('assigned_to')
                    ->label('Assigned To')
                    ->options(function () {
                        return User::where('role_id', 2) // Investigator role
                            ->where('is_active', true)
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->required()
                    ->searchable(),

                Forms\Components\DateTimePicker::make('assigned_at')
                    ->label('Assigned Date & Time')
                    ->default(now())
                    ->required(),

                Forms\Components\Textarea::make('assignment_notes')
                    ->label('Assignment Notes/Initial Instructions')
                    ->maxLength(1000)
                    ->columnSpanFull(),

                Forms\Components\Toggle::make('is_priority')
                    ->label('Mark as Priority')
                    ->default(false),

                Forms\Components\Checkbox::make('send_sms')
                    ->label('Send SMS notification to officer')
                    ->default(true)
                    ->visible(fn () => Auth::user()->role_id == 1) // Only visible to OIC
                    ->dehydrated(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('assigned_at')
                    ->label('Date Assigned')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('case_name')
                    ->label('Case')
                    ->getStateUsing(fn (Assignment $record) => $record->getCaseName())
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('pinkFile', fn($q) => $q->where('complainant_name', 'like', "%{$search}%"))
                                    ->orWhereHas('inquiryFile', fn($q) => $q->where('complainant', 'like', "%{$search}%")
                                                                              ->orWhere('if_number', 'like', "%{$search}%"));
                    }),

                Tables\Columns\TextColumn::make('assignedBy.name')
                    ->label('Assigned By')
                    ->sortable(),

                Tables\Columns\TextColumn::make('assignedTo.name')
                    ->label('Assigned To')
                    ->sortable(),

                Tables\Columns\TextColumn::make('current_status')
                    ->label('Current Status')
                    ->getStateUsing(fn (Assignment $record) => $record->getCurrentStatus())
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Inquiry File Opened' => 'gray',
                        'Under Investigation' => 'warning',
                        'Taken to NPA' => 'info',
                        'Taken to Court' => 'primary',
                        'Case Closed' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\IconColumn::make('is_priority')
                    ->label('Priority')
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('danger')
                    ->falseColor('gray'),

                Tables\Columns\TextColumn::make('acknowledgment')
                    ->label('Acknowledged')
                    ->getStateUsing(function (Assignment $record) {
                        if ($record->inquiryFile) {
                            return $record->inquiryFile->acknowledged_at ? 'Yes' : 'No';
                        }
                        return '-';
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Yes' => 'success',
                        'No' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('assigned_by')
                    ->relationship('assignedBy', 'name')
                    ->label('Assigned By'),

                Tables\Filters\SelectFilter::make('assigned_to')
                    ->relationship('assignedTo', 'name')
                    ->label('Assigned To'),

                Tables\Filters\TernaryFilter::make('is_priority')
                    ->label('Priority'),

                Tables\Filters\Filter::make('unacknowledged')
                    ->label('Unacknowledged Cases')
                    ->query(fn (Builder $query): Builder => $query
                        ->whereHas('inquiryFile', fn($q) => $q->whereNull('acknowledged_at')))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('addComment')
                    ->label('Add Comment')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->form([
                        Forms\Components\Textarea::make('oic_comment')
                            ->label('OIC Comment')
                            ->required()
                            ->maxLength(1000),

                        Forms\Components\Checkbox::make('send_sms')
                            ->label('Send SMS notification to officer')
                            ->default(true),
                    ])
                    ->action(function (Assignment $record, array $data) {
                        if (!$record->inquiryFile) {
                            Notification::make()
                                ->title('Error')
                                ->body('No inquiry file exists for this assignment yet')
                                ->danger()
                                ->send();
                            return;
                        }

                        // Create a new case status entry with the OIC comment
                        $statusEntry = CaseStatus::create([
                            'case_id' => $record->inquiryFile->id,
                            'user_id' => Auth::id(),
                            'old_status' => $record->inquiryFile->if_status_id,
                            'new_status' => $record->inquiryFile->if_status_id, // Same status, just adding comment
                            'oic_comment' => $data['oic_comment'],
                            'reason' => 'OIC comment added',
                        ]);

                        // Send SMS notification to the officer if requested
                        if ($data['send_sms'] && $record->assigned_to) {
                            $officer = User::find($record->assigned_to);

                            if ($officer && $officer->phone) {
                                $message = "New comment from OIC for case {$record->getCaseName()}: " .
                                           substr($data['oic_comment'], 0, 100) .
                                           (strlen($data['oic_comment']) > 100 ? '...' : '');

                                $sent = SmsService::sendMessage($message, $officer->phone);

                                if ($sent) {
                                    Notification::make()
                                        ->title('SMS sent successfully')
                                        ->success()
                                        ->send();
                                } else {
                                    Notification::make()
                                        ->title('Failed to send SMS')
                                        ->warning()
                                        ->send();
                                }
                            } else {
                                Notification::make()
                                    ->title('Officer has no phone number')
                                    ->warning()
                                    ->send();
                            }
                        }

                        // Show notification
                        Notification::make()
                            ->title('Comment added successfully')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (): bool => auth()->user()->role_id === 1), // Only visible to OIC (role_id 1)

                Tables\Actions\Action::make('viewCase')
                    ->label('View Case Details')
                    ->icon('heroicon-o-eye')
                    ->url(function (Assignment $record) {
                        if ($record->inquiry_file_id) {
                            return InquiryFileResource::getUrl('view', ['record' => $record->inquiry_file_id]);
                        } elseif ($record->pink_file_id) {
                            return PinkFileResource::getUrl('view', ['record' => $record->pink_file_id]);
                        }
                        return '#';
                    })
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                    Tables\Actions\BulkAction::make('sendReminderSMS')
                        ->label('Send Reminder to Selected')
                        ->icon('heroicon-o-device-phone-mobile')
                        ->action(function (Collection $records): void {
                            $successCount = 0;
                            $failCount = 0;

                            foreach ($records as $record) {
                                if ($record->assigned_to) {
                                    $officer = User::find($record->assigned_to);

                                    if ($officer && $officer->phone) {
                                        $message = "REMINDER: Case {$record->getCaseName()} requires your attention.";

                                        $sent = SmsService::sendMessage($message, $officer->phone);

                                        if ($sent) {
                                            $successCount++;
                                        } else {
                                            $failCount++;
                                        }
                                    } else {
                                        $failCount++;
                                    }
                                } else {
                                    $failCount++;
                                }
                            }

                            Notification::make()
                                ->title('SMS Reminders Sent')
                                ->body("Successfully sent {$successCount} reminders. Failed to send {$failCount} reminders.")
                                ->success()
                                ->send();
                        })
                        ->visible(fn (): bool => auth()->user()->role_id === 1), // Only visible to OIC
                ]),
            ]);
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
            'index' => Pages\ListAssignments::route('/'),
            'create' => Pages\CreateAssignment::route('/create'),
            'edit' => Pages\EditAssignment::route('/{record}/edit'),
            'view' => Pages\ViewAssignment::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();

        $query = parent::getEloquentQuery();

        if ($user->role_id == 3) { // Admin
            return $query;
        } else if ($user->role_id == 1) { // OIC
            return $query; // OIC sees all assignments
        } else { // Investigators
            return $query->where('assigned_to', $user->id);
        }
    }

    public static function shouldRegisterNavigation(): bool
    {
        //return in_array(Auth::user()->role_id, [1, 2, 3]);
        return false;
    }
}
