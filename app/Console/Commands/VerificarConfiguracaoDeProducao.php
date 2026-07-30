<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Verifica, antes de uma implantação, a configuração que não se pode confirmar
 * de dentro da aplicação em execução.
 *
 * A aplicação já recusa arrancar com `APP_DEBUG=true` em produção. Mas há um
 * caso que nenhuma verificação em runtime apanha: **produção a correr com
 * `APP_ENV=local`**. Nessa situação a aplicação julga-se em desenvolvimento,
 * relaxa o Content-Security-Policy e serve o cookie de sessão sem a flag Secure
 * — e nada dentro dela sabe que está errada, porque a única fonte de verdade
 * sobre "isto é produção" é a própria variável que está errada.
 *
 * Daí este comando existir fora do arranque: é executado deliberadamente por
 * quem implanta, que sabe o que está a implantar.
 */
class VerificarConfiguracaoDeProducao extends Command
{
    protected $signature = 'bilhete:verificar-producao';

    protected $description = 'Verifica se a configuração é segura para um ambiente exposto';

    public function handle(): int
    {
        $problemas = [];

        if (config('app.debug') === true) {
            $problemas[] = 'APP_DEBUG está ligado: uma exceção não apanhada exporia host, credenciais parciais e stack trace.';
        }

        if (in_array(config('app.env'), ['local', 'testing'], true)) {
            $problemas[] = 'APP_ENV é "'.config('app.env').'": o CSP fica relaxado e o cookie de sessão sai sem a flag Secure.';
        }

        if (config('session.secure') !== true) {
            $problemas[] = 'SESSION_SECURE_COOKIE não está ativo: o cookie de sessão pode viajar em claro.';
        }

        if (blank(config('app.key'))) {
            $problemas[] = 'APP_KEY não está definida: sessões e dados cifrados ficam inutilizáveis ou inseguros.';
        }

        if (config('mail.default') !== 'array' && app()->environment('production') === false) {
            $problemas[] = 'MAIL_MAILER não é "array" fora de produção: há risco de envio real de mensagens, proibido pela secção 16 do agent.md.';
        }

        if ($problemas === []) {
            $this->components->info('Configuração adequada a um ambiente exposto.');

            return self::SUCCESS;
        }

        foreach ($problemas as $problema) {
            $this->components->error($problema);
        }

        return self::FAILURE;
    }
}
