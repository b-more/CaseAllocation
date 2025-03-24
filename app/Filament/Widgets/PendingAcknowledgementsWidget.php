<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\InquiryFileResource;
use App\Filament\Resources\PinkFileResource;
use App\Models\PinkFile;
use App\Models\User;
use App\Services\SmsService;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;

class PendingPinkFilesWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $user = Auth::user();
        $isInvestigator = $user->role_id == 2;

        // Base query - pink files without inquiry files
        $query = PinkFile::whereDoesntHave('inquiryFile');

        // For investigators, show different cases depending on acknowledgment status
        if ($isInvestigator) {
            $query->where('assigned_to', $user->id);
        }

        return $table
            ->query($query)
            ->columns([
                Tables\Columns\TextColumn::make('complainant_name')
                    ->label('Complainant')
                    ->searchable(),

                Tables\Columns\TextColumn::make('date_time_of_occurrence')
                    ->label('Date of Occurrence')
                    ->dateTime('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('crimeType.name')
                    ->label('Crime Type')
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('priority')
                    ->colors([
                        'danger' => 'very_high',
                        'warning' => 'high',
                        'success' => 'normal',
                        'gray' => 'low',
                    ]),

                // Add a column to show acknowledgment status
                Tables\Columns\IconColumn::make('acknowledged_at')
                    ->label('Acknowledged')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('assignedOfficer.name')
                    ->label('Assigned To')
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date Assigned')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('View Details')
                    ->url(fn (PinkFile $record): string => PinkFileResource::getUrl('view', ['record' => $record->id]))
                    ->icon('heroicon-o-eye'),

                // Acknowledge action - only for investigators and only if not yet acknowledged
                Tables\Actions\Action::make('acknowledgeCase')
                    ->label('Acknowledge')
                    ->icon('heroicon-o-check-badge')
                    ->action(function (PinkFile $record) {
                        // Mark the case as acknowledged
                        $record->update([
                            'acknowledged_at' => now(),
                        ]);

                        // Notify the OIC that the case has been acknowledged
                        $oicUsers = User::where('role_id', 1)->get(); // OIC role
                        foreach ($oicUsers as $oicUser) {
                            Notification::make()
                                ->title('Case Acknowledged')
                                ->body('Case for ' . $record->complainant_name . ' has been acknowledged by ' . Auth::user()->name)
                                ->sendToDatabase($oicUser);

                            // Send SMS notification if OIC has a phone number
                            if ($oicUser->phone) {
                                $message = "Case acknowledgement: Case for {$record->complainant_name} has been acknowledged by " . Auth::user()->name;
                                SmsService::sendMessage($message, $oicUser->phone);
                            }
                        }

                        Notification::make()
                            ->title('Case Acknowledged')
                            ->body('You have successfully acknowledged this case')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (PinkFile $record): bool =>
                        Auth::user()->role_id === 2 &&
                        Auth::user()->id === $record->assigned_to &&
                        $record->acknowledged_at === null
                    )
                    ->color('warning'),

                // Create Inquiry File action - only visible after acknowledgment
                Tables\Actions\Action::make('createInquiryFile')
                    ->label('Create Inquiry File')
                    ->icon('heroicon-o-document-plus')
                    ->url(fn (PinkFile $record): string => route('filament.admin.resources.inquiry-files.create', ['pinkFileId' => $record->id]))
                    ->color('success')
                    ->visible(fn (PinkFile $record): bool =>
                        $record->acknowledged_at !== null &&
                        (Auth::user()->role_id === 1 || (Auth::user()->role_id === 2 && Auth::user()->id === $record->assigned_to))
                    ),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([5])
            ->heading('Pending Pink Files');
    }
}
