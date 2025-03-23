<?php

namespace App\Filament\Resources\InquiryFileResource\Pages;

use App\Filament\Resources\InquiryFileResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInquiryFiles extends ListRecords
{
    protected static string $resource = InquiryFileResource::class;

    protected function getHeaderActions(): array
    {
        return [
           // Actions\CreateAction::make(),
        ];
    }
}
