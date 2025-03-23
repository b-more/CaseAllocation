<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\CaseDistributionChart;
use App\Filament\Widgets\CaseStatisticsWidget;
use App\Filament\Widgets\InvestigatorDashboardWidget;
use App\Filament\Widgets\MonthlyCaseTrendChart;
use App\Filament\Widgets\OfficerPerformanceChart;
use App\Filament\Widgets\RecentAssignmentsWidget;
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
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                InvestigatorDashboardWidget::class,
                //RecentAssignmentsWidget::class,
                // You can keep these if you want them
                // Widgets\AccountWidget::class,
                // Widgets\FilamentInfoWidget::class, // You may want to remove this in production

                // Add these if you've created them
                // CaseStatisticsWidget::class,
                // CaseDistributionChart::class,
                // OfficerPerformanceChart::class,
                // MonthlyCaseTrendChart::class,
            ])
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
            ->authMiddleware([
                Authenticate::class,
            ])
            ->databaseNotifications()
            ->brandName('Anti-Fraud Office')
            ->favicon(asset('images/favicon.ico'));
    }
}
