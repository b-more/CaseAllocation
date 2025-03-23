<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Services\SmsService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),

            Actions\Action::make('resetPassword')
                ->label('Reset Password')
                ->icon('heroicon-o-key')
                ->form([
                    \Filament\Forms\Components\TextInput::make('password')
                        ->label('New Password')
                        ->password()
                        ->required()
                        ->minLength(8)
                        ->confirmed(),

                    \Filament\Forms\Components\TextInput::make('password_confirmation')
                        ->label('Confirm Password')
                        ->password()
                        ->required(),

                    \Filament\Forms\Components\Checkbox::make('send_sms')
                        ->label('Send password via SMS')
                        ->default(true)
                        ->visible(fn () => !empty($this->record->phone)),
                ])
                ->action(function (array $data): void {
                    // Update the user's password
                    $this->record->update([
                        'password' => bcrypt($data['password']),
                    ]);

                    // Send an SMS with the new password if requested
                    if (isset($data['send_sms']) && $data['send_sms'] && !empty($this->record->phone)) {
                        $message = "Hello {$this->record->name}, your password for the Anti-Fraud Office Case Management System has been reset. Your new password is: {$data['password']}";
                        $sent = SmsService::sendMessage($message, $this->record->phone);

                        if ($sent) {
                            \Filament\Notifications\Notification::make()
                                ->title('Password reset SMS sent')
                                ->body('The new password has been sent to the user\'s phone number.')
                                ->success()
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('Failed to send password reset SMS')
                                ->body('There was an error sending the SMS. Please inform the user of their new password manually.')
                                ->warning()
                                ->send();
                        }
                    }

                    \Filament\Notifications\Notification::make()
                        ->title('Password has been reset')
                        ->success()
                        ->send();
                }),

            Actions\Action::make('toggleActive')
                ->label(fn (): string => $this->record->is_active ? 'Deactivate' : 'Activate')
                ->icon(fn (): string => $this->record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                ->color(fn (): string => $this->record->is_active ? 'danger' : 'success')
                ->requiresConfirmation()
                ->action(function (): void {
                    $this->record->is_active = !$this->record->is_active;
                    $this->record->save();

                    \Filament\Notifications\Notification::make()
                        ->title($this->record->is_active ? 'User activated' : 'User deactivated')
                        ->success()
                        ->send();
                }),

            Actions\Action::make('sendTestSMS')
                ->label('Send Test SMS')
                ->icon('heroicon-o-device-phone-mobile')
                ->color('warning')
                ->requiresConfirmation()
                ->action(function (): void {
                    if (!$this->record->phone) {
                        \Filament\Notifications\Notification::make()
                            ->title('No Phone Number')
                            ->body('This user does not have a phone number to send an SMS to.')
                            ->danger()
                            ->send();
                        return;
                    }

                    $message = "Hello {$this->record->name}, this is a test SMS from the Anti-Fraud Office Case Management System.";
                    $sent = SmsService::sendMessage($message, $this->record->phone);

                    if ($sent) {
                        \Filament\Notifications\Notification::make()
                            ->title('Test SMS Sent')
                            ->body('A test SMS has been sent to ' . $this->record->phone)
                            ->success()
                            ->send();
                    } else {
                        \Filament\Notifications\Notification::make()
                            ->title('Failed to Send SMS')
                            ->body('There was an error sending the test SMS. Please check the logs.')
                            ->danger()
                            ->send();
                    }
                })
                ->visible(fn (): bool => !empty($this->record->phone)),
        ];
    }
}
