<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\post;

uses(RefreshDatabase::class);

/*
 * O plano do ciclo C0.5 exigia limitador em login, registo E recuperação. Só o
 * login o tinha: o Fortify entrega `POST /register`, `POST /forgot-password` e
 * `POST /reset-password` com `['web', 'guest:web']` e mais nada — confirmado com
 * `php artisan route:list`. Uma revisão independente apanhou a discrepância
 * entre o plano e o código.
 *
 * Estes testes são a prova de que deixou de ser assim.
 */

it('bloqueia o registo em massa a partir do mesmo IP', function (): void {
    // Dez pedidos por minuto por IP; o décimo primeiro é recusado.
    foreach (range(1, 10) as $i) {
        post('/register', [
            'name' => "Cliente {$i}",
            'email' => "cliente-{$i}@bilhete.test",
            'password' => 'palavra-passe-forte-1',
            'password_confirmation' => 'palavra-passe-forte-1',
        ]);
    }

    post('/register', [
        'name' => 'Um a mais',
        'email' => 'um-a-mais@bilhete.test',
        'password' => 'palavra-passe-forte-1',
        'password_confirmation' => 'palavra-passe-forte-1',
    ])->assertStatus(429);

    // O pedido recusado não criou conta.
    assertDatabaseMissing('users', ['email' => 'um-a-mais@bilhete.test']);

    // Só uma conta existe, e não dez: o registo autentica, e o `guest:web` do
    // Fortify passa a redirecionar as tentativas seguintes desta mesma sessão.
    // O limitador conta os pedidos de qualquer forma — é por isso que o décimo
    // primeiro é travado — e é isso que interessa: um atacante real não guarda
    // cookies entre tentativas.
    assertDatabaseCount('users', 1);
});

it('bloqueia a insistência sobre o mesmo email na recuperação', function (): void {
    Notification::fake();

    $utilizador = User::factory()->create();

    // Cinco por minuto por email; o sexto é recusado.
    foreach (range(1, 5) as $ignorado) {
        post('/forgot-password', ['email' => $utilizador->email]);
    }

    post('/forgot-password', ['email' => $utilizador->email])->assertStatus(429);
});

it('limita a recuperação por IP, mesmo variando o email', function (): void {
    // É este o cesto que impede usar a recuperação para enumerar endereços: sem
    // ele, o atacante trocava de email a cada pedido e nunca era travado.
    Notification::fake();

    foreach (range(1, 10) as $i) {
        post('/forgot-password', ['email' => "desconhecido-{$i}@bilhete.test"]);
    }

    post('/forgot-password', ['email' => 'mais-um@bilhete.test'])->assertStatus(429);
});

it('limita a redefinição de palavra-passe, travando adivinhação de tokens', function (): void {
    $utilizador = User::factory()->create();

    foreach (range(1, 5) as $i) {
        post('/reset-password', [
            'token' => "token-inventado-{$i}",
            'email' => $utilizador->email,
            'password' => 'nova-palavra-passe-1',
            'password_confirmation' => 'nova-palavra-passe-1',
        ]);
    }

    post('/reset-password', [
        'token' => 'token-inventado-6',
        'email' => $utilizador->email,
        'password' => 'nova-palavra-passe-1',
        'password_confirmation' => 'nova-palavra-passe-1',
    ])->assertStatus(429);
});

it('não limita a navegação normal nem as páginas de autenticação', function (string $caminho): void {
    // O limitador aplica-se a POST em três caminhos. Se afetasse GET, bastaria
    // recarregar a página de entrada onze vezes para ficar sem acesso.
    foreach (range(1, 15) as $ignorado) {
        \Pest\Laravel\get($caminho)->assertOk();
    }
})->with(['/', '/login', '/register', '/forgot-password']);
