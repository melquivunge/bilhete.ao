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

pest()->extend(TestCase::class)->in('Feature');
