<?php

declare(strict_types=1);

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Http\Responses\RespostaUniformeDeRecuperacao;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Ver a classe para a razão: sem isto, o formulário de recuperação
        // revela quem tem conta na plataforma.
        $this->app->singleton(
            FailedPasswordResetLinkRequestResponse::class,
            RespostaUniformeDeRecuperacao::class
        );
    }

    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        $this->registarVistas();
        $this->registarLimitadores();
    }

    /**
     * As páginas são componentes Inertia escritos de raiz. Nenhum starter kit é
     * usado: a secção 11 do agent.md exige interface visualmente original, e um
     * layout herdado teria de ser reescrito de qualquer forma.
     */
    private function registarVistas(): void
    {
        Fortify::loginView(fn () => Inertia::render('Auth/Entrar', [
            'estado' => session('status'),
        ]));

        Fortify::registerView(fn () => Inertia::render('Auth/Registar'));

        Fortify::requestPasswordResetLinkView(fn () => Inertia::render('Auth/PedirRecuperacao', [
            'estado' => session('status'),
        ]));

        Fortify::resetPasswordView(fn (Request $request) => Inertia::render('Auth/RedefinirPalavraPasse', [
            'token' => $request->route('token'),
            'email' => $request->query('email'),
        ]));
    }

    /**
     * Limites contra força bruta e credential stuffing (agent.md, secção 10).
     *
     * A chave do login combina o email com o IP: limitar só por IP castigaria
     * utilizadores atrás do mesmo NAT, e limitar só por email permitiria a um
     * atacante bloquear a conta de outra pessoa à vontade.
     */
    private function registarLimitadores(): void
    {
        RateLimiter::for('login', function (Request $request): Limit {
            $chave = Str::transliterate(Str::lower((string) $request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($chave);
        });
    }
}
