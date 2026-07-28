<?php

declare(strict_types=1);

use function Pest\Laravel\get;

it('arranca e serve a raiz', function (): void {
    get('/')->assertOk();
});

it('expõe o health check nativo do Laravel', function (): void {
    get('/up')->assertOk();
});

it('tem chave de aplicação definida', function (): void {
    expect(config('app.key'))->not->toBeEmpty();
});
