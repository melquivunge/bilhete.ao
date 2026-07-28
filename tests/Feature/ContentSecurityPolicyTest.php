<?php

declare(strict_types=1);

use function Pest\Laravel\get;

it('emite Content-Security-Policy com nonce por pedido', function (): void {
    $response = get('/')->assertOk();

    $policy = $response->headers->get('Content-Security-Policy');

    expect($policy)->not->toBeNull()
        ->and($policy)->toContain("default-src 'self'")
        ->and($policy)->toContain("frame-ancestors 'none'")
        ->and($policy)->toContain("object-src 'none'")
        ->and($policy)->toMatch("/script-src 'self' 'nonce-[A-Za-z0-9+\/=]+'/")
        ->and($policy)->toMatch("/style-src 'self' 'nonce-[A-Za-z0-9+\/=]+'/");
});

it('nunca recorre a unsafe-inline nem unsafe-eval', function (): void {
    $policy = get('/')->assertOk()->headers->get('Content-Security-Policy');

    expect($policy)->not->toContain('unsafe-inline')
        ->and($policy)->not->toContain('unsafe-eval');
});

it('publica no HTML o mesmo nonce que envia no cabeçalho', function (): void {
    // Se estes divergirem, o browser bloqueia os <style> que o Inertia injeta em
    // runtime — barra de progresso e diálogo de erro — sem que nenhum teste de
    // servidor dê por isso. Foi exatamente o que aconteceu em C0.4.
    $response = get('/')->assertOk();

    preg_match("/'nonce-([A-Za-z0-9+\/=]+)'/", (string) $response->headers->get('Content-Security-Policy'), $noCabecalho);
    preg_match('/name="csp-nonce" content="([^"]+)"/', $response->getContent(), $noHtml);

    expect($noCabecalho[1] ?? null)->not->toBeNull()
        ->and($noHtml[1] ?? null)->not->toBeNull()
        ->and($noHtml[1])->toBe($noCabecalho[1]);
});

it('gera um nonce diferente em cada pedido', function (): void {
    $extrair = function (): ?string {
        preg_match("/'nonce-([A-Za-z0-9+\/=]+)'/", (string) get('/')->headers->get('Content-Security-Policy'), $m);

        return $m[1] ?? null;
    };

    expect($extrair())->not->toBe($extrair());
});
