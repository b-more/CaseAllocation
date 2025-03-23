<?php

namespace App\Filament\Resources\PinkFileResource\Pages;

use App\Filament\Resources\PinkFileResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPinkFiles extends ListRecords
{
    protected static string $resource = PinkFileResource::class;

    protected static ?string $breadcrumb = 'Cases';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
