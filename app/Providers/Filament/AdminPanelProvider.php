<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\FinancialChart;
use App\Filament\Widgets\LatestBookings;
use App\Filament\Widgets\MyDailyTasks;
use App\Filament\Widgets\StatsOverview;
use Filament\Http\Middleware\Authenticate;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
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
            // ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                StatsOverview::class,
                FinancialChart::class,
                LatestBookings::class,
                
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
                FilamentShieldPlugin::make()->navigationGroup('Pengaturan Sistem'),
            ])
            ->navigationItems([
                NavigationItem::make('Finance Command Center')
                    ->url('/finance-center') 
                    ->icon('heroicon-o-computer-desktop')
                    ->group('Keuangan')
                    ->sort(1)
                    ->visible(fn () =>
                             auth()->user()?->hasRole('finance')
                        )
                    ->openUrlInNewTab(false),
                
                NavigationItem::make('Opersional Command Center')
                    ->url('/operations') 
                    ->icon('heroicon-o-computer-desktop')
                    ->group('Logistik & Aset')
                    ->sort(1)
                    ->visible(fn () =>
                             auth()->user()?->hasRole('operasional')
                        )
                    ->openUrlInNewTab(false),
                
                 NavigationItem::make('Creative Command Center')
                    ->url('/media') 
                    ->icon('heroicon-o-computer-desktop')
                    ->group('Media Creative')
                    ->sort(1)
                    ->visible(fn () =>
                             auth()->user()?->hasRole('media')
                        )
                    ->openUrlInNewTab(false),
                
                NavigationItem::make('Marketing Command Center')
                    ->url('/marketing') 
                    ->icon('heroicon-o-computer-desktop')
                    ->group('Marketing & Sales')
                    ->sort(1)
                    ->visible(fn () =>
                             auth()->user()?->hasRole('marketing')
                        )
                    ->openUrlInNewTab(false),
                
                NavigationItem::make('Report Center')
                    ->url('/executive-dashboard') 
                    ->icon('heroicon-o-computer-desktop')
                    ->group('Laporan')
                    ->sort(1)
                    ->visible(fn () =>
                             auth()->user()?->hasRole('owner')
                            || auth()->user()?->hasRole('super_admin')
                        )
                    ->openUrlInNewTab(false),
            ])
            ->navigationGroups([
                NavigationGroup::make()
                    ->label('Laporan')
                    ->collapsible(false),
                NavigationGroup::make()
                    ->label('Workspace')
                    ->collapsible(false),
                NavigationGroup::make()
                    ->label('Marketing & Sales')
                    ->collapsible(false),
                NavigationGroup::make()
                    ->label('Keuangan')
                    ->collapsible(true),
                NavigationGroup::make()
                    ->label('Manajemen SDM')
                    ->collapsible(true),
                NavigationGroup::make()
                    ->label('Media Creative')
                    ->collapsible(true),
                NavigationGroup::make()
                    ->label('Logistik & Aset')
                    ->collapsible(true),
                NavigationGroup::make()
                    ->label('Manajemen Agen')
                    ->collapsible(true),
                NavigationGroup::make()
                    ->label('Master Data')
                    ->collapsible(true),
                NavigationGroup::make()
                    ->label('Pengaturan Sistem')
                    ->collapsible(true)
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
