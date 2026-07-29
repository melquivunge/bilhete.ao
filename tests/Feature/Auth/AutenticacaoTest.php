<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertAuthenticatedAs;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    RateLimiter::clear('');
});

it('mostra a página de entrada', function (): void {
    get('/login')->assertOk()->assertInertia(
        fn (AssertableInertia $page) => $page->component('Auth/Entrar')
    );
});

it('autentica com credenciais corretas', function (): void {
    $utilizador = User::factory()->create(['password' => 'palavra-passe-forte-1']);

    post('/login', [
        'email' => $utilizador->email,
        'password' => 'palavra-passe-forte-1',
    ])->assertRedirect('/');

    assertAuthenticatedAs($utilizador);
});

it('recusa palavra-passe errada', function (): void {
    $utilizador = User::factory()->create(['password' => 'palavra-passe-forte-1']);

    post('/login', [
        'email' => $utilizador->email,
        'password' => 'palavra-passe-errada',
    ])->assertSessionHasErrors('email');

    assertGuest();
});

it('recusa email inexistente', function (): void {
    post('/login', [
        'email' => 'ninguem@bilhete.test',
        'password' => 'palavra-passe-forte-1',
    ])->assertSessionHasErrors('email');

    assertGuest();
});

it('não revela se a conta existe: a mensagem é a mesma nos dois casos', function (): void {
    // Enumeração de recursos (agent.md, secção 10). Se o erro distinguisse
    // "email não existe" de "palavra-passe errada", o formulário de login seria
    // um oráculo para descobrir quem tem conta na plataforma.
    $utilizador = User::factory()->create(['password' => 'palavra-passe-forte-1']);

    $mensagem = __('auth.failed');

    post('/login', [
        'email' => $utilizador->email,
        'password' => 'palavra-passe-errada',
    ])->assertInvalid(['email' => $mensagem]);

    post('/login', [
        'email' => 'ninguem@bilhete.test',
        'password' => 'palavra-passe-errada',
    ])->assertInvalid(['email' => $mensagem]);
});

it('bloqueia por rate limiting após tentativas falhadas seguidas', function (): void {
    // Força bruta e credential stuffing (agent.md, secção 10).
    $utilizador = User::factory()->create(['password' => 'palavra-passe-forte-1']);

    foreach (range(1, 5) as $ignorado) {
        post('/login', ['email' => $utilizador->email, 'password' => 'errada']);
    }

    // A credencial agora está CORRETA e mesmo assim não entra. É isto que prova
    // que o bloqueio existe: se o rate limiting não estivesse a funcionar, este
    // pedido autenticava. O throttle responde 429, não um erro de validação.
    post('/login', [
        'email' => $utilizador->email,
        'password' => 'palavra-passe-forte-1',
    ])->assertStatus(429);

    assertGuest();
});

it('limita por combinação de email e IP, não só por IP', function (): void {
    // Limitar apenas por IP castigaria utilizadores atrás do mesmo NAT. Depois
    // de esgotar as tentativas de uma conta, outra conta a partir do mesmo IP
    // tem de continuar a conseguir entrar.
    $vitima = User::factory()->create(['password' => 'palavra-passe-forte-1']);
    $outro = User::factory()->create(['password' => 'palavra-passe-forte-2']);

    foreach (range(1, 6) as $ignorado) {
        post('/login', ['email' => $vitima->email, 'password' => 'errada']);
    }

    post('/login', [
        'email' => $outro->email,
        'password' => 'palavra-passe-forte-2',
    ])->assertRedirect('/');

    assertAuthenticatedAs($outro);
});

it('termina a sessão', function (): void {
    $utilizador = User::factory()->create();

    actingAs($utilizador)->post('/logout')->assertRedirect();

    assertGuest();
});

it('regenera o identificador de sessão ao autenticar', function (): void {
    // Fixação de sessão: sem regeneração, um identificador plantado antes do
    // login continuaria válido depois dele.
    $utilizador = User::factory()->create(['password' => 'palavra-passe-forte-1']);

    $antes = session()->getId();

    post('/login', [
        'email' => $utilizador->email,
        'password' => 'palavra-passe-forte-1',
    ]);

    expect(session()->getId())->not->toBe($antes);
});
