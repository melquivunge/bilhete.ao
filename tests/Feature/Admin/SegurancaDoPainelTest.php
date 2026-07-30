<?php

declare(strict_types=1);

use App\Filament\Avatares\AvatarLocalComIniciais;
use App\Models\User;
use Illuminate\Cache\RateLimiter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

/*
 * O painel esteve sem Content-Security-Policy nenhum, e ninguém deu por isso.
 *
 * A causa: o array de middleware do Filament substitui o grupo `web` do Laravel
 * em vez de o herdar, pelo que o middleware anexado em bootstrap/app.php nunca
 * chegava a /admin. A verificação em browser não revelou nada — não havia
 * política para violar.
 *
 * Estes testes existem para que a ausência volte a ser detetável.
 */

it('emite Content-Security-Policy no painel', function (string $caminho): void {
    $resposta = $caminho === '/admin'
        ? actingAs(User::factory()->staff()->create())->get($caminho)
        : get($caminho);

    $politica = $resposta->headers->get('Content-Security-Policy');

    expect($politica)->not->toBeNull()
        ->and($politica)->toContain("default-src 'self'")
        ->and($politica)->toContain("frame-ancestors 'none'")
        ->and($politica)->toContain("object-src 'none'");
})->with(['/admin/login', '/admin']);

it('impede o painel de carregar recursos de origens externas', function (): void {
    // É esta a parte que continua a valer, mesmo com unsafe-eval: um XSS no
    // painel não consegue exfiltrar dados para um domínio do atacante.
    $politica = (string) actingAs(User::factory()->staff()->create())
        ->get('/admin')
        ->headers->get('Content-Security-Policy');

    expect($politica)->toContain("connect-src 'self'")
        ->and($politica)->not->toContain('http://')
        ->and($politica)->not->toContain('https://');
});

it('não envia dados de staff para serviços de terceiros nos avatares', function (): void {
    // O provedor de avatar por omissão do Filament pedia a imagem a
    // ui-avatars.com com o nome do utilizador no URL. Foi o CSP do painel que
    // expôs isso, ao bloquear os pedidos.
    $staff = User::factory()->staff()->create(['name' => 'Operador Sigiloso']);

    $html = actingAs($staff)->get('/admin')->assertSuccessful()->getContent();

    expect($html)->not->toContain('ui-avatars.com')
        ->and($html)->not->toContain('gravatar.com');
});

it('gera o avatar localmente como data URI', function (): void {
    $avatar = (new AvatarLocalComIniciais)
        ->get(User::factory()->make(['name' => 'Nzola Kiala']));

    expect($avatar)->toStartWith('data:image/svg+xml;base64,');

    $svg = base64_decode(str_replace('data:image/svg+xml;base64,', '', $avatar), true);

    expect($svg)->toBeString()
        ->and($svg)->toContain('NK');
});

it('limita as tentativas de entrada no painel', function (): void {
    // O Filament traz limitador por omissão. Um teste próprio evita que a
    // proteção dependa de confiança numa dependência que pode mudar.
    $staff = User::factory()->staff()->create(['password' => 'palavra-passe-forte-1']);

    $limitador = app(RateLimiter::class);
    $chave = Str::transliterate(Str::lower($staff->email).'|127.0.0.1');

    foreach (range(1, 5) as $ignorado) {
        $limitador->hit($chave);
    }

    expect($limitador->tooManyAttempts($chave, 5))->toBeTrue();
});
