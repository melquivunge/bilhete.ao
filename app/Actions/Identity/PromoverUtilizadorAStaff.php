<?php

declare(strict_types=1);

namespace App\Actions\Identity;

use App\Models\User;

/**
 * Único caminho para conceder acesso ao painel administrativo.
 *
 * A coluna `is_staff` está deliberadamente fora do `#[Fillable]` do User, e por
 * isso `User::create()` e `fill()` não a conseguem escrever. A razão é concreta:
 * uma allowlist de atribuição em massa é por modelo, não por rota. Se a coluna
 * fosse acrescentada ao `#[Fillable]` para conveniência dos formulários do
 * Filament, passaria a ser atribuível também pelo `POST /register` público — e o
 * controlo de acesso ao painel tornava-se decorativo, porque qualquer pessoa se
 * promovia no formulário de criação de conta.
 *
 * `forceFill` é usado de propósito: é explícito, não depende da allowlist, e
 * obriga quem promove alguém a passar por aqui.
 */
class PromoverUtilizadorAStaff
{
    public function executar(User $utilizador): User
    {
        $utilizador->forceFill(['is_staff' => true])->save();

        return $utilizador;
    }

    public function revogar(User $utilizador): User
    {
        $utilizador->forceFill(['is_staff' => false])->save();

        return $utilizador;
    }
}
