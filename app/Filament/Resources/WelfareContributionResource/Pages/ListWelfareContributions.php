<?php

namespace App\Filament\Resources\WelfareContributionResource\Pages;

use App\Filament\Resources\WelfareContributionResource;
use App\Models\WelfareContribution;
use App\Models\WelfareMonth;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ListWelfareContributions extends ListRecords
{
    protected static string $resource = WelfareContributionResource::class;

    protected function getHeaderActions(): array
    {
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        return [
            Actions\Action::make('exportReport')
                ->label('Export Report')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->url(fn () => route('welfare.export-report'))
                ->openUrlInNewTab(),

            Actions\Action::make('paymentStatistics')
                ->label('Payment Statistics')
                ->icon('heroicon-o-chart-bar')
                ->color('primary')
                ->action(function () {
                    // This will be handled by a modal in the future if needed
                }),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
           // \App\Filament\Widgets\WelfareContributionStatsWidget::class,
        ];
    }

    protected function getTableFiltersFormWidth(): string
    {
        return 'md';
    }

    protected function getTableFiltersFormColumns(): int
    {
        return 2;
    }
}
