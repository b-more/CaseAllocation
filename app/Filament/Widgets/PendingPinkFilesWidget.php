<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\InquiryFileResource;
use App\Filament\Resources\PinkFileResource;
use App\Models\PinkFile;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class PendingPinkFilesWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $user = Auth::user();
        $isInvestigator = $user->role_id == 2;

        $query = PinkFile::whereDoesntHave('inquiryFile');

        // For investigators, only show their assigned pink files
        if ($isInvestigator) {
            $query->where('assigned_to', $user->id);
        }

        return $table
            ->query($query)
            ->columns([
                Tables\Columns\TextColumn::make('complainant_name')
                    ->label('Complainant')
                    ->searchable(),

                Tables\Columns\TextColumn::make('date_time_of_occurrence')
                    ->label('Date of Occurrence')
                    ->dateTime('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('crimeType.name')
                    ->label('Crime Type')
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('priority')
                    ->colors([
                        'danger' => 'very_high',
                        'warning' => 'high',
                        'success' => 'normal',
                        'gray' => 'low',
                    ]),

                Tables\Columns\TextColumn::make('assignedOfficer.name')
                    ->label('Assigned To')
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date Assigned')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('View Details')
                    ->url(fn (PinkFile $record): string => PinkFileResource::getUrl('view', ['record' => $record->id]))
                    ->icon('heroicon-o-eye'),

                Tables\Actions\Action::make('createInquiryFile')
                    ->label('Create Inquiry File')
                    ->icon('heroicon-o-document-duplicate')
                    ->url(fn (PinkFile $record): string => route('filament.admin.resources.inquiry-files.create', ['pinkFileId' => $record->id]))
                    ->color('success'),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([5])
            ->heading('Pending Pink Files');
    }
}
