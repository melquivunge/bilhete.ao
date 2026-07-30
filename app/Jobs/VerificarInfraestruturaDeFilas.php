<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

/**
 * Job de verificação da infraestrutura de filas.
 *
 * Existe para provar, por execução, que o percurso completo funciona: despacho
 * para Redis, consumo por um worker num container separado, e efeito observável.
 * Não tem lógica de negócio e não deve ganhar nenhuma.
 *
 * O primeiro job de negócio real é a expiração de reservas, no Marco 2, e é
 * crítico: liberta assentos. Descobrir só aí que as filas não estavam a ser
 * consumidas seria descobrir com assentos presos.
 */
class VerificarInfraestruturaDeFilas implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly string $marca) {}

    public function handle(): void
    {
        Cache::put($this->chave($this->marca), now()->toIso8601String(), now()->addMinutes(10));
    }

    public static function chave(string $marca): string
    {
        return "verificacao-de-filas:{$marca}";
    }
}
