<?php

namespace App\Filament\Resources\PinkFileResource\Pages;

use App\Filament\Resources\PinkFileResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPinkFile extends ViewRecord
{
    protected static string $resource = PinkFileResource::class;



    protected function getHeaderActions(): array
    {
        return [
            //Actions\EditAction::make(),
            //Actions\DeleteAction::make(),

            // Actions\Action::make('createInquiryFile')
            //     ->label('Create Inquiry File')
            //     ->icon('heroicon-o-document-duplicate')
            //     ->url(fn (): string => route('filament.admin.resources.inquiry-files.create', ['pinkFileId' => $this->record->id]))
            //     ->color('success')
            //     ->visible(fn (): bool => $this->record->inquiryFile === null),
        ];
    }
}
