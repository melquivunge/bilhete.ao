<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Verificação de prontidão: a aplicação consegue falar com PostgreSQL e Redis?
 *
 * É distinta do `/up` do Laravel, que continua a existir e responde sem tocar em
 * dependência nenhuma. A separação é deliberada:
 *
 * - `/up` responde "o processo está vivo" — é o que o nginx usa como healthcheck.
 *   Se dependesse da base de dados, uma falha do PostgreSQL derrubaria também o
 *   container web, que continuava perfeitamente capaz de servir páginas de erro.
 * - `/saude` responde "a aplicação consegue servir pedidos reais".
 *
 * A resposta é deliberadamente pobre. Não diz **qual** dependência falhou, não
 * devolve mensagens de exceção, não expõe hosts, portas nem versões. Quem faz o
 * diagnóstico é quem tem acesso aos logs; quem faz o pedido só precisa de saber
 * se pode encaminhar tráfego.
 */
class VerificacaoDeSaudeController
{
    public function __invoke(): JsonResponse
    {
        $falhas = array_keys(array_filter([
            'postgres' => ! $this->verificar('postgres', fn () => DB::select('select 1')),
            'redis' => ! $this->verificar('redis', fn () => Redis::ping()),
        ]));

        if ($falhas !== []) {
            // O detalhe fica no log, com o nome do serviço; a resposta não o leva.
            return response()->json(
                ['estado' => 'indisponivel'],
                Response::HTTP_SERVICE_UNAVAILABLE
            );
        }

        return response()->json(['estado' => 'ok']);
    }

    /**
     * Executa a sondagem apanhando **qualquer** Throwable.
     *
     * Sem isto, uma PDOException ou RedisException subiria até ao handler de
     * exceções. Com APP_DEBUG ativo, a resposta seria a página de erro completa
     * do Laravel — host, credencial parcial e stack trace — precisamente no
     * endpoint que existe para ser consultado por sistemas externos.
     *
     * @param  callable(): mixed  $sondagem
     */
    private function verificar(string $servico, callable $sondagem): bool
    {
        try {
            $sondagem();

            return true;
        } catch (Throwable $erro) {
            Log::warning('Dependência indisponível na verificação de saúde.', [
                'servico' => $servico,
                'excecao' => $erro::class,
            ]);

            return false;
        }
    }
}
