<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        /*
         * A suíte de backend não depende de assets construídos.
         *
         * Sem isto, qualquer teste que renderize uma página Inertia exige
         * `public/build/manifest.json`, ou seja, um `npm run build` prévio.
         * Localmente passava sempre — eu tinha o build feito — e falharam 23
         * testes na CI, onde o job de backend não constrói o frontend.
         *
         * Quem verifica que os assets são gerados e referenciados é o job de
         * frontend e a verificação em browser.
         */
        $this->withoutVite();
    }
}
