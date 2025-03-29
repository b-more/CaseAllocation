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
use Filament\Notifications\Notification;
use Filament\Support\Colors\Color;
use Illuminate\Database\Eloquent\Collection;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $modelLabel = 'System User';

    protected static ?string $pluralModelLabel = 'System Users';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('User Information')
                    ->description('Basic user account details')
                    ->icon('heroicon-o-user')
                    ->columns(2)
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
                            ->prefix('+26')
                            ->helperText('Used for SMS notifications. Include country code (e.g., 260XXXXXXXXX)')
                            ->maxLength(20),

                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->maxLength(255),
                    ]),

                Forms\Components\Section::make('Role & Status')
                    ->description('User role and account status')
                    ->icon('heroicon-o-cog')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('role_id')
                            ->relationship('role', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->helperText('Inactive users cannot log in')
                            ->default(true)
                            ->required(),
                    ]),

                Forms\Components\Section::make('Profile Image')
                    ->description('User profile photo')
                    ->icon('heroicon-o-camera')
                    ->schema([
                        Forms\Components\FileUpload::make('image')
                            ->label('Profile Image')
                            ->image()
                            ->directory('profile-photos')
                            ->visibility('public')
                            ->maxSize(1024)
                            ->imageEditor(),
                    ]),

                Forms\Components\Section::make('Notifications')
                    ->description('Welcome message settings')
                    ->icon('heroicon-o-bell')
                    ->schema([
                        Forms\Components\Toggle::make('send_welcome_sms')
                            ->label('Send Welcome SMS')
                            ->helperText('Send a welcome SMS with account details')
                            ->default(true),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label('Photo')
                    ->circular()
                    ->defaultImageUrl(fn (User $record): string =>
                        "https://ui-avatars.com/api/?name=" . urlencode($record->name) . "&color=FFFFFF&background=111827"),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable()
                    ->color('gray')
                    ->icon('heroicon-m-envelope'),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Phone')
                    ->searchable()
                    ->formatStateUsing(fn (string $state): string => $state ? "+$state" : '-')
                    ->icon('heroicon-m-device-phone-mobile'),

                Tables\Columns\TextColumn::make('role.name')
                    ->label('Role')
                    ->badge()
                    ->color(fn (string $state): string => match (strtolower($state)) {
                        'admin' => 'danger',
                        'oic' => 'primary',
                        'investigator' => 'success',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor(Color::Green)
                    ->falseColor(Color::Rose),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->icon('heroicon-m-calendar'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->relationship('role', 'name')
                    ->label('Role')
                    ->indicator('Role')
                    ->multiple(),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active')
                    ->placeholder('All Users')
                    ->trueLabel('Active Users')
                    ->falseLabel('Inactive Users')
                    ->indicator('Status'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->color('gray'),

                    Tables\Actions\EditAction::make()
                        ->color('primary'),

                    Tables\Actions\DeleteAction::make()
                        ->color('danger'),

                    Tables\Actions\Action::make('toggleActive')
                        ->label(fn (User $record): string => $record->is_active ? 'Deactivate' : 'Activate')
                        ->icon(fn (User $record): string => $record->is_active ? 'heroicon-m-x-circle' : 'heroicon-m-check-circle')
                        ->color(fn (User $record): string => $record->is_active ? 'danger' : 'success')
                        ->requiresConfirmation()
                        ->modalHeading(fn (User $record): string => ($record->is_active ? 'Deactivate' : 'Activate') . ' User')
                        ->modalDescription(fn (User $record): string =>
                            'Are you sure you want to ' .
                            ($record->is_active ? 'deactivate' : 'activate') .
                            ' this user? ' .
                            ($record->is_active ? 'They will no longer be able to log in.' : 'They will be able to log in again.'))
                        ->modalSubmitActionLabel('Confirm')
                        ->action(function (User $record): void {
                            $record->is_active = !$record->is_active;
                            $record->save();

                            $status = $record->is_active ? 'activated' : 'deactivated';

                            Notification::make()
                                ->title("User $status")
                                ->body("User has been $status successfully.")
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('sendWelcomeSMS')
                        ->label('Send Welcome SMS')
                        ->icon('heroicon-m-device-phone-mobile')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Send Welcome SMS')
                        ->modalDescription('Send a welcome SMS to this user with their account details.')
                        ->modalSubmitActionLabel('Send SMS')
                        ->action(function (User $record): void {
                            if (!$record->phone) {
                                Notification::make()
                                    ->title('No Phone Number')
                                    ->body('This user does not have a phone number to send an SMS to.')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            $message = "Welcome {$record->name} to the Anti-Fraud Office Case Management System. Your account has been created with email: {$record->email}";
                            $sent = \App\Services\SmsService::sendMessage($message, $record->phone);

                            if ($sent) {
                                Notification::make()
                                    ->title('Welcome SMS Sent')
                                    ->body('A welcome SMS has been sent to ' . $record->phone)
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Failed to Send SMS')
                                    ->body('There was an error sending the welcome SMS. Please check the logs.')
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->visible(fn (User $record): bool => !empty($record->phone)),
                ])
                ->tooltip('Actions')
                ->icon('heroicon-m-ellipsis-vertical'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->icon('heroicon-m-trash')
                        ->modalHeading('Delete Selected Users')
                        ->modalDescription('Are you sure you want to delete these users? This action cannot be undone.'),

                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-m-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Activate Selected Users')
                        ->modalDescription('Are you sure you want to activate these users? They will be able to log in.')
                        ->modalSubmitActionLabel('Activate')
                        ->action(function (Builder $query): void {
                            $count = $query->count();
                            $query->update(['is_active' => true]);

                            Notification::make()
                                ->title("Users Activated")
                                ->body("$count users have been activated successfully.")
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-m-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Deactivate Selected Users')
                        ->modalDescription('Are you sure you want to deactivate these users? They will no longer be able to log in.')
                        ->modalSubmitActionLabel('Deactivate')
                        ->action(function (Builder $query): void {
                            $count = $query->count();
                            $query->update(['is_active' => false]);

                            Notification::make()
                                ->title("Users Deactivated")
                                ->body("$count users have been deactivated successfully.")
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('sendWelcomeSMS')
                        ->label('Send Welcome SMS')
                        ->icon('heroicon-m-device-phone-mobile')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Send Welcome SMS')
                        ->modalDescription('Send a welcome SMS to all selected users with their account details.')
                        ->modalSubmitActionLabel('Send SMS')
                        ->action(function (Collection $records): void {
                            $sentCount = 0;
                            $failedCount = 0;

                            foreach ($records as $record) {
                                if (!$record->phone) {
                                    $failedCount++;
                                    continue;
                                }

                                $message = "Welcome {$record->name} to the Anti-Fraud Office Case Management System. Your account has been created with email: {$record->email}";
                                $sent = \App\Services\SmsService::sendMessage($message, $record->phone);

                                if ($sent) {
                                    $sentCount++;
                                } else {
                                    $failedCount++;
                                }
                            }

                            if ($sentCount > 0) {
                                Notification::make()
                                    ->title('Welcome SMS Sent')
                                    ->body("Welcome SMS sent to $sentCount users" . ($failedCount > 0 ? " ($failedCount failed)" : ""))
                                    ->success()
                                    ->send();
                            } else if ($failedCount > 0) {
                                Notification::make()
                                    ->title('Failed to Send SMS')
                                    ->body("Failed to send welcome SMS to $failedCount users")
                                    ->danger()
                                    ->send();
                            }
                        }),
                ])
                ->icon('heroicon-m-cog'),
            ])
            ->emptyStateIcon('heroicon-o-users')
            ->emptyStateHeading('No users yet')
            ->emptyStateDescription('Once you add users, they will appear here.')
            ->emptyStateActions([
                Tables\Actions\Action::make('create')
                    ->label('Create User')
                    ->icon('heroicon-m-plus')
                    ->url(fn (): string => route('filament.admin.resources.users.create'))
                    ->color('primary'),
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

    public static function shouldRegisterNavigation(): bool
    {
        // Only show user management for admin and OIC roles
        $user = Auth::user();
        return in_array($user->role_id, [1, 3]); // OIC (1) or Admin (3)
    }

    /**
     * Get the global search elements.
     *
     * @param \Illuminate\Database\Eloquent\Model $record
     * @return array
     */
    public static function getGlobalSearchResultDetails(\Illuminate\Database\Eloquent\Model $record): array
    {
        return [
            'Role' => $record->role->name,
            'Status' => $record->is_active ? 'Active' : 'Inactive',
        ];
    }

    /**
     * Get the global search results.
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email', 'phone'];
    }
}
