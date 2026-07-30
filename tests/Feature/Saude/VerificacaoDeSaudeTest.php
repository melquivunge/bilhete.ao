<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

use function Pest\Laravel\get;

it('responde 200 quando PostgreSQL e Redis respondem', function (): void {
    get('/saude')->assertOk()->assertExactJson(['estado' => 'ok']);
});

it('responde 503 quando a base de dados não responde', function (): void {
    DB::shouldReceive('select')->andThrow(new PDOException('SQLSTATE[08006] could not connect to server: bilhete:senha@postgres:5432'));

    get('/saude')->assertStatus(503);
});

it('responde 503 quando o Redis não responde', function (): void {
    Redis::shouldReceive('ping')->andThrow(new RuntimeException('Connection refused [tcp://redis:6379]'));

    get('/saude')->assertStatus(503);
});

it('não revela qual dependência falhou nem detalhes da exceção', function (): void {
    /*
     * A resposta é deliberadamente pobre. Se dissesse "postgres em baixo", um
     * atacante mapeava a infraestrutura interrogando um endpoint público. Pior:
     * uma mensagem de exceção do PDO transporta host, porta e por vezes parte
     * da credencial — foi por isso que a mensagem de teste acima os inclui.
     */
    DB::shouldReceive('select')->andThrow(new PDOException('SQLSTATE[08006] could not connect to server: bilhete:senha-secreta@postgres:5432'));

    $corpo = get('/saude')->assertStatus(503)->getContent();

    expect($corpo)->not->toContain('postgres')
        ->and($corpo)->not->toContain('senha-secreta')
        ->and($corpo)->not->toContain('5432')
        ->and($corpo)->not->toContain('SQLSTATE')
        ->and($corpo)->not->toContain('PDOException');
});

it('não deixa a exceção subir até ao handler, mesmo com depuração ligada', function (): void {
    // Com APP_DEBUG ligado, uma exceção não apanhada devolveria a página de erro
    // completa do Laravel — exatamente no endpoint feito para ser consultado por
    // sistemas externos. Este teste prova que é apanhada antes disso.
    config(['app.debug' => true]);

    DB::shouldReceive('select')->andThrow(new PDOException('detalhe interno que não pode sair'));

    $resposta = get('/saude')->assertStatus(503);

    expect($resposta->getContent())->not->toContain('detalhe interno')
        ->and($resposta->headers->get('content-type'))->toContain('application/json');
});

it('mantém o /up do Laravel independente das dependências', function (): void {
    /*
     * `/up` é o healthcheck do nginx e responde sem tocar em PostgreSQL nem
     * Redis. Se dependesse deles, uma falha da base de dados derrubaria também o
     * container web — que continua capaz de servir páginas de erro.
     */
    DB::shouldReceive('select')->andThrow(new PDOException('base de dados em baixo'));

    get('/up')->assertOk();
});
