<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\assertAuthenticated;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

uses(RefreshDatabase::class);

it('mostra a página de registo', function (): void {
    get('/register')->assertOk()->assertInertia(
        fn (AssertableInertia $page) => $page->component('Auth/Registar')
    );
});

it('cria a conta e autentica com dados válidos', function (): void {
    post('/register', [
        'name' => 'Nzola Kiala',
        'email' => 'nzola@bilhete.test',
        'password' => 'palavra-passe-forte-1',
        'password_confirmation' => 'palavra-passe-forte-1',
    ])->assertRedirect('/');

    assertAuthenticated();
    assertDatabaseCount('users', 1);

    $utilizador = User::firstOrFail();

    expect($utilizador->email)->toBe('nzola@bilhete.test')
        ->and($utilizador->name)->toBe('Nzola Kiala');
});

it('guarda a palavra-passe com hash e nunca em claro', function (): void {
    post('/register', [
        'name' => 'Nzola Kiala',
        'email' => 'nzola@bilhete.test',
        'password' => 'palavra-passe-forte-1',
        'password_confirmation' => 'palavra-passe-forte-1',
    ]);

    $utilizador = User::firstOrFail();

    expect($utilizador->password)->not->toBe('palavra-passe-forte-1')
        ->and(Hash::check('palavra-passe-forte-1', $utilizador->password))->toBeTrue();
});

it('recusa registo com dados inválidos', function (array $dados, string $campo): void {
    post('/register', $dados)->assertSessionHasErrors($campo);

    assertGuest();
    assertDatabaseCount('users', 0);
})->with([
    'sem nome' => [['email' => 'a@b.test', 'password' => 'palavra-passe-forte-1', 'password_confirmation' => 'palavra-passe-forte-1'], 'name'],
    'email inválido' => [['name' => 'A', 'email' => 'nao-e-email', 'password' => 'palavra-passe-forte-1', 'password_confirmation' => 'palavra-passe-forte-1'], 'email'],
    'palavra-passe curta' => [['name' => 'A', 'email' => 'a@b.test', 'password' => 'curta', 'password_confirmation' => 'curta'], 'password'],
    'confirmação diferente' => [['name' => 'A', 'email' => 'a@b.test', 'password' => 'palavra-passe-forte-1', 'password_confirmation' => 'outra-coisa-qualquer'], 'password'],
]);

it('recusa email já registado', function (): void {
    User::factory()->create(['email' => 'nzola@bilhete.test']);

    post('/register', [
        'name' => 'Outro',
        'email' => 'nzola@bilhete.test',
        'password' => 'palavra-passe-forte-1',
        'password_confirmation' => 'palavra-passe-forte-1',
    ])->assertSessionHasErrors('email');

    assertDatabaseCount('users', 1);
});

it('ignora campos não atribuíveis enviados no formulário de registo', function (): void {
    // Mass assignment (agent.md, secção 10). O registo é o primeiro ponto do
    // sistema em que entrada pública popula um modelo. Se um campo de privilégio
    // fosse atribuível aqui, o controlo de acesso ao painel do ciclo C0.6 seria
    // inútil: qualquer pessoa se promovia no formulário de criação de conta.
    post('/register', [
        'name' => 'Nzola Kiala',
        'email' => 'nzola@bilhete.test',
        'password' => 'palavra-passe-forte-1',
        'password_confirmation' => 'palavra-passe-forte-1',
        'id' => 999999,
        'email_verified_at' => now()->toDateTimeString(),
        'remember_token' => 'token-escolhido-pelo-atacante',
    ]);

    $utilizador = User::firstOrFail();

    expect($utilizador->id)->not->toBe(999999)
        ->and($utilizador->email_verified_at)->toBeNull()
        ->and($utilizador->remember_token)->not->toBe('token-escolhido-pelo-atacante');

    assertDatabaseMissing('users', ['id' => 999999]);
});
