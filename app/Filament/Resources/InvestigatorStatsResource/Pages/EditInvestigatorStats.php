<?php

namespace App\Filament\Resources\InvestigatorStatsResource\Pages;

use App\Filament\Resources\InvestigatorStatsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInvestigatorStats extends EditRecord
{
    protected static string $resource = InvestigatorStatsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
