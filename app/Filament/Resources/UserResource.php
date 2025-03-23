<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\Role;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    //protected static ?string $navigationGroup = 'User Management';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignorable: fn ($record) => $record),

                Forms\Components\TextInput::make('phone')
                    ->tel()
                    ->label('Phone Number')
                    ->helperText('Used for SMS notifications. Include country code (e.g., 260XXXXXXXXX)')
                    ->maxLength(20),

                Forms\Components\TextInput::make('password')
                    ->password()
                    ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->maxLength(255),

                Forms\Components\Select::make('role_id')
                    ->relationship('role', 'name')
                    ->required()
                    ->preload(),

                Forms\Components\Toggle::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->required(),

                Forms\Components\FileUpload::make('image')
                    ->label('Profile Image')
                    ->image()
                    ->directory('profile-photos')
                    ->visibility('public')
                    ->maxSize(1024)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label('Photo')
                    ->circular(),

                Tables\Columns\TextColumn::make('name')
                    ->searchable(),

                Tables\Columns\TextColumn::make('email')
                    ->searchable(),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Phone')
                    ->searchable(),

                Tables\Columns\TextColumn::make('role.name')
                    ->label('Role')
                    ->badge()
                    ->color(fn (string $state): string => match (strtolower($state)) {
                        'admin' => 'danger',
                        'oic' => 'primary',
                        'investigator' => 'success',
                        default => 'gray',
                    })
                    ->searchable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->relationship('role', 'name')
                    ->label('Role'),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active')
                    ->placeholder('All Users')
                    ->trueLabel('Active Users')
                    ->falseLabel('Inactive Users'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),

                Tables\Actions\Action::make('toggleActive')
                    ->label(fn (User $record): string => $record->is_active ? 'Deactivate' : 'Activate')
                    ->icon(fn (User $record): string => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn (User $record): string => $record->is_active ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->action(function (User $record): void {
                        $record->is_active = !$record->is_active;
                        $record->save();
                    }),

                Tables\Actions\Action::make('sendTestSMS')
                    ->label('Send Test SMS')
                    ->icon('heroicon-o-device-phone-mobile')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (User $record): void {
                        if (!$record->phone) {
                            \Filament\Notifications\Notification::make()
                                ->title('No Phone Number')
                                ->body('This user does not have a phone number to send an SMS to.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $message = "Hello {$record->name}, this is a test SMS from the Anti-Fraud Office Case Management System.";
                        $sent = \App\Services\SmsService::sendMessage($message, $record->phone);

                        if ($sent) {
                            \Filament\Notifications\Notification::make()
                                ->title('Test SMS Sent')
                                ->body('A test SMS has been sent to ' . $record->phone)
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
                    ->visible(fn (User $record): bool => !empty($record->phone))
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function (Builder $query): void {
                            $query->update(['is_active' => true]);
                        }),

                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function (Builder $query): void {
                            $query->update(['is_active' => false]);
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
            'view' => Pages\ViewUser::route('/{record}'),
        ];
    }

    // public static function shouldRegisterNavigation(): bool
    // {
    //     // Only show user management for OIC and Admin
    //     $user = Auth::user();
    //     return $user->role_id === Role::OIC || $user->role_id === Role::ADMIN;
    // }

    public static function shouldRegisterNavigation(): bool
    {
        // Only show user management for admin and OIC roles
        $user = Auth::user();
        return in_array($user->role_id, [1, 3]); // OIC (1) or Admin (3)
    }
}
