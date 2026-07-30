# Arquitetura

Estado em 2026-07-30, fim do Marco 0. Descreve o que **existe**, não o que está
planeado. O que ainda não foi construído está marcado como tal.

---

## Forma geral

Uma aplicação Laravel 13 única, modular por domínio, sobre PostgreSQL e Redis.
Sem microserviços — ver [ADR-001](decisions/ADR-001-monolito-laravel-modular.md).

```text
                    ┌──────────┐
   browser ────────▶│  nginx   │  :8080 (só 127.0.0.1)
                    └────┬─────┘
                         │ fastcgi, apenas index.php
                    ┌────▼─────┐        ┌──────────┐
                    │   app    │───────▶│ postgres │  fonte de verdade
                    │ php-fpm  │        └──────────┘
                    │   8.4.23 │        ┌──────────┐
                    └────┬─────┘───────▶│  redis   │  cache, filas, locks
                         │              └────▲─────┘
                    ┌────▼─────┐             │
                    │ horizon  │─────────────┘  consome as filas
                    └──────────┘
                    ┌──────────┐
                    │   node   │  :5173  Vite, só em desenvolvimento
                    └──────────┘
```

Seis serviços no Compose, todos com `healthcheck`. As portas são publicadas
apenas em `127.0.0.1`.

## Duas interfaces, uma sessão

| | Site público | Painel administrativo |
| --- | --- | --- |
| Caminho | `/` | `/admin`, `/horizon` |
| Renderização | Inertia + Vue 3 + TypeScript | Filament (Livewire) e Horizon (Vue próprio) |
| Assets | Vite | pipelines próprios dos pacotes |
| Autenticação | Fortify, páginas escritas de raiz | ecrã do Filament |
| Guard | `web` | `web` — **o mesmo** |
| Fronteira | — | **autorização**: `canAccessPanel()` e gate `viewHorizon` |

Clientes e staff partilham o modelo `User` e o guard. A fronteira é de
autorização, não de autenticação — ver
[ADR-006](decisions/ADR-006-autenticacao-fortify-e-filament.md). Isso concentra o
risco num ponto pequeno e testável, em vez de o espalhar por duas configurações
paralelas que podem divergir.

**Consequência que já custou um defeito:** o array de middleware do Filament
**substitui** o grupo `web` em vez de o herdar. Middleware acrescentado em
`bootstrap/app.php` não chega ao painel sem ser duplicado no
`AdminPanelProvider` (KI-017). O Horizon faz o contrário — usa o grupo `web` por
nome, e herda.

## Onde vive cada coisa

| Responsabilidade | Onde | Nunca |
| --- | --- | --- |
| Disponibilidade de assentos, pedidos, pagamentos | **PostgreSQL** | — |
| Sessões | **PostgreSQL** | não migrar para Redis |
| Cache, filas, rate limiting, locks auxiliares | **Redis** | nunca fonte de verdade |
| Assets do site público | Vite → `public/build` | — |

A secção 3 do `agent.md` não lista sessões entre os usos do Redis. Há um teste que
impede a mudança por conveniência.

## Camadas HTTP

Pedidos ao site público atravessam, por esta ordem:

1. **nginx** — só `index.php` é executado; qualquer outro `.php` devolve 404.
   Cabeçalhos estáticos: `nosniff`, `X-Frame-Options: DENY`, `Referrer-Policy`.
2. **`ContentSecurityPolicy`** — CSP com nonce por pedido. Sai da aplicação, e não
   do nginx, porque precisa de um valor por pedido (KI-012).
3. **`HandleInertiaRequests`** — partilha `locale` e `auth.user` com **id e nome
   apenas**. O email nunca entra.
4. **`LimitarPedidosSensiveis`** — limita registo, recuperação e redefinição, que
   o Fortify entrega sem travão nenhum.

O painel administrativo recebe uma política CSP diferente, com `unsafe-eval` e
`unsafe-inline`, porque o Alpine avalia expressões com `new Function()`. Mantém
`default-src 'self'` e `connect-src 'self'`: um XSS no painel não exfiltra para
fora do domínio.

## Trabalho assíncrono

Jobs vão para Redis e são consumidos pelo serviço `horizon`, num container
próprio — um worker que morre tem de ser visível e reiniciável sozinho. O
`healthcheck` usa `horizon:status`.

Nenhuma tarefa de negócio está agendada. A primeira será a expiração de reservas,
no Marco 2, e não entra sem os testes de concorrência que o plano exige.

## Saúde

| Endpoint | Pergunta | Toca em dependências |
| --- | --- | --- |
| `/up` | o processo está vivo? | **não** |
| `/saude` | consegue servir pedidos reais? | PostgreSQL e Redis |

A separação é deliberada: o `healthcheck` do nginx usa `/up`. Se dependesse da
base de dados, uma falha do PostgreSQL derrubaria o container web, que continua
capaz de servir páginas de erro.

`/saude` não diz **qual** dependência falhou. O diagnóstico está nos logs.

## Estrutura de código

```text
app/
├── Actions/        Identity/ (promoção a staff), Fortify/
├── Console/        comandos de operação
├── Filament/       painel administrativo
├── Http/           Controllers, Middleware, Responses
├── Jobs/  Listeners/  Logging/  Models/  Providers/
```

As pastas de domínio da secção 4 do `agent.md` — Cinema, Catalog, Screening,
Booking, Checkout, Payment, Ticketing — **ainda não existem**. São criadas quando
tiverem conteúdo real, a partir do Marco 1. Pastas vazias para satisfazer um
diagrama seriam ruído.

## Ambientes

Docker é o ambiente canónico — [ADR-007](decisions/ADR-007-docker-ambiente-canonico.md).
A CI constrói e corre a **mesma imagem** do `Dockerfile`, para não existir uma
terceira versão de PHP além do host (8.5) e do container (8.4.23).

Limite descoberto em C0.9: correr dentro do container dá paridade de *software*,
não de *sistema de ficheiros*. Um bind mount de macOS herda insensibilidade a
maiúsculas mesmo dentro de Linux (KI-022). Para nomes de ficheiros e permissões,
a autoridade é a CI.

## O que não existe

Catálogo, sessões, assentos, reservas, checkout, pagamentos, bilhetes, QR Code,
scanner, relatórios. Nada disso foi construído: o Marco 0 é fundação.

Também não existe imagem de produção — o `Dockerfile` só tem o stage
`development`, que monta o código em leitura-escrita e traz ferramentas de build.
**Não deve ser usado em produção** (B-017).
