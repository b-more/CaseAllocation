<?php

namespace App\Filament\Resources\PinkFileResource\Pages;

use App\Filament\Resources\PinkFileResource;
use App\Models\User;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

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

    protected function afterCreate(): void
    {
        // Create a notification for the assigned officer
        $record = $this->record;

        if ($record->assigned_to) {
            // Get the user model instead of just the ID
            $assignedUser = User::find($record->assigned_to);

            if ($assignedUser) {
                Notification::make()
                    ->title('New Case Assigned')
                    ->body("You have been assigned a new case: {$record->complainant_name}")
                    ->actions([
                        \Filament\Notifications\Actions\Action::make('view')
                            ->button()
                            ->url(PinkFileResource::getUrl('view', ['record' => $record]))
                    ])
                    ->sendToDatabase($assignedUser); // Pass the User model, not just the ID
            }
        }
    }
}
