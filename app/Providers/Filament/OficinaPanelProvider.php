<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\View;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class OficinaPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('oficina')
            ->path('oficina')
            ->login()
            ->topbar(false)
            ->sidebarFullyCollapsibleOnDesktop()
            ->maxContentWidth(Width::Full)
            ->brandName('Magna Oficina')
            ->brandLogo(asset('images/logo.svg'))
            ->brandLogoHeight('2.25rem')
            ->favicon(asset('images/favicon.svg'))
            ->colors([
                'primary' => Color::Zinc,
            ])
            ->discoverResources(in: app_path('Filament/Oficina/Resources'), for: 'App\Filament\Oficina\Resources')
            ->discoverPages(in: app_path('Filament/Oficina/Pages'), for: 'App\Filament\Oficina\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Oficina/Widgets'), for: 'App\Filament\Oficina\Widgets')
            ->widgets([])
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
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): View => view('filament.hooks.pwa-head'),
            )
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn (): View => view('filament.hooks.close-action-group-js'),
            )
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn (): View => view('filament.hooks.pwa-register'),
            );
    }
}
