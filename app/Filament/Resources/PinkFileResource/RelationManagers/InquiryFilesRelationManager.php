<?php

namespace App\Filament\Resources\PinkFileResource\RelationManagers;

use App\Models\CaseStatus;
use App\Models\IfStatus;
use App\Models\User;
use App\Services\SmsService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class InquiryFilesRelationManager extends RelationManager
{
    protected static string $relationship = 'inquiryFile';

    protected static ?string $recordTitleAttribute = 'if_number';

    protected static ?string $title = 'Inquiry File';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('if_number')
                    ->label('Inquiry File Number')
                    ->disabled()
                    ->required(),

                Forms\Components\TextInput::make('complainant')
                    ->label('Complainant')
                    ->maxLength(255)
                    ->required(),

                Forms\Components\TextInput::make('offence')
                    ->label('Offence')
                    ->maxLength(255)
                    ->required(),

                Forms\Components\Select::make('if_status_id')
                    ->relationship('status', 'name')
                    ->required()
                    ->label('Case Status'),

                Forms\Components\Textarea::make('remarks')
                    ->label('Remarks')
                    ->maxLength(1000),

                // Field for OIC to add comment
                Forms\Components\Textarea::make('oic_comment')
                    ->label('OIC Comment')
                    ->placeholder('Add comments or directions for the investigator')
                    ->maxLength(1000)
                    ->visible(fn () => Auth::user()->role_id == 1), // Only visible to OIC
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('if_number')
            ->columns([
                Tables\Columns\TextColumn::make('if_number')
                    ->label('IF Number')
                    ->searchable(),

                Tables\Columns\TextColumn::make('date')
                    ->date('d M Y')
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
                    }),

                Tables\Columns\TextColumn::make('officer.name')
                    ->label('Dealing Officer'),

                Tables\Columns\IconColumn::make('acknowledged_at')
                    ->label('Acknowledged')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                // Show the latest OIC comment (from case_statuses)
                Tables\Columns\TextColumn::make('oic_comment')
                    ->label('OIC Comment')
                    ->getStateUsing(function ($record) {
                        $latestStatus = CaseStatus::where('case_id', $record->id)
                            ->whereNotNull('oic_comment')
                            ->latest()
                            ->first();

                        return $latestStatus ? $latestStatus->oic_comment : null;
                    })
                    ->limit(30),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('if_status')
                    ->relationship('status', 'name')
                    ->label('Case Status'),
            ])
            ->headerActions([
                // Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),

                // Add an action for OIC to add comments on status changes
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
                    ->action(function ($record, array $data) {
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

                // Add status change action with reason and notification
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
                    ->action(function ($record, array $data) {
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

                        // Notify the OIC users about the status change
                        $oicUsers = User::where('role_id', 1)->get();
                        foreach ($oicUsers as $oicUser) {
                            Notification::make()
                                ->title('Case Status Updated')
                                ->body("Inquiry file {$record->if_number} status changed from {$oldStatusName} to {$newStatusName}.")
                                ->sendToDatabase($oicUser);

                            // If OIC has a phone number, send SMS
                            if ($oicUser->phone) {
                                $message = "Case status update: {$record->if_number} changed from {$oldStatusName} to {$newStatusName}. Reason: " .
                                           substr($data['reason'], 0, 100) .
                                           (strlen($data['reason']) > 100 ? '...' : '');

                                SmsService::sendMessage($message, $oicUser->phone);
                            }
                        }

                        // Show notification
                        Notification::make()
                            ->title('Status Updated')
                            ->success()
                            ->send();
                    })
                    ->visible(function ($record) {
                        $user = Auth::user();
                        return $user->id === $record->dealing_officer &&
                               $record->acknowledged_at !== null;
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public function isReadOnly(): bool
    {
        // Make the relation read-only if the user is not the dealing officer or an OIC/admin
        $user = Auth::user();
        $record = $this->getOwnerRecord();

        if ($user->role_id == 1 || $user->role_id == 3) {
            // OIC and Admin can always edit
            return false;
        }

        // For investigators, check if they are the dealing officer
        if ($this->getRelationship()->first()) {
            return $user->id !== $this->getRelationship()->first()->dealing_officer;
        }

        // If there's no inquiry file yet, allow editing based on whether they're assigned to the case
        return $user->id !== $record->assigned_to;
    }
}
