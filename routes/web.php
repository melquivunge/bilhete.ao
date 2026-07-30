<?php

declare(strict_types=1);

use App\Http\Controllers\VerificacaoDeSaudeController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
 * Rota mínima de verificação do stack Inertia.
 *
 * NÃO é a home descrita na secção 11 do agent.md — seletor de localização, hero,
 * filmes em cartaz, cinemas — que pertence ao Marco 1 e depende do catálogo.
 *
 * Não devolve o nome do ambiente. Devolvia, e isso chegava a qualquer visitante
 * anónimo no payload data-page, visível em "ver código-fonte", confirmando a um
 * atacante se estava diante de produção ou de um ambiente de ensaio.
 */
Route::get('/', fn () => Inertia::render('Inicio'))->name('inicio');

/*
 * Prontidão. Distinta do `/up` do Laravel, que continua a responder sem tocar em
 * dependências e é o que o nginx usa como healthcheck — ver o controlador.
 */
Route::get('/saude', VerificacaoDeSaudeController::class)->name('saude');
