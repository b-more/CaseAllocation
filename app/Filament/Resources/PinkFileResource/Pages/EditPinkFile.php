<?php

namespace App\Filament\Resources\PinkFileResource\Pages;

use App\Filament\Resources\PinkFileResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditPinkFile extends EditRecord
{
    protected static string $resource = PinkFileResource::class;





    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),

            Actions\Action::make('createInquiryFile')
                ->label('Create Inquiry File')
                ->icon('heroicon-o-document-duplicate')
                ->url(fn (): string => route('filament.admin.resources.inquiry-files.create', ['pinkFileId' => $this->record->id]))
                ->color('success')
                ->visible(fn (): bool => $this->record->inquiryFile === null),
        ];
    }

    protected function afterSave(): void
    {
        // Send notification if the assigned officer has changed
        if ($this->record->wasChanged('assigned_to') && $this->record->assigned_to) {
            Notification::make()
                ->title('Case Assignment')
                ->body("You have been assigned to case: {$this->record->complainant_name}")
                ->actions([
                    \Filament\Notifications\Actions\Action::make('view')
                        ->button()
                        ->url(PinkFileResource::getUrl('view', ['record' => $this->record]))
                ])
                ->sendToDatabase($this->record->assigned_to);
        }
    }
}
