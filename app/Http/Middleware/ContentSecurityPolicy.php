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

        // connect-src inclui ws: em desenvolvimento por causa do HMR do Vite.
        $connect = app()->environment('local')
            ? "'self' ws: wss:"
            : "'self'";

        return implode('; ', [
            "default-src 'self'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
            "object-src 'none'",
            "img-src 'self' data:",
            "font-src 'self' data:",
            "script-src 'self'{$nonceSource}",
            "style-src 'self'{$nonceSource}",
            "connect-src {$connect}",
        ]);
    }
}
