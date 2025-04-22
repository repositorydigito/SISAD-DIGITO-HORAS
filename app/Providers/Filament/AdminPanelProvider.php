<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Filament\Widgets\CalendarWidget;
use Joaopaulolndev\FilamentEditProfile\FilamentEditProfilePlugin;
use App\Filament\Widgets\IncomeExpensesOverview;
use App\Filament\Widgets\ExpensesByCostCenter;
use App\Filament\Widgets\ExpensesByCategory;


class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('Dígito')
            ->brandLogo(asset('images/black.png'))
            ->darkModeBrandLogo(asset('images/white.png')) // Logo para modo oscuro
            ->colors([
                'primary' => Color::Blue,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                \App\Filament\Widgets\IncomeExpensesOverview::class,
                \App\Filament\Widgets\ExpensesByCostCenter::class,
                \App\Filament\Widgets\ExpensesByCategory::class
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
            ->plugins([
                \BezhanSalleh\FilamentShield\FilamentShieldPlugin::make(),
                FilamentEditProfilePlugin::make()
                    ->slug('mi-perfil')
                    ->setTitle('Mi Perfil')
                    ->setNavigationLabel('Mi Perfil')
                    ->setNavigationGroup('Configuracion')
                    ->shouldShowDeleteAccountForm(false)
                    ->shouldShowEditProfileForm(false)
                    ->setIcon('heroicon-o-user')
            ])->plugin(\TomatoPHP\FilamentUsers\FilamentUsersPlugin::make());
    }
}
