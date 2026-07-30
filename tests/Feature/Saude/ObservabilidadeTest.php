<?php

declare(strict_types=1);

use App\Logging\AplicarRedacao;
use App\Logging\RedigirDadosSensiveis;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Logger;
use Illuminate\Support\Facades\Log;
use Monolog\Handler\TestHandler;
use Monolog\Level;
use Monolog\Logger as MonologLogger;
use Monolog\LogRecord;

use function Pest\Laravel\post;

uses(RefreshDatabase::class);

/**
 * Captura os registos reais que a aplicação escreve, em vez de espiar a facade.
 * Verifica o que sai do logger, e não o que foi pedido ao logger.
 */
function capturarRegistos(): TestHandler
{
    $handler = new TestHandler;
    $logger = new Logger(new MonologLogger('teste', [$handler]));

    // Aplica o mesmo tap que a configuração aplica aos canais reais. Sem isto, o
    // logger capturado não seria o logger da aplicação, e os testes mediriam um
    // objeto que não existe em produção.
    (new AplicarRedacao)($logger);

    Log::swap($logger);

    return $handler;
}

/**
 * @param  array<string, mixed>  $contexto
 */
function registo(array $contexto): LogRecord
{
    return new LogRecord(new DateTimeImmutable, 'teste', Level::Info, 'mensagem', $contexto);
}

it('redige chaves sensíveis no contexto dos registos', function (string $chave): void {
    $resultado = (new RedigirDadosSensiveis)(registo([$chave => 'valor-secreto']));

    expect($resultado->context[$chave])->toBe('[redigido]');
})->with([
    'password',
    'password_confirmation',
    'api_token',
    'remember_token',
    'Authorization',
    'stripe_secret',
    'card_number',
    'cvv',
    'iban',
    'cookie',
]);

it('redige em qualquer profundidade', function (): void {
    $resultado = (new RedigirDadosSensiveis)(registo([
        'pedido' => ['cliente' => ['password' => 'segredo', 'nome' => 'Nzola']],
    ]));

    expect($resultado->context['pedido']['cliente']['password'])->toBe('[redigido]')
        ->and($resultado->context['pedido']['cliente']['nome'])->toBe('Nzola');
});

it('não toca em campos inofensivos', function (): void {
    $resultado = (new RedigirDadosSensiveis)(registo(['utilizador' => 12, 'ip' => '127.0.0.1']));

    expect($resultado->context['utilizador'])->toBe(12)
        ->and($resultado->context['ip'])->toBe('127.0.0.1');
});

it('redige de facto no que o logger escreve, e não apenas na configuração', function (): void {
    /*
     * A primeira versão deste teste verificava que a classe constava de
     * `config('logging.channels.X.processors')`. Passava — e a redação **não
     * acontecia**: com o canal `stack`, que é o predefinido, os processadores
     * declarados nos sub-canais ficam pelo caminho, porque o stack agrega apenas
     * os handlers. A palavra-passe aparecia em claro em storage/logs.
     *
     * Configuração presente não é comportamento verificado. Este teste passa a
     * medir o que sai do logger.
     */
    $handler = capturarRegistos();

    Log::info('teste.de.redacao', ['password' => 'nao-deve-aparecer', 'utilizador' => 7]);

    $registo = $handler->getRecords()[0];

    expect($registo->context['password'])->toBe('[redigido]')
        ->and($registo->context['utilizador'])->toBe(7);
});

it('aplica a redação por tap, para valer em qualquer canal incluindo o stack', function (string $canal): void {
    expect(config("logging.channels.{$canal}.tap"))
        ->toContain(AplicarRedacao::class);
})->with(['stack', 'single', 'daily', 'stderr']);

it('regista as tentativas de entrada falhadas sem a palavra-passe', function (): void {
    $utilizador = User::factory()->create(['password' => 'palavra-passe-forte-1']);
    $handler = capturarRegistos();

    post('/login', ['email' => $utilizador->email, 'password' => 'errada-de-proposito']);

    $registo = collect($handler->getRecords())
        ->first(fn (LogRecord $r): bool => $r->message === 'autenticacao.falha');

    expect($registo)->not->toBeNull()
        ->and($registo->context['identificador_tentado'])->toBe($utilizador->email)
        ->and($registo->context)->not->toContain('errada-de-proposito')
        ->and($registo->context['ip'])->not->toBeEmpty();
});

it('regista a entrada bem sucedida', function (): void {
    $utilizador = User::factory()->create(['password' => 'palavra-passe-forte-1']);
    $handler = capturarRegistos();

    post('/login', ['email' => $utilizador->email, 'password' => 'palavra-passe-forte-1']);

    $registo = collect($handler->getRecords())
        ->first(fn (LogRecord $r): bool => $r->message === 'autenticacao.entrada');

    expect($registo)->not->toBeNull()
        ->and($registo->context['utilizador'])->toBe($utilizador->id);
});
