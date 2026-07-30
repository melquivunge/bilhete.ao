<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        /*
         * O painel do Horizon fica sempre sujeito ao gate, incluindo em `local`.
         *
         * Por omissão, o Horizon abre o painel a qualquer pessoa quando o
         * ambiente é `local`, e só aplica o gate fora dele. Isso significaria que
         * a configuração exercitada em desenvolvimento não é a que protege a
         * produção — e é precisamente esse tipo de divergência que já nos custou
         * um painel sem CSP no ciclo C0.6 (KI-017).
         *
         * Aqui, o caminho testado é o mesmo caminho que protege.
         */
        Horizon::auth(fn ($request): bool => Gate::allows('viewHorizon', [$request->user()]));
    }

    /**
     * Quem pode ver o painel do Horizon.
     *
     * O painel expõe payloads de jobs, mensagens de exceção e nomes de filas.
     * A partir do Marco 4, os jobs em fila passam a transportar identificadores
     * de pagamento e de pedido — o painel deixa de ser diagnóstico e passa a ser
     * dados de negócio.
     *
     * Nega por omissão: só quem é staff, e a comparação é estrita, como no
     * canAccessPanel() do Filament. Um `$user` nulo nunca passa.
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', function (?User $user = null): bool {
            return $user?->is_staff === true;
        });
    }
}
