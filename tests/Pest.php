<?php

declare(strict_types=1);

use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Os testes de Feature correm contra a aplicação completa. A trait
| RefreshDatabase só será aplicada a partir de C0.3, quando existir PostgreSQL
| nos containers — ver docs/decisions/ADR-007-docker-ambiente-canonico.md.
|
*/

/*
 * `withoutVite()` em todos os testes de Feature.
 *
 * Sem isto, qualquer teste que renderize uma página Inertia exige
 * `public/build/manifest.json`, ou seja, um `npm run build` prévio. Localmente
 * passava sempre — eu tinha o build feito — e falhou na CI, onde o job de
 * backend não constrói o frontend. Vinte e três testes.
 *
 * A suíte de backend não deve depender de artefactos de build: quem verifica que
 * os assets são gerados e referenciados é o job de frontend e a verificação em
 * browser.
 */
pest()->extend(TestCase::class)->in('Feature');
