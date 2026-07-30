<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

/*
 * O painel do Horizon expõe payloads de jobs, mensagens de exceção e nomes de
 * filas. A partir do Marco 4, os jobs transportam identificadores de pagamento e
 * de pedido — deixa de ser diagnóstico e passa a ser dados de negócio.
 *
 * Por omissão o Horizon abre o painel a todos em `local` e só aplica o gate fora
 * dele. Isso foi desligado: o caminho testado tem de ser o mesmo que protege.
 */

it('nega o painel do Horizon a um visitante anónimo', function (): void {
    get('/horizon')->assertForbidden();
});

it('nega o painel do Horizon a um cliente autenticado', function (): void {
    actingAs(User::factory()->create())->get('/horizon')->assertForbidden();
});

it('deixa entrar quem é staff', function (): void {
    actingAs(User::factory()->staff()->create())->get('/horizon')->assertSuccessful();
});

it('aplica o gate mesmo em ambiente local', function (): void {
    // Se o gate só valesse fora de `local`, a configuração exercitada em
    // desenvolvimento não seria a que protege a produção — a mesma classe de
    // divergência que deixou o painel do Filament sem CSP (KI-017).
    app()['env'] = 'local';

    get('/horizon')->assertForbidden();
});

it('nega o gate por omissão, perante utilizador nulo', function (): void {
    expect(Gate::forUser(null)->allows('viewHorizon', [null]))->toBeFalse();
});

it('emite Content-Security-Policy no painel do Horizon', function (): void {
    // O Horizon usa o grupo `web` por nome, ao contrário do Filament, pelo que
    // herda o middleware da aplicação. Isto está aqui para o dia em que alguém
    // mude `config/horizon.php` e essa herança se perca em silêncio.
    $politica = actingAs(User::factory()->staff()->create())
        ->get('/horizon')
        ->headers->get('Content-Security-Policy');

    expect($politica)->not->toBeNull()
        ->and($politica)->toContain("default-src 'self'")
        ->and($politica)->toContain("frame-ancestors 'none'");
});

it('não contacta serviços de terceiros no painel do Horizon', function (): void {
    /*
     * O layout do Horizon carregava uma fonte de fonts.bunny.net em cada
     * carregamento, o que fazia o IP e o referer de cada membro do staff sair da
     * plataforma. Foi descoberto pelo CSP a bloquear o pedido — o mesmo padrão do
     * provedor de avatar do Filament (KI-018).
     *
     * O layout está sobreposto em resources/views/vendor/horizon/.
     */
    $html = actingAs(User::factory()->staff()->create())
        ->get('/horizon')
        ->assertSuccessful()
        ->getContent();

    expect($html)->not->toContain('bunny.net')
        ->and($html)->not->toContain('fonts.googleapis.com')
        ->and($html)->not->toContain('gravatar.com');
});

it('serve o painel do Horizon com a política dos painéis, e não a do site', function (): void {
    // Um nonce em script-src não cobre o script inline de configuração do
    // Horizon: com a política do site, o painel renderizava vazio. Isto passou
    // desapercebido a um teste que só verificava a presença do cabeçalho.
    $politica = (string) actingAs(User::factory()->staff()->create())
        ->get('/horizon')
        ->headers->get('Content-Security-Policy');

    expect($politica)->toContain("script-src 'self' 'unsafe-eval' 'unsafe-inline'")
        ->and($politica)->not->toContain('nonce-');
});
