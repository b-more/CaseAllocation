<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Filament\Navigation\MenuItem;
use Joaopaulolndev\FilamentEditProfile\Pages\EditProfilePage;
use Joaopaulolndev\FilamentEditProfile\FilamentEditProfilePlugin;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Amber,
                'gray' => Color::Slate,
                'danger' => Color::Rose,
                'info' => Color::Sky,
                'success' => Color::Emerald,
                'warning' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->userMenuItems([
                'profile' => MenuItem::make()
                    ->label(fn() => auth()->user()->name)
                    ->url(fn (): string => EditProfilePage::getUrl())
                    ->icon('heroicon-m-user-circle'),
                    //If you are using tenancy need to check with the visible method where ->company() is the relation between the user and tenancy model as you called
                    // ->visible(function (): bool {
                    //     return auth()->user()->company()->exists();
                    // }),
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            // Do not register widgets here - they're being handled in the Dashboard class
            ->navigationGroups([
                NavigationGroup::make()
                    ->label('Case Management')
                    ->icon('heroicon-o-document-text'),
                NavigationGroup::make()
                    ->label('User Management')
                    ->icon('heroicon-o-users'),
                NavigationGroup::make()
                    ->label('System Settings')
                    ->icon('heroicon-o-cog')
                    ->collapsed(),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->plugins([
                FilamentEditProfilePlugin::make()
                    ->slug('my-profile')
                    ->setTitle('My Profile')
                    ->setNavigationLabel('My Profile')
                    ->setNavigationGroup('Group Profile')
                    ->setIcon('heroicon-o-user')
                    ->setSort(10)
                    ->canAccess(fn () => auth()->user()->id === 1)
                    ->shouldRegisterNavigation(false)
                    ->shouldShowDeleteAccountForm(false)
                    //->shouldShowSanctumTokens()
                    ->shouldShowBrowserSessionsForm()
                    //->shouldShowAvatarForm()
                    // ->customProfileComponents([
                    //     \App\Livewire\CustomProfileComponent::class,
                    // ])
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->databaseNotifications()
            ->brandName('Anti-Fraud Office')
            ->favicon(asset('images/favicon.ico'));
    }
}
