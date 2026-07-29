<?php

declare(strict_types=1);

namespace App\Http\Responses;

use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Torna o pedido de recuperação de palavra-passe indistinguível entre um email
 * registado e um email desconhecido.
 *
 * O comportamento por omissão do Fortify devolve o erro `passwords.user`
 * — "não encontramos utilizador com esse endereço" — quando a conta não existe.
 * Isso transforma o formulário público de recuperação num oráculo: qualquer
 * pessoa pode testar endereços e descobrir quem tem conta no Bilhete.ao. É
 * enumeração de recursos, proibida pela secção 10 do agent.md.
 *
 * Substituindo a resposta de falha pela mesma mensagem da de sucesso, o
 * atacante deixa de aprender seja o que for. A notificação continua a ser
 * enviada apenas quando a conta existe de facto.
 */
class RespostaUniformeDeRecuperacao implements FailedPasswordResetLinkRequestResponse
{
    public function toResponse($request): Response
    {
        /** @var Request $request */
        $mensagem = trans('passwords.sent');

        if ($request->wantsJson()) {
            return response()->json(['message' => $mensagem]);
        }

        return back()->with('status', $mensagem);
    }
}
