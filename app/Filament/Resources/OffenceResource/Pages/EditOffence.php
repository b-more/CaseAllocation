<?php

namespace App\Filament\Resources\OffenceResource\Pages;

use App\Filament\Resources\OffenceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOffence extends EditRecord
{
    protected static string $resource = OffenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
