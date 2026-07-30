<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

uses(RefreshDatabase::class);

/*
 * Fecha KI-004 e KI-005, abertos desde o ciclo C0.1 como riscos por confirmar.
 */

it('não tem colisão de rotas entre o Fortify e o Filament', function (): void {
    // KI-005. O Fortify registra na raiz, o Filament sob /admin. Um caminho
    // servido por duas rotas seria resolvido pela ordem de registo, o que é
    // frágil e silencioso.
    $caminhos = collect(Route::getRoutes()->getRoutes())
        ->map(fn ($rota) => implode('|', $rota->methods()).' '.$rota->uri())
        ->countBy()
        ->filter(fn (int $vezes) => $vezes > 1);

    expect($caminhos->all())->toBe([]);
});

it('mantém entradas separadas para clientes e para staff', function (): void {
    get('/login')->assertOk()->assertInertia(
        fn (AssertableInertia $page) => $page->component('Auth/Entrar')
    );

    // A entrada do painel não é uma página Inertia: é servida pelo Filament.
    get('/admin/login')->assertOk()->assertDontSee('Auth/Entrar', false);
});

it('autenticar-se como cliente não dá acesso ao painel', function (): void {
    // O guard é partilhado (ADR-006): entrar pelo site público cria uma sessão
    // válida para o guard `web`. O que impede o acesso ao painel é a autorização,
    // e é isso que este teste prova.
    $cliente = User::factory()->create(['password' => 'palavra-passe-forte-1']);

    post('/login', [
        'email' => $cliente->email,
        'password' => 'palavra-passe-forte-1',
    ])->assertRedirect('/');

    get('/admin')->assertForbidden();
});

it('autenticar-se como staff dá acesso ao painel e ao site público', function (): void {
    $staff = User::factory()->staff()->create(['password' => 'palavra-passe-forte-1']);

    post('/login', [
        'email' => $staff->email,
        'password' => 'palavra-passe-forte-1',
    ])->assertRedirect('/');

    get('/admin')->assertSuccessful();
    get('/')->assertOk();
});

it('o painel não serve os assets do Vite do site público', function (): void {
    // KI-004. Filament traz Livewire e o seu próprio pipeline de assets; o site
    // público usa Vite. Se o painel carregasse o bundle do Inertia, teríamos duas
    // aplicações a arrancar na mesma página.
    $staff = User::factory()->staff()->create();

    $html = actingAs($staff)->get('/admin')->assertSuccessful()->getContent();

    expect($html)->not->toContain('resources/js/app.ts')
        ->and($html)->not->toContain('data-page=');
});

it('o site público não carrega os assets do Filament', function (): void {
    $html = get('/')->assertOk()->getContent();

    expect($html)->not->toContain('filament/filament')
        ->and($html)->toContain('data-page=');
});
