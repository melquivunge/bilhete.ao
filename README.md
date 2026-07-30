# Bilhete.ao

Plataforma angolana de venda de bilhetes de cinema e, futuramente, de eventos.
O produto arranca em Luanda.

Experiência principal:

```text
filme → cinema → data → sessão → assentos → checkout → pagamento → bilhete com QR Code
```

---

## Estado atual

**Marco 0 — Fundação · concluído.**

Existe uma aplicação Laravel 13 em Docker, ligada a PostgreSQL e Redis, a servir
páginas Inertia com Vue 3 e TypeScript, com autenticação de clientes, painel
administrativo Filament, filas com Horizon, verificação de saúde e integração
contínua verde no GitHub.

**Não existe ainda catálogo**: nem filmes, nem cinemas, nem sessões, nem lugares,
nem bilhetes. Isso é o Marco 1. Ver [`docs/product-scope.md`](docs/product-scope.md).

| Documento | Conteúdo |
| --- | --- |
| [`docs/architecture.md`](docs/architecture.md) | Como o sistema está montado |
| [`docs/security.md`](docs/security.md) | Controlos em vigor e riscos aceites |
| [`docs/product-scope.md`](docs/product-scope.md) | O que o produto é e por onde vai |
| [`docs/progress.md`](docs/progress.md) | Registo de cada ciclo executado |
| [`docs/known-issues.md`](docs/known-issues.md) | Problemas conhecidos, com causa e estado |

### Arranque

Requer apenas Docker. Não é preciso PHP, Composer nem Node instalados.

**1.** Criar o ficheiro de ambiente:

```bash
cp .env.example .env
```

**2.** Definir `DB_PASSWORD` e `REDIS_PASSWORD` no `.env`, com valores à escolha.
Não têm valor por omissão: nenhuma credencial funcional é versionada, e o Compose
recusa arrancar sem elas em vez de cair para uma senha pública.

**3.** Construir a imagem e instalar dependências **antes** de levantar os
serviços — o nginx precisa de uma aplicação para servir:

```bash
docker compose build
docker compose run --rm --no-deps app composer install
docker compose run --rm --no-deps app php artisan key:generate
docker compose run --rm --no-deps node npm ci
docker compose run --rm --no-deps node npm run build
```

**4.** Levantar tudo e aplicar as migrations:

```bash
docker compose up -d
docker compose exec app php artisan migrate --force
```

**5.** Confirmar:

```bash
docker compose ps                                  # seis serviços healthy
curl http://127.0.0.1:8080/saude                   # {"estado":"ok"}
```

Aplicação em `http://127.0.0.1:8080`.

### Conta de staff

```bash
docker compose exec app php artisan bilhete:criar-staff
```

A palavra-passe é pedida interativamente e nunca aceite como argumento — um
argumento ficaria no histórico da shell. Depois: painel em `/admin`, filas em
`/horizon`.

### Verificação

```bash
docker compose exec app composer check           # validate + pint + phpstan + pest
docker compose run --rm --no-deps node npm run lint
docker compose run --rm --no-deps node npm run type-check
docker compose run --rm --no-deps node npm run format:check
docker compose run --rm --no-deps node npm run build
```

`php artisan test` **não** é usado: imprime `passed` e sai com código 1 (KI-008).
O comando é `composer test`.

Antes de qualquer implantação:

```bash
docker compose exec app php artisan bilhete:verificar-producao
```

O Node corre no container (Node 24 LTS), não no host. `node_modules` vive num
volume próprio e por isso não aparece na árvore de ficheiros.

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
