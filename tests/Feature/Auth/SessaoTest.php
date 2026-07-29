<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

uses(RefreshDatabase::class);

it('marca o cookie de sessão como HttpOnly e SameSite', function (): void {
    // Verifica o cabeçalho Set-Cookie real, não a configuração. Confiar na
    // configuração deixaria passar o caso de ela não estar a ser aplicada.
    $cookies = get('/')->headers->getCookies();

    $sessao = collect($cookies)->firstWhere('getName', config('session.cookie'))
        ?? collect($cookies)->first(fn ($c) => $c->getName() === config('session.cookie'));

    expect($sessao)->not->toBeNull()
        ->and($sessao->isHttpOnly())->toBeTrue()
        ->and($sessao->getSameSite())->toBe('lax');
});

it('não marca o cookie como Secure em ambiente de teste', function (): void {
    // Em `testing` não há TLS, logo Secure tem de estar desligado.
    //
    // O caso que importa de verdade — o valor por omissão ser seguro em produção
    // quando a variável é esquecida — **não** é coberto por este teste: exigiria
    // reavaliar a configuração sob outro APP_ENV. Está na expressão de
    // config/session.php e será travado pela verificação de arranque do C0.8
    // (B-044). Fica dito, em vez de fingido por uma asserção tautológica.
    expect(config('session.secure'))->toBeFalse();
});

it('invalida a sessão anterior ao terminar sessão', function (): void {
    $utilizador = User::factory()->create();

    actingAs($utilizador)->get('/')->assertOk();

    $idAntes = session()->getId();

    post('/logout')->assertRedirect();

    // A sessão foi regenerada, não apenas esvaziada: o identificador antigo
    // deixa de corresponder à sessão ativa.
    expect(session()->getId())->not->toBe($idAntes);

    get('/')->assertOk()->assertInertia(
        fn (AssertableInertia $page) => $page->where('auth.user', null)
    );
});

it('exige autenticação para terminar sessão', function (): void {
    post('/logout')->assertRedirect();
});
