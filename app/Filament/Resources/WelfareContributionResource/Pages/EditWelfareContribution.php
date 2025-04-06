<?php

namespace App\Filament\Resources\WelfareContributionResource\Pages;

use App\Filament\Resources\WelfareContributionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWelfareContribution extends EditRecord
{
    protected static string $resource = WelfareContributionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
