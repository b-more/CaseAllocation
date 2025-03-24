<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PinkFileResource\Pages;
use App\Filament\Resources\PinkFileResource\RelationManagers\InquiryFilesRelationManager;
use App\Models\InquiryFile;
use App\Models\PinkFile;
use App\Models\User;
use App\Services\SmsService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Filament\Notifications\Notification;

class PinkFileResource extends Resource
{
    protected static ?string $model = PinkFile::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    //protected static ?string $navigationGroup = 'Case Management';

    protected static ?string $navigationLabel = 'Cases';
    protected static ?string $pluralModelLabel = 'Cases';
    protected static ?string $modelLabel = 'Case';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('pink_file_type_id')
                    ->relationship('fileType', 'name')
                    ->required()
                    ->label('File Type'),

                Forms\Components\Select::make('complainant_type_id')
                    ->relationship('complainantType', 'name')
                    ->required()
                    ->label('Complainant Type'),

                Forms\Components\TextInput::make('complainant_name')
                    ->required()
                    ->label('Complainant Name')
                    ->placeholder('Enter complainant name')
                    ->maxLength(255),

                Forms\Components\DateTimePicker::make('date_time_of_occurrence')
                    ->label('Date & Time of Occurrence')
                    ->seconds(false),

                Forms\Components\Select::make('crime_type_id')
                    ->relationship('crimeType', 'name')
                    ->required()
                    ->label('Crime Type'),

                Forms\Components\Select::make('priority')
                    ->options(PinkFile::getPriorityOptions())
                    ->default('normal')
                    ->required(),

                Forms\Components\Select::make('assigned_to')
                    ->label('Assigned Officer')
                    ->options(function () {
                        return User::where('role_id', 2) // Investigator role
                            ->where('is_active', true)
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->searchable()
                    ->required(),

                Forms\Components\Textarea::make('oic_comment')
                    ->label('OIC Comment')
                    ->placeholder('Enter Officer in Charge comments')
                    ->columnSpanFull()
                    ->visible(fn () => Auth::user()->role_id == 1), // Only visible to OIC
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('fileType.name')
                    ->label('File Type')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('complainant_name')
                    ->label('Complainant')
                    ->searchable(),

                Tables\Columns\TextColumn::make('date_time_of_occurrence')
                    ->label('Date of Occurrence')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('crimeType.name')
                    ->label('Crime Type')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('priority')
                    ->colors([
                        'danger' => 'very_high',
                        'warning' => 'high',
                        'success' => 'normal',
                        'gray' => 'low',
                    ]),

                Tables\Columns\TextColumn::make('assignedOfficer.name')
                    ->label('Assigned To')
                    ->searchable(),

                // Add acknowledgment status column
                Tables\Columns\IconColumn::make('acknowledged_at')
                    ->label('Acknowledged')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                // Add the status column (from the related inquiry file)
                Tables\Columns\TextColumn::make('inquiryFile.status.name')
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
                    ->placeholder('No Inquiry File'),

                // Add a column to show if inquiry file exists
                Tables\Columns\IconColumn::make('has_inquiry_file')
                    ->label('Inquiry File')
                    ->boolean()
                    ->getStateUsing(fn (PinkFile $record): bool => $record->inquiryFile !== null)
                    ->trueIcon('heroicon-o-document-text')
                    ->falseIcon('heroicon-o-x-mark')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('file_type')
                    ->relationship('fileType', 'name')
                    ->label('File Type'),

                Tables\Filters\SelectFilter::make('crime_type')
                    ->relationship('crimeType', 'name')
                    ->label('Crime Type'),

                Tables\Filters\SelectFilter::make('priority')
                    ->options(PinkFile::getPriorityOptions())
                    ->label('Priority'),

                Tables\Filters\SelectFilter::make('assigned_to')
                    ->relationship('assignedOfficer', 'name')
                    ->label('Assigned Officer'),

                // Add filter for acknowledgment status
                Tables\Filters\TernaryFilter::make('acknowledged')
                    ->label('Acknowledged')
                    ->nullable()
                    ->attribute('acknowledged_at'),

                // Add filter for inquiry file status
                Tables\Filters\SelectFilter::make('inquiry_status')
                    ->relationship('inquiryFile.status', 'name')
                    ->label('Case Status'),

                // Add filter for cases without inquiry files
                Tables\Filters\Filter::make('no_inquiry_file')
                    ->label('Without Inquiry File')
                    ->query(fn (Builder $query): Builder => $query->whereDoesntHave('inquiryFile'))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->color('gray'),

                // Add Acknowledge action for investigators
                Tables\Actions\Action::make('acknowledge')
                    ->label('Acknowledge')
                    ->icon('heroicon-o-check-badge')
                    ->action(function (PinkFile $record) {
                        // Add logic to acknowledge the case
                        $record->acknowledge();

                        // Notify the OIC users about the acknowledgment
                        $oicUsers = User::where('role_id', 1)->get();
                        foreach ($oicUsers as $oicUser) {
                            Notification::make()
                                ->title('Case Acknowledged')
                                ->body('Case ' . $record->complainant_name . ' has been acknowledged by ' . Auth::user()->name)
                                ->sendToDatabase($oicUser);

                            // Send SMS notification if OIC has a phone number
                            if ($oicUser->phone) {
                                $message = "Case acknowledgement: {$record->complainant_name} has been acknowledged by " . Auth::user()->name;
                                SmsService::sendMessage($message, $oicUser->phone);
                            }
                        }

                        Notification::make()
                            ->title('Case Acknowledged')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->visible(function (PinkFile $record) {
                        $user = Auth::user();
                        return $user->id === $record->assigned_to &&
                            $record->acknowledged_at === null &&
                            $record->inquiryFile === null;
                    })
                    ->color('success'),

                // Move Create Inquiry File action to the first position and make it more prominent
                Tables\Actions\Action::make('createInquiryFile')
                    ->label('Create Inquiry File')
                    ->icon('heroicon-o-document-plus')
                    ->url(fn (PinkFile $record): string => route('filament.admin.resources.inquiry-files.create', ['pinkFileId' => $record->id]))
                    ->color('success')
                    ->button() // Make it a full button
                    ->disabled(fn (PinkFile $record): bool => $record->inquiryFile !== null)
                    ->visible(fn (PinkFile $record): bool =>
                        // Visible to OIC and investigators
                        in_array(auth()->user()->role_id, [1, 2]) &&
                        // And only if no inquiry file exists
                        $record->inquiryFile === null &&
                        // And only if the case has been acknowledged
                        $record->acknowledged_at !== null &&
                        // And only if the current user is the assigned officer (for investigators)
                        (auth()->user()->role_id === 1 || auth()->user()->id === $record->assigned_to)
                    ),

                Tables\Actions\EditAction::make()
                    ->color('gray'),

                Tables\Actions\DeleteAction::make()
                    ->color('gray'),

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
                    ->action(function (PinkFile $record, array $data) {
                        // Update the OIC comment
                        $record->update([
                            'oic_comment' => $data['oic_comment']
                        ]);

                        // Send SMS notification to the officer if requested
                        if ($data['send_sms'] && $record->assigned_to) {
                            $officer = User::find($record->assigned_to);

                            if ($officer && $officer->phone) {
                                $message = "New comment from OIC for case {$record->complainant_name}: " .
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
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                    // Add bulk action to send reminders
                    Tables\Actions\BulkAction::make('sendReminders')
                        ->label('Send Reminder to Create Inquiry File')
                        ->icon('heroicon-o-bell-alert')
                        ->color('warning')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $count = 0;

                            foreach ($records as $record) {
                                // Skip records that already have an inquiry file
                                if ($record->inquiryFile !== null) {
                                    continue;
                                }

                                // Skip records that haven't been acknowledged
                                if ($record->acknowledged_at === null) {
                                    continue;
                                }

                                // Send notification to the assigned officer
                                if ($record->assigned_to) {
                                    $officer = \App\Models\User::find($record->assigned_to);

                                    if ($officer) {
                                        \Filament\Notifications\Notification::make()
                                            ->title('Case Reminder')
                                            ->body("Please create an inquiry file for case: {$record->complainant_name}")
                                            ->actions([
                                                \Filament\Notifications\Actions\Action::make('createFile')
                                                    ->button()
                                                    ->url(route('filament.admin.resources.inquiry-files.create', ['pinkFileId' => $record->id]))
                                            ])
                                            ->sendToDatabase($officer);

                                        // Send SMS if officer has a phone number
                                        if ($officer->phone) {
                                            $message = "REMINDER: Please create an inquiry file for case '{$record->complainant_name}' as soon as possible.";
                                            \App\Services\SmsService::sendMessage($message, $officer->phone);
                                        }

                                        $count++;
                                    }
                                }
                            }

                            \Filament\Notifications\Notification::make()
                                ->title('Reminders Sent')
                                ->body("Successfully sent reminders for {$count} cases")
                                ->success()
                                ->send();
                        })
                        ->visible(fn (): bool => auth()->user()->role_id === 1) // Only visible to OIC
                        ->requiresConfirmation(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            InquiryFilesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPinkFiles::route('/'),
            'create' => Pages\CreatePinkFile::route('/create'),
            'edit' => Pages\EditPinkFile::route('/{record}/edit'),
            'view' => Pages\ViewPinkFile::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        // Add some debug logging to track what's happening
        $user = Auth::user();
        Log::info('User accessing PinkFileResource: ID=' . $user->id . ', Role=' . $user->role_id . ', Name=' . $user->name);

        $query = parent::getEloquentQuery();

        // Based on your RoleSeeder:
        // 1 = "oic"
        // 2 = "investigator"
        // 3 = "admin"

        if ($user->role_id == 3) { // Admin
            Log::info('User is ADMIN - showing all records');
            return $query;
        } else if ($user->role_id == 1) { // OIC
            Log::info('User is OIC - showing all records');
            return $query; // OIC sees all files
        } else { // Investigators
            Log::info('User is INVESTIGATOR - filtering for assigned_to=' . $user->id);
            return $query->where('assigned_to', $user->id);
        }
    }

    public static function shouldRegisterNavigation(): bool
    {
        // Based on your RoleSeeder:
        // 1 = "oic"
        // 2 = "investigator"
        // 3 = "admin"

        $user = Auth::user();
        Log::info('Checking navigation visibility for: ID=' . $user->id . ', Role=' . $user->role_id);

        // Show this resource for OIC, investigators, and admin
        return in_array($user->role_id, [1, 2, 3]);
    }
}
