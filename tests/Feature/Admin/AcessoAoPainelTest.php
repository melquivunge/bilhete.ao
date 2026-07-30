<?php

declare(strict_types=1);

use App\Actions\Identity\PromoverUtilizadorAStaff;
use App\Models\User;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

uses(RefreshDatabase::class);

/*
 * Este ficheiro é a condição de fecho do ciclo C0.6.
 *
 * Clientes e staff partilham o modelo User e o guard `web` (ADR-006). Isso
 * concentra deliberadamente todo o risco num único ponto: canAccessPanel(). Se
 * esse ponto falhar, um cliente qualquer entra na área administrativa. É por isso
 * que estes testes não são um extra do ciclo — são o ciclo.
 */

it('nega o painel a um cliente autenticado', function (): void {
    $cliente = User::factory()->create();

    actingAs($cliente)->get('/admin')->assertForbidden();
});

it('nega também as páginas internas do painel a um cliente autenticado', function (string $caminho): void {
    // Negar apenas a raiz do painel não bastaria: bastaria adivinhar um caminho
    // interno para contornar.
    $cliente = User::factory()->create();

    actingAs($cliente)->get($caminho)->assertForbidden();
})->with(['/admin', '/admin/']);

it('redireciona um visitante anónimo para a entrada do painel', function (): void {
    get('/admin')->assertRedirect('/admin/login');
});

it('deixa entrar quem é staff', function (): void {
    $staff = User::factory()->staff()->create();

    actingAs($staff)->get('/admin')->assertSuccessful();
});

it('nega por omissão: um utilizador recém-criado não é staff', function (): void {
    // A omissão tem de ser negar. Um valor ausente, nulo ou inesperado nunca pode
    // conceder acesso.
    $utilizador = User::factory()->create();

    expect($utilizador->is_staff)->toBeFalse();

    actingAs($utilizador)->get('/admin')->assertForbidden();
});

it('não permite promover-se a staff pelo formulário público de registo', function (): void {
    /*
     * B-042. Este é o teste que impede a fronteira do painel de se tornar
     * decorativa: se `is_staff` fosse atribuível em massa, qualquer pessoa se
     * promovia no registo e o canAccessPanel() deixava de significar nada.
     */
    post('/register', [
        'name' => 'Atacante',
        'email' => 'atacante@bilhete.test',
        'password' => 'palavra-passe-forte-1',
        'password_confirmation' => 'palavra-passe-forte-1',
        'is_staff' => true,
    ]);

    $utilizador = User::where('email', 'atacante@bilhete.test')->firstOrFail();

    expect($utilizador->is_staff)->toBeFalse();

    actingAs($utilizador)->get('/admin')->assertForbidden();
});

it('rebenta em vez de descartar em silêncio uma atribuição em massa de is_staff', function (): void {
    // Antes de `preventSilentlyDiscardingAttributes`, isto era descartado sem
    // aviso: protegia contra o atacante e escondia o erro honesto. Agora falha
    // alto, e o teste passa a exigir esse comportamento.
    $utilizador = User::factory()->create();

    expect(fn () => $utilizador->fill(['is_staff' => true]))
        ->toThrow(MassAssignmentException::class);

    expect($utilizador->fresh()->is_staff)->toBeFalse();
});

it('promove e revoga apenas pela Action dedicada', function (): void {
    $utilizador = User::factory()->create();
    $accao = new PromoverUtilizadorAStaff;

    $accao->executar($utilizador);
    expect($utilizador->fresh()->is_staff)->toBeTrue();
    actingAs($utilizador->fresh())->get('/admin')->assertSuccessful();

    $accao->revogar($utilizador);
    expect($utilizador->fresh()->is_staff)->toBeFalse();
});
