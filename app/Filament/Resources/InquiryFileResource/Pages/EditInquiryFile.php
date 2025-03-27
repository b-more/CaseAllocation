<?php

namespace App\Filament\Resources\InquiryFileResource\Pages;

use App\Filament\Resources\InquiryFileResource;
use App\Models\CaseStatus;
use App\Models\IfStatus;
use App\Models\User;
use App\Services\SmsService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;

class EditInquiryFile extends EditRecord
{
    protected static string $resource = InquiryFileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),

            Actions\Action::make('acknowledge')
                ->label('Acknowledge')
                ->icon('heroicon-o-check-badge')
                ->action(function () {
                    // Add logic to acknowledge the case
                    $this->record->acknowledged_at = now();
                    $this->record->save();

                    // Notify the OIC users about the acknowledgment
                    $oicUsers = User::where('role_id', 1)->get();
                    foreach ($oicUsers as $oicUser) {
                        Notification::make()
                            ->title('Case Acknowledged')
                            ->body('Inquiry file ' . $this->record->if_number . ' has been acknowledged by ' . Auth::user()->name)
                            ->sendToDatabase($oicUser);

                        // Send SMS notification if OIC has a phone number
                        if ($oicUser->phone) {
                            $message = "Case acknowledgement: {$this->record->if_number} has been acknowledged by " . Auth::user()->name;
                            SmsService::sendMessage($message, $oicUser->phone);
                        }
                    }

                    Notification::make()
                        ->title('Case Acknowledged')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->visible(function () {
                    $user = Auth::user();
                    return $user->id === $this->record->dealing_officer &&
                           $this->record->acknowledged_at === null;
                })
                ->color('success'),

                Actions\Action::make('changeStatus')
                ->label('Update Status')
                ->icon('heroicon-o-arrow-path')
                ->form([
                    \Filament\Forms\Components\Select::make('if_status_id')
                        ->label('New Status')
                        ->options(function () {
                            return IfStatus::pluck('name', 'id')->toArray();
                        })
                        ->required()
                        ->disabled()
                        ->reactive(), // Make this reactive to show/hide case close reason field

                    \Filament\Forms\Components\Textarea::make('case_close_reason')
                        ->label('Case Close Reason')
                        ->placeholder('Please provide a reason for closing this case')
                        ->required()
                        ->maxLength(500)
                        ->visible(fn (callable $get) => $get('if_status_id') == 5), // Only visible when status ID is 5 (Case Closed)

                    \Filament\Forms\Components\Textarea::make('reason')
                        ->label('Reason for Status Change')
                        ->required()
                        ->maxLength(500),
                ])
                ->action(function (array $data) {
                    // Record the old status
                    $oldStatus = $this->record->if_status_id;

                    // Build update data
                    $updateData = ['if_status_id' => $data['if_status_id']];

                    // Add case close reason if status is "Case Closed"
                    if ($data['if_status_id'] == 5 && isset($data['case_close_reason'])) {
                        $updateData['case_close_reason'] = $data['case_close_reason'];
                    } else {
                        // Clear case close reason if changing away from "Case Closed"
                        $updateData['case_close_reason'] = null;
                    }

                    // Update the record with the new status
                    $this->record->update($updateData);

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

                    // Notify the OIC users about the status change
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
                })
                ->visible(function () {
                    $user = Auth::user();
                    return $user->id === $this->record->dealing_officer &&
                           $this->record->acknowledged_at !== null;
                }),

            Actions\Action::make('exportPDF')
                ->label('Export PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->url(fn () => route('inquiry-file.export-pdf', ['id' => $this->record->id]))
                ->openUrlInNewTab()
                ->color('success'),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // If this is an OIC and they've added a comment, create a status entry
        if (Auth::user()->role_id == 1 && !empty($data['oic_comment'])) {
            // We'll handle the OIC comment in the afterSave method
            $this->oicComment = $data['oic_comment'];
            $this->sendSms = $data['send_sms'] ?? true;
        }

        // Check if status has changed and we need to record the reason
        if (isset($data['if_status_id']) &&
            $this->record->if_status_id !== $data['if_status_id'] &&
            !empty($data['status_change_reason'])) {
            $this->statusChangeReason = $data['status_change_reason'];
        }

        // Remove fields that are not part of the model
        unset($data['oic_comment']);
        unset($data['send_sms']);
        unset($data['status_change_reason']);
        unset($data['case_close_visible']);
        unset($data['court_type_visible']);
        unset($data['court_stage_visible']);

        return $data;
    }

    protected function afterSave(): void
    {
        $recordHasChanged = false;

        // Send notification if the dealing officer has changed
        if ($this->record->wasChanged('dealing_officer') && $this->record->dealing_officer) {
            $recordHasChanged = true;

            $newOfficer = User::find($this->record->dealing_officer);

            Notification::make()
                ->title('Case Assignment')
                ->body("You have been assigned to inquiry file: {$this->record->if_number}")
                ->actions([
                    \Filament\Notifications\Actions\Action::make('view')
                        ->button()
                        ->url(InquiryFileResource::getUrl('view', ['record' => $this->record]))
                ])
                ->sendToDatabase($newOfficer);

            // Send SMS notification if the officer has a phone number
            if ($newOfficer && $newOfficer->phone) {
                $message = "New case assignment: You have been assigned to inquiry file {$this->record->if_number}. Please login to the system to acknowledge.";
                SmsService::sendMessage($message, $newOfficer->phone);
            }
        }

        // Create status change record if status changed
        if ($this->record->wasChanged('if_status_id')) {
            $recordHasChanged = true;

            $reason = $this->statusChangeReason ?? 'Status updated via edit form';

            CaseStatus::create([
                'case_id' => $this->record->id,
                'user_id' => Auth::id(),
                'old_status' => $this->record->getOriginal('if_status_id'),
                'new_status' => $this->record->if_status_id,
                'reason' => $reason,
            ]);

            // Get the old and new status names for notifications
            $oldStatusName = IfStatus::find($this->record->getOriginal('if_status_id'))->name ?? 'Unknown';
            $newStatusName = IfStatus::find($this->record->if_status_id)->name ?? 'Unknown';

            // If this is an investigator updating status, notify OICs
            if (Auth::user()->role_id == 2) {
                // Notify the OIC users about the status change
                $oicUsers = User::where('role_id', 1)->get();
                foreach ($oicUsers as $oicUser) {
                    Notification::make()
                        ->title('Case Status Updated')
                        ->body("Inquiry file {$this->record->if_number} status changed from {$oldStatusName} to {$newStatusName}.")
                        ->sendToDatabase($oicUser);

                    // Send SMS notification if OIC has a phone number
                    if ($oicUser->phone) {
                        $message = "Case status update: {$this->record->if_number} changed from {$oldStatusName} to {$newStatusName}. Reason: " .
                                   substr($reason, 0, 100) .
                                   (strlen($reason) > 100 ? '...' : '');

                        SmsService::sendMessage($message, $oicUser->phone);
                    }
                }
            }
        }

        // If there's an OIC comment, create a status entry
        if (Auth::user()->role_id == 1 && isset($this->oicComment) && !empty($this->oicComment)) {
            $recordHasChanged = true;

            // Create a case status entry with the OIC comment
            CaseStatus::create([
                'case_id' => $this->record->id,
                'user_id' => Auth::id(),
                'old_status' => $this->record->if_status_id,
                'new_status' => $this->record->if_status_id, // Same status, just adding comment
                'oic_comment' => $this->oicComment,
                'reason' => 'OIC comment added',
            ]);

            // Send notification to the officer
            if ($this->sendSms && $this->record->dealing_officer) {
                $officer = User::find($this->record->dealing_officer);

                if ($officer) {
                    // Send in-app notification
                    Notification::make()
                        ->title('New OIC Comment')
                        ->body("OIC has added a comment to inquiry file: {$this->record->if_number}")
                        ->actions([
                            \Filament\Notifications\Actions\Action::make('view')
                                ->button()
                                ->url(InquiryFileResource::getUrl('view', ['record' => $this->record]))
                        ])
                        ->sendToDatabase($officer);

                    // Send SMS notification if the officer has a phone number
                    if ($officer->phone) {
                        $message = "New comment from OIC for inquiry file {$this->record->if_number}: " .
                                substr($this->oicComment, 0, 100) .
                                (strlen($this->oicComment) > 100 ? '...' : '');

                        SmsService::sendMessage($message, $officer->phone);
                    }
                }
            }
        }

        // Show a success notification only if something was changed
        if ($recordHasChanged) {
            Notification::make()
                ->title('Record Updated')
                ->success()
                ->send();
        }
    }
}
