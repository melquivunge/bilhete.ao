<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

it('partilha apenas id e nome de quem está autenticado', function (): void {
    $utilizador = User::factory()->create();

    actingAs($utilizador)->get('/')->assertOk()->assertInertia(
        fn (AssertableInertia $page) => $page
            ->where('auth.user.id', $utilizador->id)
            ->where('auth.user.name', $utilizador->name)
            ->missing('auth.user.email')
            ->missing('auth.user.password')
            ->missing('auth.user.remember_token')
    );
});

it('nunca deixa o email do utilizador entrar no payload de uma página pública', function (): void {
    // O email é o identificador de login e um dado pessoal. Esta asserção é
    // sobre o payload inteiro, não sobre um campo: apanha o caso de alguém
    // acrescentar o utilizador completo a share() num ciclo futuro.
    $utilizador = User::factory()->create(['email' => 'cliente-de-teste@bilhete.test']);

    $resposta = actingAs($utilizador)->get('/')->assertOk();

    expect($resposta->getContent())->not->toContain('cliente-de-teste@bilhete.test');
});

it('não expõe identidade a quem não está autenticado', function (): void {
    get('/')->assertOk()->assertInertia(
        fn (AssertableInertia $page) => $page->where('auth.user', null)
    );
});
