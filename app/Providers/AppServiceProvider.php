<?php

declare(strict_types=1);

namespace App\Providers;

use App\Listeners\RegistarEventosDeAutenticacao;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        /*
         * Faz a atribuição em massa de um campo não permitido rebentar, em vez de
         * ser descartada em silêncio.
         *
         * O `#[Fillable]` do User já impedia `is_staff` de ser escrito pelo
         * registo público — mas impedia-o calando-se. Isso protege o caso do
         * atacante e esconde o caso do erro honesto: um campo mal ligado num
         * formulário do painel, ou uma chave errada num `updateOrCreate`,
         * falhariam sem uma linha de aviso.
         *
         * Fora de produção, onde um lançamento inesperado seria pior do que o
         * descarte, o silêncio deixa de ser possível. Isto vale mais à medida que
         * o Marco 1 acrescentar colunas de privilégio por empresa e cinema.
         */
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());

        $this->registarAuditoriaDeAutenticacao();
        $this->recusarConfiguracaoInsegura();
    }

    /**
     * Auditoria dos eventos de autenticação (agent.md, secção 10).
     */
    private function registarAuditoriaDeAutenticacao(): void
    {
        Event::listen(Login::class, [RegistarEventosDeAutenticacao::class, 'autenticou']);
        Event::listen(Failed::class, [RegistarEventosDeAutenticacao::class, 'falhou']);
        Event::listen(Lockout::class, [RegistarEventosDeAutenticacao::class, 'bloqueou']);
        Event::listen(Logout::class, [RegistarEventosDeAutenticacao::class, 'saiu']);
        Event::listen(Registered::class, [RegistarEventosDeAutenticacao::class, 'registou']);
        Event::listen(PasswordReset::class, [RegistarEventosDeAutenticacao::class, 'redefiniu']);
    }

    /**
     * Recusa arrancar em produção com depuração ligada.
     *
     * `APP_DEBUG=true` em produção transforma qualquer exceção não apanhada numa
     * página com host, credencial parcial e stack trace. É o modo de falha mais
     * banal e mais caro que existe, e não deve depender de alguém se lembrar.
     *
     * Falha alto no arranque, e não silenciosamente à primeira exceção.
     */
    private function recusarConfiguracaoInsegura(): void
    {
        if ($this->app->isProduction() && config('app.debug') === true) {
            throw new RuntimeException(
                'APP_DEBUG está ligado em produção. A aplicação recusa arrancar: '
                .'uma exceção não apanhada exporia host, credenciais parciais e stack trace.'
            );
        }
    }
}
