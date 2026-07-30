<?php

declare(strict_types=1);

namespace App\Logging;

use Illuminate\Log\Logger;
use Monolog\Logger as MonologLogger;

/**
 * Tap que aplica a redação ao logger, seja qual for o canal.
 *
 * Declarar `processors` na configuração de um canal **não basta** quando o canal
 * usado é o `stack`: o stack agrega os *handlers* dos sub-canais, e os
 * processadores declarados neles ficam pelo caminho. Verificado por execução —
 * uma palavra-passe passada em contexto aparecia em claro em
 * `storage/logs/laravel.log`.
 *
 * Um tap é aplicado pelo LogManager a qualquer canal resolvido, incluindo o
 * stack, o que torna a redação independente da forma como o canal é composto.
 */
class AplicarRedacao
{
    public function __invoke(Logger $logger): void
    {
        $monolog = $logger->getLogger();

        // getLogger() é declarado como LoggerInterface, mas nos canais que este
        // projeto usa é sempre um Monolog. A verificação evita um erro fatal se
        // alguém configurar um canal com outro logger.
        if ($monolog instanceof MonologLogger) {
            $monolog->pushProcessor(new RedigirDadosSensiveis);
        }
    }
}
