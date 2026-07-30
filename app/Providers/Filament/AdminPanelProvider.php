<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Filament\Avatares\AvatarLocalComIniciais;
use App\Http\Middleware\ContentSecurityPolicy;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
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
            // Avatares gerados localmente. Ver a classe: o provedor por omissão
            // enviava o nome de cada membro do staff para ui-avatars.com.
            ->defaultAvatarProvider(AvatarLocalComIniciais::class)
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
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->middleware([
                /*
                 * O array de middleware do painel SUBSTITUI o grupo `web` do
                 * Laravel — não o herda. Confirmado no código do Filament
                 * (Panel\Concerns\HasMiddleware) e por execução: antes desta
                 * linha, `/admin` não emitia Content-Security-Policy nenhum,
                 * enquanto `/` emitia.
                 *
                 * Consequência a lembrar: qualquer middleware acrescentado ao
                 * grupo `web` em bootstrap/app.php NÃO chega aqui sem ser
                 * duplicado nesta lista. A sessão é partilhada com o site
                 * público; o middleware não é.
                 */
                ContentSecurityPolicy::class.':painel',
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
