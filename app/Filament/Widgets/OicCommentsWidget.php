<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\InquiryFileResource;
use App\Models\CaseStatus;
use App\Models\InquiryFile;
use App\Models\User;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        $userId = Auth::id();

        // First, let's verify if there are any OIC comments in the system
        $allCommentsCount = DB::table('case_statuses')
            ->whereNotNull('oic_comment')
            ->count();

        Log::info("Total OIC comments in system: {$allCommentsCount}");

        // Check all inquiry files assigned to this investigator
        $assignedFiles = DB::table('inquiry_files')
            ->where('dealing_officer', $userId)
            ->get(['id', 'if_number']);

        Log::info("Investigator ($userId) is assigned to " . $assignedFiles->count() . " files");

        // List all assigned file IDs for reference
        $assignedIds = $assignedFiles->pluck('id')->toArray();
        Log::info("Assigned file IDs: " . implode(', ', $assignedIds));

        // Check if there are any comments for these files
        $commentCount = DB::table('case_statuses')
            ->whereIn('case_id', $assignedIds)
            ->whereNotNull('oic_comment')
            ->count();

        Log::info("Comments found for investigator: {$commentCount}");

        // If debugging, dump a few comments to check their properties
        $sampleComments = DB::table('case_statuses')
            ->whereIn('case_id', $assignedIds)
            ->whereNotNull('oic_comment')
            ->limit(3)
            ->get();

        foreach ($sampleComments as $comment) {
            Log::info("Sample comment: ID={$comment->id}, case_id={$comment->case_id}, comment='" . substr($comment->oic_comment, 0, 30) . "...'");
        }

        // Create a robust query that absolutely ensures we get all relevant comments
        return $table
            ->query(function () use ($assignedIds) {
                // Use a clean query builder to ensure no caching issues
                return CaseStatus::whereIn('case_id', $assignedIds)
                    ->whereNotNull('oic_comment')
                    ->orderBy('created_at', 'desc');
            })
            ->emptyStateHeading('No OIC comments')
            ->emptyStateDescription('When OIC adds comments to your cases, they will appear here.')
            ->emptyStateIcon('heroicon-o-chat-bubble-bottom-center-text')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date & Time')
                    ->dateTime('d M Y H:i'),

                Tables\Columns\TextColumn::make('case_id')
                    ->label('Case Number')
                    ->formatStateUsing(function ($state) {
                        try {
                            $inquiryFile = InquiryFile::findOrFail($state);
                            return $inquiryFile->if_number;
                        } catch (\Exception $e) {
                            Log::error("Error fetching inquiry file ($state): " . $e->getMessage());
                            return "Case #$state";
                        }
                    })
                    ->url(function (CaseStatus $record) {
                        return InquiryFileResource::getUrl('view', ['record' => $record->case_id]);
                    }),

                Tables\Columns\TextColumn::make('user_id')
                    ->label('From')
                    ->formatStateUsing(function ($state) {
                        try {
                            $user = User::findOrFail($state);
                            return $user->name;
                        } catch (\Exception $e) {
                            Log::error("Error fetching user ($state): " . $e->getMessage());
                            return "OIC";
                        }
                    }),

                Tables\Columns\TextColumn::make('oic_comment')
                    ->label('Comment')
                    ->wrap()
                    ->limit(100),

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
                        try {
                            DB::transaction(function () use ($record) {
                                $record->is_read = true;
                                $record->read_at = now();
                                $record->save();

                                Log::info("Comment ID {$record->id} marked as read successfully");
                            });

                            \Filament\Notifications\Notification::make()
                                ->title('Comment marked as read')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Log::error("Error marking comment as read: " . $e->getMessage());

                            \Filament\Notifications\Notification::make()
                                ->title('Error marking as read')
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(fn (CaseStatus $record) => !$record->is_read)
                    ->color('success'),
            ])
            ->paginated([10])
            ->heading('OIC Comments & Directions');
    }
}
