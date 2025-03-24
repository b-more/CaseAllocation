<?php

namespace App\Filament\Resources\InquiryFileResource\Pages;

use App\Filament\Resources\InquiryFileResource;
use App\Models\CaseStatus;
use App\Models\IfStatus;
use App\Models\User;
use App\Services\SmsService;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewInquiryFile extends ViewRecord
{
    protected static string $resource = InquiryFileResource::class;

    protected function getHeaderActions(): array
    {
        $actions = [];
        $user = Auth::user();

        // All users can see these actions
        $actions[] = Actions\EditAction::make()
            ->visible(function() use ($user) {
                // Investigators can only edit if they are the dealing officer
                if ($user->role_id === 2) {
                    return $user->id === $this->record->dealing_officer;
                }

                // OIC and Admin can always edit
                return true;
            });

        $actions[] = Actions\Action::make('exportPDF')
            ->label('Export PDF')
            ->icon('heroicon-o-document-arrow-down')
            ->url(fn () => route('inquiry-file.export-pdf', ['id' => $this->record->id]))
            ->openUrlInNewTab()
            ->color('success');

        // Only OIC and Admin can delete
        if ($user->role_id !== 2) {
            $actions[] = Actions\DeleteAction::make();
        }

        // Acknowledgment action for investigators
        // if ($user->role_id === 2 && $user->id === $this->record->dealing_officer && $this->record->acknowledged_at === null) {
        //     $actions[] = Action::make('acknowledge')
        //         ->label('Acknowledge')
        //         ->icon('heroicon-o-check-badge')
        //         ->action(function () {
        //             // Add logic to acknowledge the case
        //             $this->record->acknowledged_at = now();
        //             $this->record->save();

        //             // Notify the OIC users about the acknowledgment
        //             $oicUsers = User::where('role_id', 1)->get();
        //             foreach ($oicUsers as $oicUser) {
        //                 Notification::make()
        //                     ->title('Case Acknowledged')
        //                     ->body('Inquiry file ' . $this->record->if_number . ' has been acknowledged by ' . Auth::user()->name)
        //                     ->sendToDatabase($oicUser);

        //                 // Send SMS notification if OIC has a phone number
        //                 if ($oicUser->phone) {
        //                     $message = "Case acknowledgement: {$this->record->if_number} has been acknowledged by " . Auth::user()->name;
        //                     SmsService::sendMessage($message, $oicUser->phone);
        //                 }
        //             }

        //             Notification::make()
        //                 ->title('Case Acknowledged')
        //                 ->success()
        //                 ->send();
        //         })
        //         ->requiresConfirmation()
        //         ->color('success');
        // }

        // Status update action for investigators - only if acknowledged
        if ($user->role_id === 2 && $user->id === $this->record->dealing_officer && $this->record->acknowledged_at !== null) {
            $actions[] = Action::make('changeStatus')
                ->label('Update Status')
                ->icon('heroicon-o-arrow-path')
                ->form([
                    \Filament\Forms\Components\Select::make('if_status_id')
                        ->label('New Status')
                        ->options(function () {
                            return IfStatus::pluck('name', 'id')->toArray();
                        })
                        ->required(),

                    \Filament\Forms\Components\Textarea::make('reason')
                        ->label('Reason for Change')
                        ->required(),
                ])
                ->action(function (array $data) {
                    // Record the old status
                    $oldStatus = $this->record->if_status_id;

                    // Update the record with the new status
                    $this->record->update(['if_status_id' => $data['if_status_id']]);

                    // Create a status change record
                    $statusChange = CaseStatus::create([
                        'case_id' => $this->record->id,
                        'user_id' => Auth::id(),
                        'old_status' => $oldStatus,
                        'new_status' => $data['if_status_id'],
                        'reason' => $data['reason'],
                    ]);

                    // Get the old and new status names for notifications
                    $oldStatusName = IfStatus::find($oldStatus)->name ?? 'Unknown';
                    $newStatusName = IfStatus::find($data['if_status_id'])->name ?? 'Unknown';

                    // Notify OIC users about the status change
                    $oicUsers = User::where('role_id', 1)->get();
                    foreach ($oicUsers as $oicUser) {
                        Notification::make()
                            ->title('Case Status Updated')
                            ->body("Inquiry file {$this->record->if_number} status changed from {$oldStatusName} to {$newStatusName}.")
                            ->sendToDatabase($oicUser);

                        // Send SMS notification if OIC has a phone number
                        if ($oicUser->phone) {
                            $message = "Case status update: {$this->record->if_number} changed from {$oldStatusName} to {$newStatusName}. Reason: " .
                                       substr($data['reason'], 0, 100) .
                                       (strlen($data['reason']) > 100 ? '...' : '');

                            SmsService::sendMessage($message, $oicUser->phone);
                        }
                    }

                    Notification::make()
                        ->title('Status Updated')
                        ->success()
                        ->send();
                });
        }

        // OIC Comment action
        if ($user->role_id === 1) {
            $actions[] = Action::make('addOicComment')
                ->label('Add Comment')
                ->icon('heroicon-o-chat-bubble-left-ellipsis')
                ->form([
                    \Filament\Forms\Components\Textarea::make('oic_comment')
                        ->label('OIC Comment')
                        ->required()
                        ->maxLength(1000),

                    \Filament\Forms\Components\Checkbox::make('send_sms')
                        ->label('Send SMS notification to officer')
                        ->default(true),
                ])
                ->action(function (array $data) {
                    // Create a status change record with the OIC comment
                    $statusChange = CaseStatus::create([
                        'case_id' => $this->record->id,
                        'user_id' => Auth::id(),
                        'old_status' => $this->record->if_status_id,
                        'new_status' => $this->record->if_status_id, // Same status, just adding comment
                        'oic_comment' => $data['oic_comment'],
                        'reason' => 'OIC comment added',
                    ]);

                    // Send notification to the dealing officer
                    if ($data['send_sms'] && $this->record->dealing_officer) {
                        $officer = User::find($this->record->dealing_officer);

                        if ($officer) {
                            // Send in-app notification
                            Notification::make()
                                ->title('New OIC Comment')
                                ->body("OIC has added a comment to inquiry file: {$this->record->if_number}")
                                ->actions([
                                    \Filament\Notifications\Actions\Action::make('view')
                                        ->button()
                                        ->url(InquiryFileResource::getUrl('view', ['record' => $this->record->id]))
                                ])
                                ->sendToDatabase($officer);

                            // Send SMS notification if the officer has a phone number
                            if ($officer->phone) {
                                $message = "New comment from OIC for inquiry file {$this->record->if_number}: " .
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
                    }

                    Notification::make()
                        ->title('Comment Added')
                        ->success()
                        ->send();
                });
        }

        return $actions;
    }
}
