<?php

namespace App\Filament\Resources\PinkFileResource\Pages;

use App\Filament\Resources\PinkFileResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListPinkFiles extends ListRecords
{
    protected static string $resource = PinkFileResource::class;

    protected static ?string $breadcrumb = 'Cases';

    protected function getHeaderActions(): array
    {
        // Only show the Create button for OIC (role_id 1)
        if (Auth::user()->role_id === 1) {
            return [
                Actions\CreateAction::make(),
            ];
        }

        // Return empty array for other roles (investigators, etc.)
        return [];
    }
}
