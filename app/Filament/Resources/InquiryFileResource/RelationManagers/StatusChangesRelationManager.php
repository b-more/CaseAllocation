<?php

namespace App\Filament\Resources\InquiryFileResource\RelationManagers;

use App\Models\IfStatus;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class StatusChangesRelationManager extends RelationManager
{
    protected static string $relationship = 'statusChanges';

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?string $title = 'Status History & Comments';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('reason')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date & Time')
                    ->dateTime('d M Y H:i:s')
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Updated By')
                    ->sortable(),

                Tables\Columns\TextColumn::make('old_status_name')
                    ->label('From Status')
                    ->getStateUsing(function ($record) {
                        $status = IfStatus::find($record->old_status);
                        return $status ? $status->name : 'N/A';
                    })
                    ->badge()
                    ->color(function ($state) {
                        return match ($state) {
                            'Inquiry File Opened' => 'gray',
                            'Under Investigation' => 'warning',
                            'Taken to NPA' => 'info',
                            'Taken to Court' => 'primary',
                            'Case Closed' => 'success',
                            default => 'gray',
                        };
                    }),

                Tables\Columns\TextColumn::make('new_status_name')
                    ->label('To Status')
                    ->getStateUsing(function ($record) {
                        $status = IfStatus::find($record->new_status);
                        return $status ? $status->name : 'N/A';
                    })
                    ->badge()
                    ->color(function ($state) {
                        return match ($state) {
                            'Inquiry File Opened' => 'gray',
                            'Under Investigation' => 'warning',
                            'Taken to NPA' => 'info',
                            'Taken to Court' => 'primary',
                            'Case Closed' => 'success',
                            default => 'gray',
                        };
                    }),

                Tables\Columns\TextColumn::make('reason')
                    ->label('Reason')
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }
                        return $state;
                    }),

                Tables\Columns\TextColumn::make('oic_comment')
                    ->label('OIC Comment')
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }
                        return $state;
                    }),

                Tables\Columns\IconColumn::make('is_read')
                    ->label('Read')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->visible(function () {
                        // Only show this column to investigators
                        return Auth::user()->role_id == 2;
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user')
                    ->relationship('user', 'name')
                    ->label('Updated By'),

                Tables\Filters\TernaryFilter::make('is_read')
                    ->label('Read Status')
                    ->nullable(),
            ])
            ->headerActions([
                // Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('markAsRead')
                    ->label('Mark as Read')
                    ->icon('heroicon-o-check-circle')
                    ->action(function ($record) {
                        $record->update([
                            'is_read' => true,
                            'read_at' => now(),
                        ]);
                    })
                    ->visible(function ($record) {
                        // Only investigators can mark as read and only if not already read
                        $user = Auth::user();
                        return $user->role_id == 2 && !$record->is_read;
                    })
                    ->requiresConfirmation(false),
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
