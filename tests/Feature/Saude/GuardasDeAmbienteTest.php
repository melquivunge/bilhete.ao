<?php

declare(strict_types=1);

use function Pest\Laravel\artisan;

it('recusa a configuração de desenvolvimento como adequada a produção', function (): void {
    // Em `testing`, APP_DEBUG e APP_ENV são de desenvolvimento. O comando tem de
    // dizer que não, senão não serve para nada.
    artisan('bilhete:verificar-producao')->assertFailed();
});

it('aponta cada problema concreto, e não apenas que há problemas', function (): void {
    config(['app.debug' => true, 'app.env' => 'local', 'session.secure' => false]);

    artisan('bilhete:verificar-producao')
        ->expectsOutputToContain('APP_DEBUG')
        ->expectsOutputToContain('APP_ENV')
        ->expectsOutputToContain('SESSION_SECURE_COOKIE')
        ->assertFailed();
});

it('aceita uma configuração de ambiente exposto', function (): void {
    config([
        'app.debug' => false,
        'app.env' => 'production',
        'session.secure' => true,
        'app.key' => 'base64:'.base64_encode(random_bytes(32)),
    ]);

    artisan('bilhete:verificar-producao')->assertSuccessful();
});

it('deteta APP_ENV=local, que nenhuma verificação em runtime consegue apanhar', function (): void {
    /*
     * Este é o caso que justifica o comando existir fora do arranque: com
     * `APP_ENV=local` em produção, a aplicação julga-se em desenvolvimento e
     * relaxa CSP e cookie de sessão. Nada dentro dela sabe que está errada,
     * porque a única fonte de verdade sobre o ambiente é a variável errada.
     */
    config(['app.debug' => false, 'app.env' => 'local', 'session.secure' => true]);

    artisan('bilhete:verificar-producao')
        ->expectsOutputToContain('APP_ENV')
        ->assertFailed();
});
