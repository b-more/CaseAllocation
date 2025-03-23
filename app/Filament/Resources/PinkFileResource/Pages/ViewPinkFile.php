<?php

namespace App\Filament\Resources\PinkFileResource\Pages;

use App\Filament\Resources\PinkFileResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewPinkFile extends ViewRecord
{
    protected static string $resource = PinkFileResource::class;

    protected function getHeaderActions(): array
    {
        $actions = [
            Actions\EditAction::make()
                ->visible(fn() => Auth::user()->role_id !== 2 || $this->record->inquiryFile === null),
        ];

        // Only add the delete action for OIC and Admin
        if (Auth::user()->role_id !== 2) {
            $actions[] = Actions\DeleteAction::make()
                ->visible(fn() => $this->record->inquiryFile === null); // Can't delete if inquiry file exists
        }

        // Add Create Inquiry File action (if no inquiry file exists)
        if ($this->record->inquiryFile === null) {
            $isAssignedOfficer = Auth::user()->role_id === 2 && Auth::id() === $this->record->assigned_to;
            $isOIC = Auth::user()->role_id === 1;

            if ($isAssignedOfficer || $isOIC) {
                $actions[] = Actions\Action::make('createInquiryFile')
                    ->label('Create Inquiry File')
                    ->icon('heroicon-o-document-plus')
                    ->url(fn (): string => route('filament.admin.resources.inquiry-files.create', ['pinkFileId' => $this->record->id]))
                    ->color('success')
                    ->button(); // Make it a prominent button
            }
        } else {
            // If inquiry file exists, add an action to view it
            $actions[] = Actions\Action::make('viewInquiryFile')
                ->label('View Inquiry File')
                ->icon('heroicon-o-document-text')
                ->url(fn (): string => route('filament.admin.resources.inquiry-files.view', ['record' => $this->record->inquiryFile->id]))
                ->color('primary')
                ->button();
        }

        // Add OIC Comment action
        if (Auth::user()->role_id === 1) {
            $actions[] = Actions\Action::make('addComment')
                ->label('Add Comment')
                ->icon('heroicon-o-chat-bubble-left-ellipsis')
                ->form([
                    \Filament\Forms\Components\Textarea::make('oic_comment')
                        ->label('OIC Comment')
                        ->required()
                        ->maxLength(1000),

                    \Filament\Forms\Components\Checkbox::make('send_sms')
                        ->label('Send SMS notification to officer')
                        ->default(true),
                ])
                ->action(function (array $data) {
                    // Update the OIC comment
                    $this->record->update([
                        'oic_comment' => $data['oic_comment']
                    ]);

                    // Send SMS notification to the officer if requested
                    if ($data['send_sms'] && $this->record->assigned_to) {
                        $officer = \App\Models\User::find($this->record->assigned_to);

                        if ($officer && $officer->phone) {
                            $message = "New comment from OIC for case {$this->record->complainant_name}: " .
                                       substr($data['oic_comment'], 0, 100) .
                                       (strlen($data['oic_comment']) > 100 ? '...' : '');

                            $sent = \App\Services\SmsService::sendMessage($message, $officer->phone);

                            if ($sent) {
                                \Filament\Notifications\Notification::make()
                                    ->title('SMS sent successfully')
                                    ->success()
                                    ->send();
                            } else {
                                \Filament\Notifications\Notification::make()
                                    ->title('Failed to send SMS')
                                    ->warning()
                                    ->send();
                            }
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('Officer has no phone number')
                                ->warning()
                                ->send();
                        }
                    }

                    // Show notification
                    \Filament\Notifications\Notification::make()
                        ->title('Comment added successfully')
                        ->success()
                        ->send();
                });
        }

        return $actions;
    }
}
