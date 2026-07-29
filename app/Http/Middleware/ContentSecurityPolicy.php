<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * Define o Content-Security-Policy com um nonce por pedido.
 *
 * O CSP não pode viver no Nginx como cabeçalho estático: tanto a barra de
 * progresso do Inertia como o Vite em modo de desenvolvimento injetam elementos
 * <style> em runtime. Sem nonce, um `default-src 'self'` bloqueia-os — verificado
 * em browser, era o erro de consola que fechou o ciclo C0.4. A alternativa,
 * 'unsafe-inline', desativaria na prática a proteção contra XSS que o CSP existe
 * para dar.
 *
 * Os restantes cabeçalhos, que não dependem do pedido, continuam no Nginx.
 */
class ContentSecurityPolicy
{
    public function handle(Request $request, Closure $next): Response
    {
        Vite::useCspNonce();

        $response = $next($request);

        $response->headers->set(
            'Content-Security-Policy',
            $this->policy(Vite::cspNonce())
        );

        return $response;
    }

    private function policy(?string $nonce): string
    {
        $nonceSource = $nonce === null ? '' : " 'nonce-{$nonce}'";

        $emDesenvolvimento = app()->environment('local');

        /*
         * Concessões que existem apenas em `local`, e por motivos verificados
         * em browser, não por precaução:
         *
         * - o servidor do Vite serve os assets de outra porta e mantém o HMR
         *   por WebSocket, daí a origem extra e o ws:;
         * - em modo de desenvolvimento o Vite injeta o CSS por JavaScript, em
         *   runtime, e não tem forma de conhecer o nonce que o Blade gerou.
         *
         * `unsafe-inline` nunca sai de `local`. Os testes correm em `testing` e
         * asseguram que a política de produção não o contém — é o teste
         * "nunca recorre a unsafe-inline nem unsafe-eval" que trava isso.
         */
        $origemVite = $emDesenvolvimento ? ' http://127.0.0.1:5173' : '';
        $connect = $emDesenvolvimento ? "'self' http://127.0.0.1:5173 ws: wss:" : "'self'";

        /*
         * Em `local`, o style-src usa `unsafe-inline` e **não** usa nonce. Não é
         * escolha estética: pela especificação do CSP, a presença de um nonce
         * faz o browser ignorar o `unsafe-inline`, e os dois juntos deixariam o
         * Vite bloqueado tal como antes. O browser diz isto explicitamente:
         * "'unsafe-inline' is ignored if either a hash or nonce value is present".
         *
         * Em produção é o inverso: nonce e nada de inline.
         */
        $estilo = $emDesenvolvimento
            ? "style-src 'self'{$origemVite} 'unsafe-inline'"
            : "style-src 'self'{$nonceSource}";

        return implode('; ', [
            "default-src 'self'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
            "object-src 'none'",
            "img-src 'self' data:",
            "font-src 'self' data:{$origemVite}",
            "script-src 'self'{$nonceSource}{$origemVite}",
            $estilo,
            "connect-src {$connect}",
        ]);
    }
}
