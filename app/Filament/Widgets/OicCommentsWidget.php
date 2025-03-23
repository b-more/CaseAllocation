<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\InquiryFileResource;
use App\Models\CaseStatus;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class OicCommentsWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    // Only show for investigators
    public static function canView(): bool
    {
        return Auth::user()->role_id == 2; // Investigator role
    }

    public function table(Table $table): Table
    {
        $user = Auth::user();

        return $table
            ->query(
                CaseStatus::whereHas('case', fn ($query) => $query->where('dealing_officer', $user->id))
                    ->whereNotNull('oic_comment')
                    ->with(['case', 'user'])
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date & Time')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('case.if_number')
                    ->label('Inquiry File')
                    ->url(fn (CaseStatus $record) => InquiryFileResource::getUrl('view', ['record' => $record->case_id]))
                    ->searchable(),

                Tables\Columns\TextColumn::make('case.complainant')
                    ->label('Complainant')
                    ->limit(30),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('OIC')
                    ->searchable(),

                Tables\Columns\TextColumn::make('oic_comment')
                    ->label('Comment')
                    ->limit(50)
                    ->wrap(),

                Tables\Columns\IconColumn::make('is_read')
                    ->label('Read')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->actions([
                Tables\Actions\Action::make('viewCase')
                    ->label('View Case')
                    ->url(fn (CaseStatus $record) => InquiryFileResource::getUrl('view', ['record' => $record->case_id]))
                    ->icon('heroicon-o-eye'),

                Tables\Actions\Action::make('markAsRead')
                    ->label('Mark as Read')
                    ->icon('heroicon-o-check')
                    ->action(function (CaseStatus $record) {
                        $record->is_read = true;
                        $record->read_at = now();
                        $record->save();

                        // Notification
                        \Filament\Notifications\Notification::make()
                            ->title('Comment marked as read')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (CaseStatus $record) => !$record->is_read)
                    ->color('success'),
            ])
            ->paginated([5])
            ->heading('OIC Comments & Directions');
    }
}
