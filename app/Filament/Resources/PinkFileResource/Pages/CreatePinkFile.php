<?php

namespace App\Filament\Resources\PinkFileResource\Pages;

use App\Filament\Resources\PinkFileResource;
use App\Models\User;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use App\Services\SmsService;

class CreatePinkFile extends CreateRecord
{
    protected static string $resource = PinkFileResource::class;

    public function getBreadcrumb(): string
    {
        return 'Cases';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set default values if not provided
        if (!isset($data['date_time_of_occurrence'])) {
            $data['date_time_of_occurrence'] = now();
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        // Create a notification for the assigned officer
        $record = $this->record;

        if ($record->assigned_to) {
            // Get the user model instead of just the ID
            $assignedUser = User::find($record->assigned_to);

            if ($assignedUser) {
                // Create notification
                Notification::make()
                    ->title('New Case Assigned')
                    ->body("You have been assigned a new case: {$record->complainant_name}")
                    ->actions([
                        \Filament\Notifications\Actions\Action::make('view')
                            ->button()
                            ->url(PinkFileResource::getUrl('view', ['record' => $record]))
                    ])
                    ->sendToDatabase($assignedUser); // Pass the User model, not just the ID

                // Send SMS notification if the user has a phone number
                if ($assignedUser->phone) {
                    $message = "New case assigned: {$record->complainant_name}. Crime type: "
                            . ($record->crimeType ? $record->crimeType->name : 'Not specified')
                            . ". Priority: " . ucfirst($record->priority)
                            . ". Please login to acknowledge and create an inquiry file.";

                    // Send SMS notification
                    SmsService::sendMessage($message, $assignedUser->phone);
                }

                // Notify the OIC who created the case that it's been assigned
                $creator = Auth::user();
                if ($creator->id !== $assignedUser->id) {
                    Notification::make()
                        ->title('Case Assignment Notification Sent')
                        ->body("Officer {$assignedUser->name} has been notified of the new case assignment.")
                        ->success()
                        ->send();
                }
            }
        }
    }
}
