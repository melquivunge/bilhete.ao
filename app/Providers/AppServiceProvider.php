<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

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
    }
}
