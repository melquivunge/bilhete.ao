<?php

declare(strict_types=1);

use App\Jobs\VerificarInfraestruturaDeFilas;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;

it('despacha o job para a fila em vez de o correr em linha', function (): void {
    Queue::fake();

    VerificarInfraestruturaDeFilas::dispatch('marca-de-teste');

    Queue::assertPushed(VerificarInfraestruturaDeFilas::class);
});

it('produz efeito observável quando processado', function (): void {
    (new VerificarInfraestruturaDeFilas('marca-de-teste'))->handle();

    expect(Cache::get(VerificarInfraestruturaDeFilas::chave('marca-de-teste')))->not->toBeNull();
});

it('usa Redis para filas e cache, e PostgreSQL para sessões', function (): void {
    /*
     * A secção 3 do agent.md é explícita: Redis serve cache, filas, rate limiting
     * e locks auxiliares — nunca é fonte de verdade. As sessões ficam em
     * PostgreSQL, e este teste impede que alguém as mova para Redis por
     * conveniência num ciclo futuro.
     *
     * Em ambiente de teste os drivers são forçados no phpunit.xml; o que se
     * verifica aqui é a configuração declarada do projeto.
     */
    // Verifica o .env.example, que é a configuração declarada e versionada do
    // projeto. Ler `env()` aqui não serviria: o phpunit.xml força `sync` e
    // `array` no ambiente de teste, pelo que a asserção mediria o teste em vez
    // do projeto — foi o erro da primeira versão deste teste.
    $exemplo = (string) file_get_contents(base_path('.env.example'));

    expect($exemplo)->toContain('QUEUE_CONNECTION=redis')
        ->and($exemplo)->toContain('CACHE_STORE=redis')
        ->and($exemplo)->toContain('SESSION_DRIVER=database')
        ->and($exemplo)->not->toContain('SESSION_DRIVER=redis');
});

it('tem o agendamento de snapshot do Horizon registado', function (): void {
    // Sem snapshots, o painel do Horizon não tem histórico de carga, e o Marco 7
    // ficaria sem base para dimensionar workers antes do piloto.
    $comandos = collect(app(Schedule::class)->events())
        ->map(fn ($evento) => $evento->command ?? '')
        ->filter(fn (string $comando) => str_contains($comando, 'horizon:snapshot'));

    expect($comandos)->not->toBeEmpty();
});
