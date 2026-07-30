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
    /**
     * @param  'site'|'painel'  $perfil
     */
    public function handle(Request $request, Closure $next, string $perfil = 'site'): Response
    {
        Vite::useCspNonce();

        $response = $next($request);

        /*
         * O Horizon registra as suas rotas com o grupo `web` por nome, pelo que
         * herda este middleware sem o declarar. Não pode receber a política do
         * site: o painel do Horizon executa um script inline com a sua
         * configuração, e um nonce em script-src não o cobre.
         *
         * A deteção é feita aqui, e não acrescentando outro middleware ao
         * config/horizon.php, porque dois cabeçalhos CSP na mesma resposta são
         * aplicados como interseção — o painel ficaria bloqueado pela política
         * mais restritiva das duas.
         */
        if ($perfil === 'site' && $this->ehPainelAdministrativo($request)) {
            $perfil = 'painel';
        }

        $response->headers->set(
            'Content-Security-Policy',
            $perfil === 'painel'
                ? $this->politicaDoPainel()
                : $this->policy(Vite::cspNonce())
        );

        return $response;
    }

    private function ehPainelAdministrativo(Request $request): bool
    {
        $prefixo = trim((string) config('horizon.path', 'horizon'), '/');

        return $prefixo !== '' && $request->is($prefixo, "{$prefixo}/*");
    }

    /**
     * Política do painel administrativo (Filament, Livewire, Alpine).
     *
     * Precisa de `unsafe-eval` e `unsafe-inline`, e a razão é técnica, não
     * comodidade: o Alpine avalia as expressões dos atributos `x-*` com
     * `new Function()`, que o CSP classifica como eval. Sem `unsafe-eval` o
     * painel deixa simplesmente de funcionar — não é uma degradação subtil.
     *
     * Isto é mais fraco do que a política do site público, e a diferença é
     * deliberada e documentada. O que ainda se ganha, e não é pouco, é que
     * `default-src 'self'` continua a impedir carregar scripts, imagens ou
     * pedidos de origens externas: um XSS refletido no painel não consegue
     * exfiltrar para um domínio do atacante.
     *
     * Nota: o painel não usa o nonce. Um nonce em `script-src` faria o browser
     * ignorar o `unsafe-inline` (foi o que KI-012 ensinou), e aqui o
     * `unsafe-inline` é necessário.
     *
     * Alternativa rejeitada: a build "CSP-safe" do Alpine, que o Filament não
     * expõe como opção de configuração. Reavaliar no Marco 7 (B-045).
     */
    private function politicaDoPainel(): string
    {
        return implode('; ', [
            "default-src 'self'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
            "object-src 'none'",
            "img-src 'self' data: blob:",
            "font-src 'self' data:",
            "script-src 'self' 'unsafe-eval' 'unsafe-inline'",
            "style-src 'self' 'unsafe-inline'",
            "connect-src 'self'",
        ]);
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
