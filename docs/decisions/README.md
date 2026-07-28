# Architecture Decision Records

Uma decisão por ficheiro. Um ADR regista uma decisão **já tomada**, com o contexto
que a justificou e as consequências que aceitámos.

## Regras

- Um ADR é escrito no ciclo em que a decisão é efetivamente tomada, não antes.
- ADRs não são apagados. Quando uma decisão é substituída, o ADR antigo passa a
  `Substituído por ADR-XXX` e mantém-se no histórico.
- Números não são reutilizados.

## Índice

| ADR | Título | Estado | Ciclo |
| --- | --- | --- | --- |
| [ADR-001](ADR-001-monolito-laravel-modular.md) | Monólito Laravel modular sobre PHP 8.4 | Aceite | C0.1 |
| ADR-002 | PostgreSQL como fonte de verdade das reservas | Reservado | Marco 1 |
| ADR-003 | Bloqueio pessimista na reserva de assentos | Reservado | Marco 2 |
| ADR-004 | Abstração do gateway de pagamento | Reservado | Marco 4 |
| [ADR-005](ADR-005-inertia-e-vue-no-frontend.md) | Inertia e Vue no frontend, com Node em container | Aceite | C0.4 |
| [ADR-006](ADR-006-autenticacao-fortify-e-filament.md) | Autenticação separada: Fortify para clientes, Filament para staff | Aceite | C0.1 |
| [ADR-007](ADR-007-docker-ambiente-canonico.md) | Docker como ambiente canónico de desenvolvimento e CI | Aceite | C0.1 |

Os números 002 a 005 estão reservados para as decisões listadas como exemplo na
secção 13 do `agent.md`. Ficam por escrever até que os ciclos correspondentes as
tornem decisões reais, em vez de intenções copiadas da especificação.

A escolha do Laravel, do Inertia e do Vue enquanto stack está coberta por ADR-001.
A forma concreta de estruturar o frontend está em ADR-005, escrito em C0.4, que é
onde essa decisão passou a ser executada.
