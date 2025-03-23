<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InquiryFileResource\Pages;
use App\Filament\Resources\InquiryFileResource\RelationManagers\StatusChangesRelationManager;
use App\Models\CaseStatus;
use App\Models\IfStatus;
use App\Models\InquiryFile;
use App\Models\Role;
use App\Models\PinkFile;
use App\Models\User;
use App\Services\SmsService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Repeater;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Filament\Notifications\Notification;

class InquiryFileResource extends Resource
{
    protected static ?string $model = InquiryFile::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-magnifying-glass';

    //protected static ?string $navigationGroup = 'Case Management';

    protected static ?string $navigationLabel = 'Inquiry Files';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('File Information')
                            ->schema([
                                Forms\Components\TextInput::make('if_number')
                                    ->label('Inquiry File Number')
                                    ->default(fn() => InquiryFile::generateInquiryNumber())
                                    ->disabled()
                                    ->dehydrated()
                                    ->required(),

                                Forms\Components\TimePicker::make('time')
                                    ->seconds(false)
                                    ->required(),

                                Forms\Components\DatePicker::make('date')
                                    ->default(now())
                                    ->required(),

                                Forms\Components\TextInput::make('cr_number')
                                    ->label('CR Number')
                                    ->placeholder('Enter CR number')
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('police_station')
                                    ->label('Police Station/Post')
                                    ->placeholder('Enter police station name')
                                    ->maxLength(255),

                                Forms\Components\Hidden::make('pink_file_id')
                                    ->default(fn() => request()->get('pinkFileId')),
                            ]),

                        Forms\Components\Section::make('Case Details')
                            ->schema([
                                Forms\Components\TextInput::make('complainant')
                                    ->required()
                                    ->maxLength(255)
                                    ->default(function () {
                                        $pinkFileId = request()->get('pinkFileId');
                                        if ($pinkFileId) {
                                            $pinkFile = PinkFile::find($pinkFileId);
                                            return $pinkFile ? $pinkFile->complainant_name : '';
                                        }
                                        return '';
                                    }),

                                Forms\Components\TextInput::make('offence')
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('value_of_property_stolen')
                                    ->label('Value of Property Stolen (ZMW)')
                                    ->numeric()
                                    ->prefix('ZMW'),

                                Forms\Components\TextInput::make('value_of_property_recovered')
                                    ->label('Value of Property Recovered (ZMW)')
                                    ->numeric()
                                    ->prefix('ZMW'),

                                Repeater::make('accused_persons')
                                    ->label('Accused Persons')
                                    ->schema([
                                        Forms\Components\TextInput::make('name')
                                            ->required()
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('identification')
                                            ->label('ID/Passport')
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('contact')
                                            ->tel()
                                            ->maxLength(255),

                                        Forms\Components\Textarea::make('address')
                                            ->maxLength(500)
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2)
                                    ->collapsible()
                                    ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                                    ->defaultItems(1),
                            ]),
                    ])
                    ->columnSpan(['lg' => 2]),

                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Status Information')
                            ->schema([
                                Forms\Components\Select::make('if_status_id')
                                    ->relationship('status', 'name')
                                    ->required()
                                    ->label('Case Status')
                                    ->afterStateUpdated(function ($state, $old, callable $set) {
                                        if ($state == 5) { // Case Closed status ID
                                            $set('case_close_visible', true);
                                        } else {
                                            $set('case_close_visible', false);
                                        }

                                        if (in_array($state, [3, 4])) { // Taken to NPA or Court
                                            $set('court_type_visible', true);
                                        } else {
                                            $set('court_type_visible', false);
                                        }

                                        if ($state == 4) { // Taken to Court
                                            $set('court_stage_visible', true);
                                        } else {
                                            $set('court_stage_visible', false);
                                        }
                                    }),

                                Forms\Components\Hidden::make('case_close_visible')
                                    ->default(false),

                                Forms\Components\Hidden::make('court_type_visible')
                                    ->default(false),

                                Forms\Components\Hidden::make('court_stage_visible')
                                    ->default(false),

                                Forms\Components\Textarea::make('case_close_reason')
                                    ->label('Case Close Reason')
                                    ->placeholder('Reason for closing the case')
                                    ->maxLength(500)
                                    ->visible(fn (callable $get) => $get('case_close_visible') || $get('if_status_id') == 5),

                                Forms\Components\Select::make('court_type_id')
                                    ->relationship('courtType', 'name')
                                    ->label('Court Type')
                                    ->visible(fn (callable $get) => $get('court_type_visible') || in_array($get('if_status_id'), [3, 4])),

                                Forms\Components\Select::make('court_stage_id')
                                    ->relationship('courtStage', 'name')
                                    ->label('Court Stage')
                                    ->visible(fn (callable $get) => $get('court_stage_visible') || $get('if_status_id') == 4),

                                // For OIC: Add reason for status change
                                Forms\Components\Textarea::make('status_change_reason')
                                    ->label('Reason for Status Change')
                                    ->placeholder('Please provide a reason for changing the status')
                                    ->maxLength(500)
                                    ->hidden(fn (string $operation): bool => $operation === 'create')
                                    ->dehydrated(false),
                            ]),

                        Forms\Components\Section::make('Officer Information')
                            ->schema([
                                Forms\Components\Select::make('dealing_officer')
                                    ->label('Dealing Officer')
                                    ->options(function () {
                                        // Get the pink file ID from the request
                                        $pinkFileId = request()->get('pinkFileId');

                                        // If we have a pink file ID, get the assigned officer from that
                                        if ($pinkFileId) {
                                            $pinkFile = PinkFile::find($pinkFileId);
                                            if ($pinkFile && $pinkFile->assigned_to) {
                                                $assignedOfficer = User::find($pinkFile->assigned_to);
                                                if ($assignedOfficer) {
                                                    // Return just this officer as an option
                                                    return [$assignedOfficer->id => $assignedOfficer->name];
                                                }
                                            }
                                        }

                                        // Otherwise, return all investigators
                                        return User::where('role_id', 2) // Investigator role
                                            ->where('is_active', true)
                                            ->pluck('name', 'id')
                                            ->toArray();
                                    })
                                    ->default(function () {
                                        // Get the pink file ID from the request
                                        $pinkFileId = request()->get('pinkFileId');

                                        // If we have a pink file ID, default to the assigned officer
                                        if ($pinkFileId) {
                                            $pinkFile = PinkFile::find($pinkFileId);
                                            if ($pinkFile) {
                                                return $pinkFile->assigned_to;
                                            }
                                        }

                                        return null;
                                    })
                                    ->searchable()
                                    ->required(),

                                Forms\Components\Textarea::make('remarks')
                                    ->placeholder('Enter any additional remarks or notes')
                                    ->maxLength(1000)
                                    ->columnSpanFull(),
                            ]),

                        // For OIC: Add comments section
                        Forms\Components\Section::make('OIC Comments')
                            ->schema([
                                Forms\Components\Textarea::make('oic_comment')
                                    ->label('Officer in Charge Comment')
                                    ->placeholder('Enter your comments or directions for the investigator')
                                    ->maxLength(1000),

                                Forms\Components\Checkbox::make('send_sms')
                                    ->label('Send SMS notification to officer')
                                    ->default(true)
                                    ->dehydrated(false),
                            ])
                            ->visible(fn () => Auth::user()->role_id == 1), // Only visible to OIC
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('if_number')
                    ->label('IF Number')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('date')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('complainant')
                    ->searchable(),

                Tables\Columns\TextColumn::make('offence')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('value_of_property_stolen')
                    ->label('Value Stolen')
                    ->money('ZMW')
                    ->sortable(),

                Tables\Columns\TextColumn::make('value_of_property_recovered')
                    ->label('Value Recovered')
                    ->money('ZMW')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status.name')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Inquiry File Opened' => 'gray',
                        'Under Investigation' => 'warning',
                        'Taken to NPA' => 'info',
                        'Taken to Court' => 'primary',
                        'Case Closed' => 'success',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('officer.name')
                    ->label('Dealing Officer')
                    ->searchable(),

                // Show the latest OIC comment
                Tables\Columns\TextColumn::make('latest_comment')
                    ->label('OIC Comment')
                    ->getStateUsing(function ($record) {
                        $latestStatus = CaseStatus::where('case_id', $record->id)
                            ->whereNotNull('oic_comment')
                            ->latest()
                            ->first();

                        return $latestStatus ? $latestStatus->oic_comment : null;
                    })
                    ->limit(30)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('acknowledged_at')
                    ->label('Acknowledged')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('if_status')
                    ->relationship('status', 'name')
                    ->label('Case Status'),

                Tables\Filters\SelectFilter::make('court_type')
                    ->relationship('courtType', 'name')
                    ->label('Court Type'),

                Tables\Filters\SelectFilter::make('court_stage')
                    ->relationship('courtStage', 'name')
                    ->label('Court Stage'),

                Tables\Filters\SelectFilter::make('dealing_officer')
                    ->relationship('officer', 'name')
                    ->label('Dealing Officer'),

                Tables\Filters\TernaryFilter::make('acknowledged')
                    ->label('Acknowledged')
                    ->attribute('acknowledged_at')
                    ->nullable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),

                Tables\Actions\Action::make('acknowledge')
                    ->label('Acknowledge')
                    ->icon('heroicon-o-check-badge')
                    ->action(function (InquiryFile $record) {
                        // Add logic to acknowledge the case
                        $record->acknowledged_at = now();
                        $record->save();

                        Log::info('Inquiry file acknowledged: ' . $record->if_number . ' by user: ' . Auth::id());

                        // Also notify the OIC that the case has been acknowledged
                        $oicUsers = User::where('role_id', 1)->get();
                        foreach ($oicUsers as $oicUser) {
                            Notification::make()
                                ->title('Case Acknowledged')
                                ->body('Inquiry file ' . $record->if_number . ' has been acknowledged by ' . Auth::user()->name)
                                ->sendToDatabase($oicUser);

                            // Send SMS to OIC if they have a phone number
                            if ($oicUser->phone) {
                                $message = "Case acknowledgement: {$record->if_number} has been acknowledged by " . Auth::user()->name;
                                SmsService::sendMessage($message, $oicUser->phone);
                            }
                        }
                    })
                    ->requiresConfirmation()
                    ->visible(function (InquiryFile $record) {
                        $user = Auth::user();
                        return $user->id === $record->dealing_officer &&
                               $record->acknowledged_at === null;
                    })
                    ->color('success'),

                Tables\Actions\Action::make('changeStatus')
                    ->label('Update Status')
                    ->icon('heroicon-o-arrow-path')
                    ->form([
                        Forms\Components\Select::make('if_status_id')
                            ->label('New Status')
                            ->options(function () {
                                return IfStatus::pluck('name', 'id')->toArray();
                            })
                            ->required(),

                        Forms\Components\Textarea::make('reason')
                            ->label('Reason for Change')
                            ->required(),
                    ])
                    ->action(function (InquiryFile $record, array $data) {
                        // Record the old status
                        $oldStatus = $record->if_status_id;

                        // Update the record with the new status
                        $record->update(['if_status_id' => $data['if_status_id']]);

                        // Create a status change record
                        $statusChange = CaseStatus::create([
                            'case_id' => $record->id,
                            'user_id' => Auth::id(),
                            'old_status' => $oldStatus,
                            'new_status' => $data['if_status_id'],
                            'reason' => $data['reason'],
                        ]);

                        // Get the old and new status names
                        $oldStatusName = IfStatus::find($oldStatus)->name ?? 'Unknown';
                        $newStatusName = IfStatus::find($data['if_status_id'])->name ?? 'Unknown';

                        // Notify OIC users about the status change
                        $oicUsers = User::where('role_id', 1)->get();
                        foreach ($oicUsers as $oicUser) {
                            Notification::make()
                                ->title('Case Status Updated')
                                ->body("Inquiry file {$record->if_number} status changed from {$oldStatusName} to {$newStatusName}.")
                                ->sendToDatabase($oicUser);

                            // Send SMS to OIC if they have a phone number
                            if ($oicUser->phone) {
                                $message = "Case status update: {$record->if_number} changed from {$oldStatusName} to {$newStatusName}. Reason: " .
                                           substr($data['reason'], 0, 100) .
                                           (strlen($data['reason']) > 100 ? '...' : '');

                                SmsService::sendMessage($message, $oicUser->phone);
                            }
                        }

                        Log::info('Inquiry file status changed: ' . $record->if_number .
                                  ' from status: ' . $oldStatus .
                                  ' to status: ' . $data['if_status_id'] .
                                  ' by user: ' . Auth::id());

                        Notification::make()
                            ->title('Status Updated')
                            ->success()
                            ->send();
                    })
                    ->visible(function (InquiryFile $record) {
                        $user = Auth::user();
                        return $user->id === $record->dealing_officer &&
                               $record->acknowledged_at !== null;
                    }),

                // Add an action for OIC to add comments
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
                    ->action(function (InquiryFile $record, array $data) {
                        // Create a new case status entry with the OIC comment
                        $statusEntry = CaseStatus::create([
                            'case_id' => $record->id,
                            'user_id' => Auth::id(),
                            'old_status' => $record->if_status_id,
                            'new_status' => $record->if_status_id, // Same status, just adding comment
                            'oic_comment' => $data['oic_comment'],
                            'reason' => 'OIC comment added',
                        ]);

                        // Send SMS notification to the officer if requested
                        if ($data['send_sms'] && $record->dealing_officer) {
                            $officer = User::find($record->dealing_officer);

                            if ($officer && $officer->phone) {
                                $message = "New comment from OIC for inquiry file {$record->if_number}: " .
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

                Tables\Actions\Action::make('exportPDF')
                    ->label('Export PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->url(fn (InquiryFile $record): string => route('inquiry-file.export-pdf', ['id' => $record->id]))
                    ->openUrlInNewTab()
                    ->color('success'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            StatusChangesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInquiryFiles::route('/'),
            'create' => Pages\CreateInquiryFile::route('/create'),
            'edit' => Pages\EditInquiryFile::route('/{record}/edit'),
            'view' => Pages\ViewInquiryFile::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        Log::info('User accessing InquiryFileResource: ID=' . $user->id . ', Role=' . $user->role_id);

        $query = parent::getEloquentQuery();

        // Based on your RoleSeeder:
        // 1 = "oic"
        // 2 = "investigator"
        // 3 = "admin"

        if ($user->role_id == 3) { // Admin
            Log::info('User is ADMIN - showing all inquiry files');
            return $query;
        } else if ($user->role_id == 1) { // OIC
            Log::info('User is OIC - showing all inquiry files');
            return $query; // OIC sees all files
        } else { // Investigators (role_id == 2)
            Log::info('User is INVESTIGATOR - filtering for dealing_officer=' . $user->id);
            return $query->where('dealing_officer', $user->id);
        }
    }

    public static function shouldRegisterNavigation(): bool
    {
        // Based on your RoleSeeder:
        // 1 = "oic"
        // 2 = "investigator"
        // 3 = "admin"

        $user = Auth::user();
        Log::info('Checking navigation visibility for InquiryFileResource - User ID=' . $user->id . ', Role=' . $user->role_id);

        // Show this resource for OIC, investigators, and admin
        return in_array($user->role_id, [1, 2, 3]);
    }
}
