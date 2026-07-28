# Bilhete.ao

Plataforma angolana de venda de bilhetes de cinema e, futuramente, de eventos.
O produto arranca em Luanda.

Experiência principal:

```text
filme → cinema → data → sessão → assentos → checkout → pagamento → bilhete com QR Code
```

---

## Estado atual

**Marco 0 — Fundação · ciclo C0.1 concluído.**

Não existe ainda aplicação Laravel neste repositório. O que existe é a inspeção
do ambiente, as decisões de arquitetura tomadas e o plano de implementação do
ambiente inicial.

O que já está feito e o que se segue está em [`docs/progress.md`](docs/progress.md)
e em [`docs/plan-marco-0.md`](docs/plan-marco-0.md).

Enquanto o ciclo C0.2 não for executado, **não há comandos de arranque** — as
instruções de instalação serão escritas quando existir código que as suporte, e
depois de terem sido executadas.

---

## Stack

Decidida e verificada no ciclo C0.1:

| Camada | Tecnologia |
| --- | --- |
| Linguagem | PHP 8.4 (container) |
| Framework | Laravel 13 |
| Base de dados | PostgreSQL 17 — fonte de verdade da disponibilidade de assentos, e das sessões |
| Cache, filas, locks auxiliares | Redis 8 — nunca fonte de verdade |
| Node (container) | 24, o LTS ativo |
| Filas e supervisão | Laravel Queues, Scheduler, Horizon |
| Frontend | Inertia.js, Vue 3, TypeScript, Tailwind CSS, Vite |
| Painel administrativo | Filament 5 |
| Autenticação | Laravel Fortify (clientes) e Filament (staff) |
| Testes | Pest |
| Infraestrutura de desenvolvimento | Docker Compose com Nginx |
| CI | GitHub Actions |

Aplicação monolítica modular. Sem microserviços nesta fase.

---

## Documentação

| Documento | Conteúdo |
| --- | --- |
| [`agent.md`](agent.md) | Especificação do produto e do processo de engenharia |
| [`docs/plan-marco-0.md`](docs/plan-marco-0.md) | Plano do Marco 0, dividido em ciclos C0.1–C0.10 |
| [`docs/progress.md`](docs/progress.md) | Registo de cada ciclo executado |
| [`docs/backlog.md`](docs/backlog.md) | Trabalho identificado e ainda não feito |
| [`docs/known-issues.md`](docs/known-issues.md) | Problemas e limitações conhecidas |
| [`docs/decisions/`](docs/decisions/) | Architecture Decision Records |

`docs/architecture.md`, `docs/data-model.md`, `docs/booking-flow.md`,
`docs/payment-flow.md`, `docs/security.md` e `docs/product-scope.md` serão
escritos nos ciclos que implementam o que descrevem. Não são criados vazios.

---

## Âmbito da primeira versão

Cinemas, filmes e sessões são **fictícios**. Não são usadas marcas, logótipos,
cartazes, textos ou programação de empresas reais sem autorização.

A integração real com o MULTICAIXA permanece desativada enquanto não existirem
documentação oficial, contrato e credenciais.
