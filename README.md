# Bilhete.ao

Plataforma angolana de venda de bilhetes de cinema e, futuramente, de eventos.
O produto arranca em Luanda.

Experiência principal:

```text
filme → cinema → data → sessão → assentos → checkout → pagamento → bilhete com QR Code
```

---

## Estado atual

**Marco 0 — Fundação · ciclos C0.1 a C0.5 concluídos.**

Existe uma aplicação Laravel 13 a correr em Docker, ligada a PostgreSQL e Redis,
a servir páginas Inertia com Vue 3 e TypeScript, com registo, entrada, saída e
recuperação de palavra-passe para clientes. Ainda **não** existem painel
administrativo, filas nem CI: são os ciclos C0.6 a C0.9.

O que já está feito e o que se segue está em [`docs/progress.md`](docs/progress.md)
e em [`docs/plan-marco-0.md`](docs/plan-marco-0.md).

### Arranque

```bash
cp .env.example .env
```

Depois **define `DB_PASSWORD` e `REDIS_PASSWORD` no `.env`** com valores à tua
escolha. Não há valores por omissão: nenhuma credencial funcional é versionada, e
o Compose recusa arrancar sem eles, em vez de cair para uma senha pública.

```bash
docker compose up -d --build
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
```

Aplicação em `http://127.0.0.1:8080`. Verificação:

```bash
docker compose exec app composer check              # validate + pint + phpstan + pest
docker compose run --rm node npm run type-check     # e ainda: lint, format:check, build
```

O Node corre no container (Node 24 LTS), não no host. `node_modules` vive num
volume próprio e por isso não aparece na tua árvore de ficheiros.

O ambiente canónico é o Docker: resultados obtidos no host não valem como
verificação do comportamento em PostgreSQL ou Redis. Ver
[`ADR-007`](docs/decisions/ADR-007-docker-ambiente-canonico.md).

`php artisan test` não é usado — sai com código 1 mesmo com os testes a passar
(KI-008). O comando é `composer test`.

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
