# ADR-001 — Monólito Laravel modular sobre PHP 8.4

**Estado.** Aceite
**Data.** 2026-07-28
**Ciclo.** C0.1

---

## Contexto

O Bilhete.ao é um produto transacional: vende bilhetes numerados, com reservas
temporárias, concorrência real sobre assentos e pagamentos. A secção 2 do
`agent.md` fixa a stack e proíbe microserviços nesta fase.

Restava escolher versões concretas. As versões disponíveis foram verificadas no
Packagist, não assumidas:

- `laravel/framework` v13.23.0, que exige `php: ^8.3`;
- `filament/support` v5.7.3, que aceita `illuminate/contracts: ^11.28|^12.0|^13.0`
  e depende de `livewire/livewire: ^4.1`;
- `inertiajs/inertia-laravel` v3.2.0, que aceita `laravel/framework: ^11.35|^12.0|^13.0`.

A máquina de desenvolvimento corre PHP 8.5.6, Composer 2.9.2 e Node 26.0.0.

## Decisão

Uma única aplicação Laravel 13, modular por domínio, a correr sobre PHP 8.4 no
container.

Os domínios da secção 4 do `agent.md` — Identity, Cinema, Catalog, Screening,
Booking, Checkout, Payment, Ticketing, Promotion, Reporting, Administration — são
fronteiras dentro do mesmo processo, não serviços.

As pastas de domínio são criadas apenas quando passam a ter conteúdo real. Não são
criadas onze pastas vazias para satisfazer o diagrama.

PHP 8.4 e não 8.5 no container: o 8.4 está confortavelmente dentro do intervalo
suportado pelo Laravel 13 e pelo Filament 5, e dá margem sobre pacotes de
terceiros cujo suporte a 8.5 ainda é irregular. A divergência face ao host está
registada em KI-002.

Pelo mesmo critério, o container usa **Node 24** e não o Node 26 do host: em
2026-07-28 o registo de releases do `nodejs.org` marca a 24.18.0 como
`lts: "Krypton"` e a 26.5.0 como `lts: false`. O Node 26 está no canal Current.
Escolher a versão estável mais recente, e não a mais recente, é a mesma regra
aplicada duas vezes.

## Alternativas consideradas

**Laravel 12 sobre PHP 8.3.** Ecossistema de terceiros mais rodado. Rejeitada:
obrigaria a um upgrade maior pouco depois do arranque, num produto que ainda não
tem utilizadores e onde o custo de estar na versão atual é mínimo.

**Laravel 13 sobre PHP 8.5.** Alinharia container e host, eliminando KI-002.
Rejeitada: o suporte a 8.5 em extensões e pacotes de terceiros ainda é irregular,
e um problema de compatibilidade no Marco 0 custa mais do que a divergência de
versões.

**Microserviços por domínio.** Rejeitada por proibição explícita do `agent.md` e
porque a operação com reservas concorrentes é mais simples e mais segura numa só
base de dados transacional.

## Consequências

Aceitamos:

- ficar dependentes da cadência de releases do Laravel 13 e do Filament 5;
- ter de vigiar a divergência PHP 8.4/8.5 entre container e host (KI-002);
- que a modularidade seja uma convenção sustentada por disciplina e revisão, não
  imposta por fronteiras de rede.

Ganhamos:

- transações que atravessam domínios sem coordenação distribuída, o que é
  determinante para o bloqueio pessimista de assentos do Marco 2;
- uma única unidade de deploy, teste e observabilidade;
- Filament, Horizon e Inertia dentro do mesmo processo, sem integração adicional.

## Revisão

Reavaliar se e quando um domínio precisar de escalar ou ser implantado de forma
independente. Até lá, extrair um serviço é considerado abstração prematura.
