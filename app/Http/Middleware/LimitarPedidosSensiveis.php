<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Limita os pedidos aos endpoints públicos de autenticação que o Fortify deixa
 * sem travão.
 *
 * O Fortify só permite configurar um limitador para o login (e para 2FA e
 * passkeys, que estão desligados). `POST /register`, `POST /forgot-password` e
 * `POST /reset-password` chegam com `['web', 'guest:web']` e nada mais —
 * verificado com `php artisan route:list`.
 *
 * Sem isto:
 *
 * - o registo permite criação automatizada de contas em massa, e cada tentativa
 *   custa um hash bcrypt ao servidor — exaustão de CPU barata para o atacante;
 * - a recuperação permite testar endereços à velocidade da rede, o que dá ao
 *   atacante amostras ilimitadas para explorar a diferença de tempo de resposta
 *   entre um email existente e um inexistente;
 * - com um transporte de email real (Marco 3), a recuperação torna-se um vetor
 *   de inundação de caixas de correio de terceiros.
 *
 * Dois cestos por endpoint: um por IP, que trava volume, e um mais apertado por
 * email, que trava a insistência sobre uma conta concreta. Nenhum dos dois
 * sozinho é suficiente — só IP castiga utilizadores atrás do mesmo NAT, só email
 * deixa o atacante variar o endereço à vontade.
 */
class LimitarPedidosSensiveis
{
    /**
     * Caminho => nome do cesto.
     */
    private const ENDPOINTS = [
        'register' => 'registo',
        'forgot-password' => 'recuperacao',
        'reset-password' => 'redefinicao',
    ];

    private const POR_IP_POR_MINUTO = 10;

    private const POR_EMAIL_POR_MINUTO = 5;

    public function handle(Request $request, Closure $next): Response
    {
        $cesto = self::ENDPOINTS[$request->path()] ?? null;

        if ($cesto === null || ! $request->isMethod('POST')) {
            return $next($request);
        }

        foreach ($this->chaves($request, $cesto) as $chave => $maximo) {
            if (RateLimiter::tooManyAttempts($chave, $maximo)) {
                return $this->respostaDeBloqueio(RateLimiter::availableIn($chave));
            }
        }

        foreach ($this->chaves($request, $cesto) as $chave => $maximo) {
            RateLimiter::hit($chave);
        }

        return $next($request);
    }

    /**
     * @return array<string, int>
     */
    private function chaves(Request $request, string $cesto): array
    {
        $chaves = ["{$cesto}|ip|".$request->ip() => self::POR_IP_POR_MINUTO];

        $email = $request->input('email');

        if (is_string($email) && $email !== '') {
            $normalizado = Str::transliterate(Str::lower($email));
            $chaves["{$cesto}|email|{$normalizado}"] = self::POR_EMAIL_POR_MINUTO;
        }

        return $chaves;
    }

    private function respostaDeBloqueio(int $segundos): Response
    {
        return response(
            __('auth.throttle', ['seconds' => $segundos]),
            Response::HTTP_TOO_MANY_REQUESTS,
            ['Retry-After' => $segundos]
        );
    }
}
