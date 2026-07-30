<?php

declare(strict_types=1);

namespace App\Listeners;

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Log;

/**
 * Auditoria dos eventos de autenticação, exigida pela secção 10 do `agent.md`.
 *
 * Fica aqui, e não no Marco 6, por uma razão prática: instrumentar os eventos
 * agora custa um ficheiro; fazê-lo depois, com dados de vendas e vários cinemas
 * em jogo, obriga a reconstituir o que aconteceu sem registo nenhum. Uma
 * investigação de acesso indevido precisa de histórico anterior ao incidente.
 *
 * O que é registado: quem, quando, de onde, e o resultado. Nunca a credencial —
 * as tentativas falhadas registam o identificador tentado, não a palavra-passe.
 * O processador RedigirDadosSensiveis é a rede de segurança para o caso de
 * alguém acrescentar aqui um campo descuidado.
 */
class RegistarEventosDeAutenticacao
{
    public function autenticou(Login $evento): void
    {
        $this->registar('autenticacao.entrada', [
            'utilizador' => $evento->user->getAuthIdentifier(),
            'guard' => $evento->guard,
        ]);
    }

    public function falhou(Failed $evento): void
    {
        $this->registar('autenticacao.falha', [
            // O identificador tentado é registado; a palavra-passe nunca.
            'identificador_tentado' => $evento->credentials['email'] ?? null,
            'conta_existe' => $evento->user !== null,
            'guard' => $evento->guard,
        ]);
    }

    public function bloqueou(Lockout $evento): void
    {
        $this->registar('autenticacao.bloqueio_por_limite', [
            'identificador_tentado' => $evento->request->input('email'),
        ]);
    }

    public function saiu(Logout $evento): void
    {
        $this->registar('autenticacao.saida', [
            'utilizador' => $evento->user->getAuthIdentifier(),
            'guard' => $evento->guard,
        ]);
    }

    public function registou(Registered $evento): void
    {
        $this->registar('autenticacao.registo', [
            'utilizador' => $evento->user->getAuthIdentifier(),
        ]);
    }

    public function redefiniu(PasswordReset $evento): void
    {
        $this->registar('autenticacao.palavra_passe_redefinida', [
            'utilizador' => $evento->user->getAuthIdentifier(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $contexto
     */
    private function registar(string $evento, array $contexto): void
    {
        Log::info($evento, [
            ...$contexto,
            'ip' => request()->ip(),
            'agente' => substr((string) request()->userAgent(), 0, 255),
        ]);
    }
}
