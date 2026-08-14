<?php

namespace App\Providers\Filament;

use App\Http\Controllers\Auth\FilamentLogoutController;
use Filament\Pages\Dashboard;
use Filament\Widgets\AccountWidget;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            ->path('admin')
            ->login()
            ->authGuard('admin')
            ->colors([
                'primary' => Color::Indigo,
                'danger' => Color::Red,
                'success' => Color::Green,
                'warning' => Color::Orange,
                'info' => Color::Blue,
            ])
            ->brandName('W9 Cafe — Admin')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->navigationGroups([
                'Menu',
                'Inventori',
                'Transaksi',
                'Keuangan',
                'Staff',
                'Analitik',
            ])
            ->font('Sans-Serif')
            ->spa()
            ->sidebarCollapsibleOnDesktop()
            ->collapsedSidebarWidth('4rem')
            ->sidebarWidth('17rem')
            ->widgets([
                AccountWidget::class,
            ])
            ->routes(function () {
                Route::post('/admin/logout', FilamentLogoutController::class)
                    ->name('filament.admin.auth.logout');
            })
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                ValidateCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
