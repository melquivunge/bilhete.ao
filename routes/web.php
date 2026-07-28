<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
 * Rota mínima de verificação do stack Inertia.
 *
 * NÃO é a home descrita na secção 11 do agent.md — seletor de localização, hero,
 * filmes em cartaz, cinemas — que pertence ao Marco 1 e depende do catálogo.
 */
Route::get('/', fn () => Inertia::render('Inicio', [
    'ambiente' => app()->environment(),
]))->name('inicio');
