<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\followingRedirects;

uses(RefreshDatabase::class);

/*
 * Estes testes existem por causa de um defeito real do ciclo C0.5: o locale da
 * aplicação é `pt`, o Laravel não traz ficheiros de idioma, e as mensagens
 * chegavam ao utilizador como `auth.failed` — a chave, não o texto.
 *
 * Nenhum dos testes de autenticação apanhou isso, porque comparavam
 * `__('auth.failed')` com a resposta: os dois lados devolviam a mesma string
 * literal e a asserção passava sem provar nada. Só a verificação em browser o
 * revelou.
 *
 * A guarda contra a regressão asserta as mensagens no payload Inertia — que é
 * exatamente o que o browser recebe — e não a estrutura interna da sessão.
 */

/**
 * @param  array<string, mixed>  $props
 * @return list<string>
 */
function mensagensDeErro(array $props): array
{
    /** @var list<string> $mensagens */
    $mensagens = array_values(array_filter(
        Arr::flatten(Arr::get($props, 'errors', [])),
        'is_string'
    ));

    return $mensagens;
}

it('traduz o erro de credenciais inválidas', function (): void {
    expect(__('auth.failed'))->not->toBe('auth.failed')
        ->and(__('auth.failed'))->toContain('credenciais');
});

it('traduz a mensagem de recuperação de palavra-passe', function (): void {
    expect(__('passwords.sent'))->not->toBe('passwords.sent')
        ->and(__('passwords.sent'))->toContain('email');
});

it('traduz as regras de validação usadas nos formulários de autenticação', function (string $chave): void {
    expect(__($chave))->not->toBe($chave);
})->with([
    'validation.required',
    'validation.email',
    'validation.confirmed',
    'validation.string',
    'validation.unique',
]);

it('entrega ao browser mensagens traduzidas quando o login é recusado', function (): void {
    $utilizador = User::factory()->create(['password' => 'palavra-passe-forte-1']);

    followingRedirects()
        ->post('/login', ['email' => $utilizador->email, 'password' => 'errada'])
        ->assertOk()
        ->assertInertia(function (AssertableInertia $page): void {
            $mensagens = mensagensDeErro($page->toArray()['props']);

            expect($mensagens)->not->toBeEmpty();

            foreach ($mensagens as $mensagem) {
                expect($mensagem)->not->toStartWith('auth.')
                    ->and($mensagem)->not->toStartWith('validation.')
                    ->and($mensagem)->not->toStartWith('passwords.');
            }
        });
});

it('entrega ao browser mensagens traduzidas quando o registo é inválido', function (): void {
    followingRedirects()
        ->post('/register', ['name' => '', 'email' => 'nao-e-email', 'password' => 'x'])
        ->assertOk()
        ->assertInertia(function (AssertableInertia $page): void {
            $mensagens = mensagensDeErro($page->toArray()['props']);

            expect($mensagens)->not->toBeEmpty();

            foreach ($mensagens as $mensagem) {
                expect($mensagem)->not->toStartWith('validation.');
            }
        });
});
