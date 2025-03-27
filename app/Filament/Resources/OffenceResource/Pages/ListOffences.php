<?php

namespace App\Filament\Resources\OffenceResource\Pages;

use App\Filament\Resources\OffenceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOffences extends ListRecords
{
    protected static string $resource = OffenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
