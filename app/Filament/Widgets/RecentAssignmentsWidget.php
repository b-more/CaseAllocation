<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\InquiryFileResource;
use App\Filament\Resources\PinkFileResource;
use App\Models\InquiryFile;
use App\Models\PinkFile;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class RecentAssignmentsWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $user = Auth::user();

        return $table
            ->query(
                // Get recent assignments
                $user->role_id == 2 // Investigator role
                    ? InquiryFile::where('dealing_officer', $user->id)
                        ->latest()
                    : InquiryFile::latest() // OIC and Admin see all
            )
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('if_number')
                    ->label('IF Number')
                    ->searchable(),

                Tables\Columns\TextColumn::make('complainant')
                    ->searchable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('offence')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Assigned Date')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\IconColumn::make('acknowledged_at')
                    ->label('Acknowledged')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('status.name')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Inquiry File Opened' => 'gray',
                        'Under Investigation' => 'warning',
                        'Taken to NPA' => 'info',
                        'Taken to Court' => 'primary',
                        'Case Closed' => 'success',
                        default => 'gray',
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('View Details')
                    ->url(fn (InquiryFile $record): string => InquiryFileResource::getUrl('view', ['record' => $record->id]))
                    ->icon('heroicon-o-eye'),

                Tables\Actions\Action::make('acknowledge')
                    ->label('Acknowledge')
                    ->icon('heroicon-o-check-badge')
                    ->action(function (InquiryFile $record) {
                        // Add logic to acknowledge the case
                        $record->acknowledged_at = now();
                        $record->save();
                    })
                    ->requiresConfirmation()
                    ->visible(function (InquiryFile $record) {
                        $user = Auth::user();
                        return $user->id === $record->dealing_officer &&
                               $record->acknowledged_at === null;
                    })
                    ->color('success'),
            ])
            ->paginated([10, 25, 50, 100])
            ->heading('Recent Case Assignments');
    }
}
