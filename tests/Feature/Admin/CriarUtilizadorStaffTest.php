<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\artisan;

uses(RefreshDatabase::class);

it('cria uma conta com acesso ao painel', function (): void {
    artisan('bilhete:criar-staff', ['--email' => 'operador@bilhete.test', '--name' => 'Operador'])
        ->expectsQuestion('Palavra-passe', 'palavra-passe-forte-1')
        ->expectsQuestion('Confirmar palavra-passe', 'palavra-passe-forte-1')
        ->assertSuccessful();

    $utilizador = User::where('email', 'operador@bilhete.test')->firstOrFail();

    expect($utilizador->is_staff)->toBeTrue()
        ->and(Hash::check('palavra-passe-forte-1', $utilizador->password))->toBeTrue();

    actingAs($utilizador)->get('/admin')->assertSuccessful();
});

it('recusa palavra-passe que não cumpre a política', function (): void {
    artisan('bilhete:criar-staff', ['--email' => 'fraco@bilhete.test', '--name' => 'Fraco'])
        ->expectsQuestion('Palavra-passe', 'curta')
        ->expectsQuestion('Confirmar palavra-passe', 'curta')
        ->assertFailed();

    expect(User::where('email', 'fraco@bilhete.test')->exists())->toBeFalse();
});

it('recusa quando a confirmação não coincide', function (): void {
    artisan('bilhete:criar-staff', ['--email' => 'engano@bilhete.test', '--name' => 'Engano'])
        ->expectsQuestion('Palavra-passe', 'palavra-passe-forte-1')
        ->expectsQuestion('Confirmar palavra-passe', 'outra-coisa-diferente')
        ->assertFailed();

    expect(User::where('email', 'engano@bilhete.test')->exists())->toBeFalse();
});

it('exige confirmação explícita para promover uma conta de cliente existente', function (): void {
    // Promover é conceder privilégio administrativo a quem já se autentica como
    // cliente. Não pode acontecer por descuido de quem corre o comando.
    $cliente = User::factory()->create(['email' => 'cliente@bilhete.test']);

    artisan('bilhete:criar-staff', ['--email' => 'cliente@bilhete.test'])
        ->expectsConfirmation('A conta cliente@bilhete.test já existe. Conceder-lhe acesso ao painel?', 'no')
        ->assertFailed();

    expect($cliente->fresh()->is_staff)->toBeFalse();
});

it('promove uma conta existente quando confirmado', function (): void {
    $cliente = User::factory()->create(['email' => 'cliente@bilhete.test']);

    artisan('bilhete:criar-staff', ['--email' => 'cliente@bilhete.test'])
        ->expectsConfirmation('A conta cliente@bilhete.test já existe. Conceder-lhe acesso ao painel?', 'yes')
        ->assertSuccessful();

    expect($cliente->fresh()->is_staff)->toBeTrue();
});

it('não aceita a palavra-passe como argumento da linha de comandos', function (): void {
    // Um argumento ficaria no histórico da shell e na lista de processos. A
    // ausência desta opção é deliberada, e este teste impede que alguém a
    // acrescente por conveniência.
    $definicao = app(Kernel::class)
        ->all()['bilhete:criar-staff']
        ->getDefinition();

    expect($definicao->hasOption('password'))->toBeFalse()
        ->and($definicao->hasArgument('password'))->toBeFalse();
});
