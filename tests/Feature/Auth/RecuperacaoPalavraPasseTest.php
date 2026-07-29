<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\assertGuest;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

uses(RefreshDatabase::class);

it('mostra a página de pedido de recuperação', function (): void {
    get('/forgot-password')->assertOk()->assertInertia(
        fn (AssertableInertia $page) => $page->component('Auth/PedirRecuperacao')
    );
});

it('envia a notificação de recuperação a uma conta existente', function (): void {
    Notification::fake();

    $utilizador = User::factory()->create();

    post('/forgot-password', ['email' => $utilizador->email]);

    Notification::assertSentTo($utilizador, ResetPassword::class);
});

it('responde da mesma forma para email inexistente, sem revelar se há conta', function (): void {
    // Enumeração de contas (agent.md, secção 10). Uma resposta diferente aqui
    // transformaria este formulário numa lista de clientes da plataforma.
    Notification::fake();

    $utilizador = User::factory()->create();

    $mensagem = __('passwords.sent');

    post('/forgot-password', ['email' => $utilizador->email])
        ->assertSessionHas('status', $mensagem)
        ->assertSessionHasNoErrors();

    post('/forgot-password', ['email' => 'ninguem@bilhete.test'])
        ->assertSessionHas('status', $mensagem)
        ->assertSessionHasNoErrors();

    // Resposta idêntica, mas notificação só para quem existe de facto.
    Notification::assertCount(1);
});

it('redefine a palavra-passe com um token válido', function (): void {
    Notification::fake();

    $utilizador = User::factory()->create();

    post('/forgot-password', ['email' => $utilizador->email]);

    $token = null;

    Notification::assertSentTo($utilizador, ResetPassword::class, function (ResetPassword $notificacao) use (&$token): bool {
        $token = $notificacao->token;

        return true;
    });

    post('/reset-password', [
        'token' => $token,
        'email' => $utilizador->email,
        'password' => 'nova-palavra-passe-1',
        'password_confirmation' => 'nova-palavra-passe-1',
    ])->assertSessionHasNoErrors();

    expect(Hash::check('nova-palavra-passe-1', $utilizador->fresh()->password))->toBeTrue();

    // Redefinir não autentica: o utilizador tem de entrar com a nova credencial.
    assertGuest();
});

it('recusa um token inválido', function (): void {
    $utilizador = User::factory()->create(['password' => 'palavra-passe-forte-1']);

    post('/reset-password', [
        'token' => 'token-inventado',
        'email' => $utilizador->email,
        'password' => 'nova-palavra-passe-1',
        'password_confirmation' => 'nova-palavra-passe-1',
    ])->assertSessionHasErrors('email');

    expect(Hash::check('palavra-passe-forte-1', $utilizador->fresh()->password))->toBeTrue();
});

it('não envia mensagens reais: o transporte de email é o array', function (): void {
    // A secção 16 do agent.md proíbe envio real de mensagens. Esta asserção
    // trava a configuração, não o comportamento de um teste isolado.
    expect(config('mail.default'))->toBe('array');
});
