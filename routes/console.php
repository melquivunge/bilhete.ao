<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

/*
 * Agendamento.
 *
 * Nenhuma tarefa de negócio está agendada, de propósito. A primeira será a
 * expiração de reservas, no Marco 2, e essa liberta assentos — não vai para aqui
 * sem os testes de concorrência que o plano exige.
 *
 * `horizon:snapshot` é infraestrutura, não negócio: alimenta os gráficos de
 * carga das filas. Sem ele, o painel do Horizon não tem histórico e o Marco 7
 * ficaria sem base para dimensionar workers antes do piloto.
 */
Schedule::command('horizon:snapshot')->everyFiveMinutes();
