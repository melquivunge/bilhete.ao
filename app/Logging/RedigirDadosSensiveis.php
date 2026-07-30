<?php

declare(strict_types=1);

namespace App\Logging;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

/**
 * Substitui valores sensíveis no contexto dos registos antes de serem escritos.
 *
 * A secção 10 do `agent.md` proíbe registar palavras-passe, tokens completos,
 * dados de pagamento, chaves privadas e cabeçalhos de autenticação. Proibir por
 * disciplina não chega: basta um `Log::info('pedido', $request->all())` num
 * ciclo futuro para a palavra-passe de alguém ficar em disco.
 *
 * Esta é a última linha antes do ficheiro. Age sobre o contexto e sobre o
 * `extra`, em qualquer profundidade.
 *
 * O que **não** faz: não analisa a mensagem em texto livre. Escrever um segredo
 * dentro da própria mensagem continua a ser possível, e continua a ser erro de
 * quem escreve. Isto reduz a superfície, não a elimina.
 */
class RedigirDadosSensiveis implements ProcessorInterface
{
    /**
     * Comparadas em minúsculas, por correspondência parcial: `api_token`,
     * `authorization`, `card_number` e afins são apanhados.
     *
     * @var list<string>
     */
    private const CHAVES_SENSIVEIS = [
        'password',
        'palavra_passe',
        'palavra-passe',
        'secret',
        'segredo',
        'token',
        'authorization',
        'api_key',
        'apikey',
        'private_key',
        'credit_card',
        'card_number',
        'cvv',
        'iban',
        'remember_token',
        'session',
        'cookie',
    ];

    private const SUBSTITUTO = '[redigido]';

    public function __invoke(LogRecord $record): LogRecord
    {
        return $record
            ->with(context: $this->redigir($record->context))
            ->with(extra: $this->redigir($record->extra));
    }

    /**
     * @param  array<array-key, mixed>  $dados
     * @return array<array-key, mixed>
     */
    private function redigir(array $dados): array
    {
        foreach ($dados as $chave => $valor) {
            if (is_array($valor)) {
                $dados[$chave] = $this->redigir($valor);

                continue;
            }

            if (is_string($chave) && $this->ehSensivel($chave)) {
                $dados[$chave] = self::SUBSTITUTO;
            }
        }

        return $dados;
    }

    private function ehSensivel(string $chave): bool
    {
        $normalizada = mb_strtolower($chave);

        foreach (self::CHAVES_SENSIVEIS as $sensivel) {
            if (str_contains($normalizada, $sensivel)) {
                return true;
            }
        }

        return false;
    }
}
