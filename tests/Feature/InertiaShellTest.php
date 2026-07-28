<?php

declare(strict_types=1);

use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\get;

it('serve a raiz como resposta Inertia com o componente esperado', function (): void {
    get('/')->assertOk()->assertInertia(
        fn (AssertableInertia $page) => $page
            ->component('Inicio')
            ->has('ambiente')
    );
});

it('partilha o locale configurado com todas as páginas', function (): void {
    get('/')->assertOk()->assertInertia(
        fn (AssertableInertia $page) => $page->where('locale', config('app.locale'))
    );
});

it('não partilha dados de utilizador por omissão numa página pública', function (): void {
    get('/')->assertOk()->assertInertia(
        fn (AssertableInertia $page) => $page->missing('auth')->missing('user')
    );
});
