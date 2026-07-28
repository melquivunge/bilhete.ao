# Plano de Implementação — Marco 0 (Fundação)

Documento produzido no ciclo **C0.1**. Define como o ambiente inicial do Bilhete.ao
será construído, dividido em ciclos pequenos e verificáveis.

Este plano cobre **apenas o Marco 0**. Catálogo, reservas, checkout, pagamentos e
bilhetes pertencem aos Marcos 1–5 e não devem ser antecipados.

---

## 1. Estado real do repositório (verificado em 2026-07-28)

Antes deste ciclo o repositório continha apenas dois ficheiros:

```text
agent.md          # especificação do produto e do processo (AGENTS.md)
repositorio.md    # instruções de criação do repositório GitHub
```

Factos apurados por inspeção direta:

| Item | Estado verificado |
| --- | --- |
| Controlo de versões | Não era um repositório Git (`fatal: not a git repository`) |
| Aplicação Laravel | Inexistente |
| `composer.json` / `package.json` | Inexistentes |
| Testes | Inexistentes |
| Docker | Inexistente no repositório |
| CI | Inexistente |
| Documentação | Inexistente (`docs/` não existia) |
| Remoto Git | `git@github.com:melquivunge/bilhete.ao.git` (indicado em `repositorio.md`, ainda não configurado) |

Toolchain da máquina de desenvolvimento:

| Ferramenta | Versão verificada |
| --- | --- |
| PHP (host) | 8.5.6 |
| Composer | 2.9.2 |
| Node | 26.0.0 |
| npm | 11.12.1 |
| Docker CLI | 29.4.0 |
| Docker Compose | v2.38.1-desktop.1 |
| Git | 2.50.1 |
| `psql` (host) | **ausente** — PostgreSQL só existirá dentro do Docker |
| Daemon Docker | **parado** no momento da inspeção |

Conclusão: o repositório está vazio para efeitos de produto. Aplica-se o caminho
"repositório vazio" da secção 18 do `agent.md`.

---

## 2. Versões-alvo (verificadas no Packagist, não assumidas)

| Componente | Versão-alvo | Evidência |
| --- | --- | --- |
| Laravel Framework | `^13.0` (atual: v13.23.0) | exige `php: ^8.3` |
| PHP (imagem Docker) | 8.4 | dentro do intervalo suportado; margem face ao 8.5 do host |
| Filament | `^5.0` (atual: v5.7.3) | `illuminate/contracts: ^11.28\|^12.0\|^13.0` → compatível com Laravel 13 |
| Livewire | `^4.1` | dependência transitiva do Filament |
| Inertia Laravel | `^3.2` | `laravel/framework: ^11.35\|^12.0\|^13.0` |
| Vue | 3.x | — |
| Laravel Horizon | `^5.48` | exige `ext-pcntl` e `ext-posix`; aceita `illuminate/contracts ^13.0` |
| PostgreSQL | 17 | tag `postgres:17-alpine` confirmada no Docker Hub |
| Redis | 8 | tag `redis:8-alpine` confirmada no Docker Hub |
| Node (container) | 24 (Krypton, LTS ativo) | `nodejs.org` marca 24.18.0 como `lts: "Krypton"` e 26.5.0 como `lts: false` |

O host corre PHP 8.5.6 e Node 26.5.0; o container correrá PHP 8.4 e Node 24.
Estas divergências são deliberadas e seguem o mesmo critério: usar a versão
estável mais recente em vez da versão mais recente. Ver
`docs/decisions/ADR-007-docker-ambiente-canonico.md`. A consequência é que
**a verificação que conta é a executada dentro do Docker**.

As tags exatas das imagens `php` e `node` são fixadas e confirmadas em C0.3, no
momento do build.

---

## 3. Divisão em ciclos

Cada ciclo é uma unidade fechada: implementa, verifica, documenta e só então
liberta o ciclo seguinte. Nenhum ciclo avança com verificação em falha.

```text
C0.1  Inspeção, decisões e plano                (este ciclo)
C0.2  Esqueleto Laravel e baseline de qualidade
C0.3  Docker Compose: PHP-FPM, Nginx, PostgreSQL, Redis
C0.4  Frontend: Vite, Vue 3, TypeScript, Tailwind, Inertia
C0.5  Autenticação de clientes (Fortify + páginas Inertia)
C0.6  Painel administrativo Filament e acesso de staff
C0.7  Redis, filas, Horizon e scheduler
C0.8  Health checks e observabilidade mínima
C0.9  Integração contínua (GitHub Actions)
C0.10 Documentação do Marco 0 e fecho
```

Dependências **duras** — quebrá-las obriga a refazer trabalho:

```text
C0.2 → C0.3 → C0.4
C0.3 → C0.5 → C0.6
C0.3 → C0.7
C0.3 → C0.8
C0.4 → C0.9   (a CI precisa dos scripts npm)
tudo  → C0.10
```

C0.7 (Redis, filas, Horizon) não depende tecnicamente de C0.6 (Filament): a ordem
entre eles é de conveniência, não de necessidade. Se C0.6 bloquear, C0.7 pode
avançar.

C0.4 depende de C0.3 apenas para verificação end-to-end; a instalação de pacotes
frontend pode ser preparada antes, mas a conclusão do ciclo exige o stack a correr.

**Regra de fecho.** Cada ciclo termina com um commit próprio, para que a fronteira
entre ciclos seja verificável em `git log`. Um ciclo sem commit não está fechado.

---

### C0.1 — Inspeção, decisões e plano *(concluído neste ciclo)*

**Objetivo.** Determinar o estado real do repositório, fixar as decisões que
condicionam todo o Marco 0 e produzir este plano.

**Ficheiros.** `README.md`, `.gitignore`, `docs/plan-marco-0.md`,
`docs/progress.md`, `docs/backlog.md`, `docs/known-issues.md`,
`docs/decisions/`.

**Riscos.** Planear sobre versões assumidas em vez de verificadas — mitigado por
consulta direta ao Packagist.

**Critério de conclusão.** Plano escrito, decisões registadas em ADR, repositório
Git inicializado, sem código de aplicação criado.

---

### C0.2 — Esqueleto Laravel e baseline de qualidade

**Objetivo.** Ter uma aplicação Laravel 13 que arranca, com as ferramentas de
qualidade a correr desde o primeiro commit de código.

**Trabalho.**
- `composer create-project laravel/laravel` fixando `^13.0`.
- Estrutura de domínio da secção 4 do `agent.md` criada **apenas** com as pastas
  que passam a ter conteúdo real; não criar as dez pastas de domínio vazias.
- Pint (formatação), Larastan/PHPStan em nível progressivo, Pest.
- `.env.example` com as chaves de PostgreSQL e Redis já previstas, sem segredos.
- Um teste de fumo que garante que a aplicação arranca.

**Ficheiros.** `composer.json`, `pint.json`, `phpstan.neon`, `tests/`,
`.env.example`, `app/`, `bootstrap/`, `config/`.

**Regras de negócio.** Nenhuma. Ciclo puramente estrutural.

**Riscos.**
- Nível de PHPStan demasiado alto trava o arranque → começar num nível que passa
  e subir num ciclo próprio.
- `composer create-project` traz scaffolding que não usaremos → remover apenas o
  que for claramente morto, sem refatorações não relacionadas.

**Testes.** `composer validate`, `./vendor/bin/pint --test`,
`./vendor/bin/phpstan analyse`, `composer test`.

Nota: `php artisan test` não é usado em ciclo nenhum — sai com código 1 mesmo com
os testes a passar (KI-008). O comando canónico é `composer test`, e `composer
check` encadeia as quatro verificações.

**Critério de conclusão.** Os quatro comandos passam no host; aplicação arranca
sem base de dados configurada.

---

### C0.3 — Docker Compose: PHP-FPM, Nginx, PostgreSQL, Redis

**Objetivo.** Tornar o Docker o ambiente canónico: a aplicação corre, liga-se ao
PostgreSQL e ao Redis e executa migrations dentro dos containers.

**Trabalho.**
- `Dockerfile` multi-stage para PHP 8.4-FPM com `pdo_pgsql`, `redis`, `intl`
  (exigida pelo `filament/support`), `pcntl` e `posix` (exigidas pelo
  `laravel/horizon`, verificado no Packagist), `bcmath`, `zip`.
- `SESSION_DRIVER=database`, com a tabela de sessões em PostgreSQL. Sessões não
  ficam em Redis: a secção 3 do `agent.md` lista Redis para cache, filas, rate
  limiting e locks auxiliares, e a perda de sessões num reinício de Redis não
  traz benefício que a justifique.
- `docker-compose.yml` com serviços `app`, `nginx`, `postgres`, `redis`.
- Configuração Nginx a servir `public/`.
- Volumes nomeados para persistência de PostgreSQL e Redis.
- `healthcheck` em cada serviço; `app` depende de `postgres`/`redis` saudáveis.
- Utilizador não-root no container da aplicação.

**Ficheiros.** `Dockerfile`, `docker-compose.yml`, `docker/nginx/default.conf`,
`docker/php/php.ini`, `.dockerignore`, `.env.example`.

**Riscos.**
- **Daemon Docker parado** — verificado como parado na inspeção. Se não arrancar,
  o ciclo fica `BLOCKED_TECHNICAL` e nenhuma verificação será declarada como
  passada.
- Extensões em falta só rebentam ciclos à frente: `intl` em C0.6 e `pcntl`/`posix`
  em C0.7. Todas são instaladas já em C0.3 e verificadas por comando.
- Permissões de `storage/` entre host e container em macOS.

**Testes.** `docker compose config`, `docker compose build`,
`docker compose up -d`, `docker compose ps` (todos os serviços `healthy`),
`docker compose exec app php -m` (confirmar `pdo_pgsql`, `redis`, `intl`,
`pcntl`, `posix`, `bcmath`, `zip`),
`docker compose exec app php artisan migrate` contra PostgreSQL,
`docker compose exec app composer test`.

**Critério de conclusão.** As sete extensões presentes em `php -m`, migrations de
raiz aplicadas em PostgreSQL dentro do container e suíte de testes verde dentro do
container.

---

### C0.4 — Frontend: Vite, Vue 3, TypeScript, Tailwind, Inertia

**Objetivo.** Servir uma página Inertia com Vue 3 e TypeScript, com build de
produção a funcionar.

**Trabalho.**
- `inertiajs/inertia-laravel` e `@inertiajs/vue3`; middleware `HandleInertiaRequests`.
- Vite com plugin Vue e TypeScript; `vue-tsc` para type-check.
- Tailwind CSS configurado mobile-first.
- ESLint e Prettier.
- **Node corre em container, não no host.** O host tem Node 26 e a decisão é
  Node 24; sem um serviço `node` no Compose (ou um stage próprio no Dockerfile),
  nada impede alguém de correr `npm run dev` no Node do host e reintroduzir a
  divergência que evitámos para o PHP. A forma concreta fica decidida e registada
  no ADR-005, escrito neste ciclo.
- Excluir `node_modules` do bind mount por volume próprio: em macOS, um bind
  mount único com `node_modules` degrada muito o I/O.
- Uma página mínima de verificação (shell da aplicação). **Não** é a home da
  secção 11 do `agent.md` — essa pertence ao Marco 1.
- Locale `pt` configurado. **Sem** helpers de formatação de Kwanza: a unidade
  monetária inteira só se decide no Marco 1 (B-021), e escrever formatação antes
  disso seria decidi-la por acidente.
- Escrever `docs/decisions/ADR-005-inertia-e-vue-no-frontend.md` neste ciclo, que
  é onde a decisão passa a ser executada.

**Ficheiros.** `package.json`, `vite.config.ts`, `tsconfig.json`,
`resources/js/app.ts`, `resources/js/Pages/`, `resources/css/app.css`,
`app/Http/Middleware/HandleInertiaRequests.php`, `eslint.config.js`.

**Riscos.**
- Filament traz Livewire e o seu próprio bundle; conflito de assets com Vite →
  manter os pipelines separados e validar em C0.6.
- Node 26 muito recente para algum plugin Vite → fixar versões e registar.

**Testes.** `npm ci`, `npm run type-check`, `npm run lint`, `npm run build`, e um
teste de feature que assere que a rota devolve uma resposta Inertia com o
componente esperado.

**Critério de conclusão.** Build de produção gerado e teste de resposta Inertia a
passar dentro do container.

---

### C0.5 — Autenticação de clientes (Fortify + páginas Inertia)

**Objetivo.** Registo, login, logout e recuperação de palavra-passe para clientes,
com páginas próprias em Inertia/Vue/TS.

**Trabalho.**
- `laravel/fortify` com views Inertia escritas por nós (sem starter kit, para não
  herdar layout genérico — requisito de originalidade visual da secção 11).
- Rate limiting em login, registo e recuperação de palavra-passe.
- Hashing e políticas de palavra-passe.
- Enumeração de contas mitigada: respostas indistinguíveis para email existente e
  inexistente na recuperação.
- Notificações de recuperação com `MAIL_MAILER=array` — **nenhum envio real**.
  Não usar o driver `log`: escreveria o token de recuperação em claro em
  `storage/logs/`, o que a secção 10 do `agent.md` proíbe.
- Proteção contra mass assignment no registo: apenas os campos esperados são
  atribuíveis, e nenhum campo que conceda privilégio (incluindo a futura marca de
  staff usada em C0.6) pode ser atribuído a partir do formulário público.

**Ficheiros.** `config/fortify.php`, `app/Providers/FortifyServiceProvider.php`,
`resources/js/Pages/Auth/`, `routes/web.php`, `tests/Feature/Auth/`.

**Regras de negócio.** Um cliente autenticado é um `User` sem vínculo a cinema.
O vínculo a empresas/cinemas é do Marco 1 e não deve ser antecipado.

**Riscos.**
- **Mass assignment:** o registo é o primeiro ponto do sistema em que entrada
  pública popula um modelo. Um campo de privilégio atribuível em massa aqui torna
  inútil o controlo de acesso de C0.6.
- Coexistência de rotas entre Fortify e Filament (KI-005) → validada em C0.6.
- Credential stuffing e força bruta → throttling verificado por teste.

**Testes.** Feature tests: registo válido, registo inválido, login correto, login
incorreto, bloqueio por rate limit, logout, fluxo de recuperação, verificação de
que a resposta de recuperação não revela existência de conta, e um teste que
tenta atribuir por mass assignment um campo de privilégio no registo e assere que
não foi atribuído.

**Critério de conclusão.** Suíte de autenticação verde, incluindo os testes de
rate limiting e de mass assignment.

---

### C0.6 — Painel administrativo Filament e acesso de staff

**Objetivo.** Painel Filament em `/admin` acessível apenas a staff, isolado da
autenticação de clientes.

**Trabalho.**
- Filament 5, painel `admin`.
- Distinção entre cliente e staff. A modelação definitiva de papéis por empresa e
  cinema é do Marco 1 e do Marco 6; aqui implementa-se o mínimo necessário para
  negar acesso a não-staff, de forma a não ter de ser refeito.
- `canAccessPanel()` a negar por omissão.
- Sessão administrativa separada e headers de segurança no painel.

**Ficheiros.** `app/Providers/Filament/AdminPanelProvider.php`, `app/Models/User.php`,
`database/migrations/`, `tests/Feature/Admin/`.

**Riscos.**
- **Crítico:** painel aberto a qualquer utilizador autenticado. Mitigação: negar
  por omissão e cobrir com teste explícito antes de fechar o ciclo.
- Decisões de papéis tomadas à pressa aqui contaminam o Marco 1 → manter o mínimo
  e registar a dívida no backlog.

**Testes.** Cliente autenticado recebe 403 em `/admin`; visitante é redirecionado;
utilizador staff entra; a rota de login do painel não colide com a do cliente.

**Critério de conclusão.** Teste de acesso negado a não-staff a passar. Este é o
critério que fecha o ciclo — sem ele, o ciclo não é dado como concluído.

---

### C0.7 — Redis, filas, Horizon e scheduler

**Objetivo.** Infraestrutura assíncrona pronta para a expiração de reservas do
Marco 2.

**Trabalho.**
- `QUEUE_CONNECTION=redis` e `CACHE_STORE=redis`. As sessões permanecem em
  PostgreSQL, conforme decidido em C0.3.
- Laravel Horizon com serviço próprio no Compose.
- Dashboard do Horizon protegido por gate (nunca aberto).
- Scheduler configurado, sem tarefas de negócio ainda.
- Um job de exemplo, apenas para provar o percurso fila → worker.

**Ficheiros.** `config/queue.php`, `config/horizon.php`,
`app/Providers/HorizonServiceProvider.php`, `docker-compose.yml`, `routes/console.php`.

**Riscos.**
- **Crítico:** dashboard do Horizon exposto sem autorização → gate verificado por
  teste, à semelhança de C0.6.
- Redis usado como fonte de verdade por engano → o `agent.md` é explícito: Redis
  é cache, filas, rate limiting e locks auxiliares; a verdade é o PostgreSQL.

**Testes.** Job despachado e processado; teste de que o dashboard do Horizon nega
acesso a não autorizados.

**Critério de conclusão.** Worker a consumir da fila dentro do Compose e gate do
Horizon coberto por teste.

---

### C0.8 — Health checks e observabilidade mínima

**Objetivo.** Saber, a partir do exterior, se a aplicação e as suas dependências
estão vivas.

**Trabalho.**
- Endpoint de saúde que verifica PostgreSQL e Redis.
- Resposta sem detalhes internos para pedidos não autenticados: estado agregado,
  sem versões, hosts ou mensagens de erro.
- Logging estruturado, com redação das chaves sensíveis listadas na secção 10 do
  `agent.md`.
- **Exceções de conectividade apanhadas explicitamente.** A fuga mais provável
  não vem de um corpo de resposta mal desenhado: vem de uma `PDOException` ou
  `RedisException` a subir sem tratamento e, com `APP_DEBUG=true`, o Laravel
  devolver a página de erro completa com host, credencial parcial e stack trace.
  O controlador devolve sempre corpo fixo, sem `getMessage()`.
- Recusar arranque com `APP_DEBUG=true` fora de `local` e `testing`, coberto por
  teste.

**Ficheiros.** `routes/web.php`, `app/Http/Controllers/HealthController.php`,
`config/logging.php`, `tests/Feature/HealthTest.php`.

**Riscos.**
- Health check a expor topologia interna → asserção explícita no teste sobre o
  corpo da resposta.
- Health check pesado usado por load balancer → manter barato e com timeout.

**Testes.** Health check devolve 200 com dependências saudáveis; devolve 503 com
dependência em baixo; a resposta não contém strings sensíveis.

**Critério de conclusão.** Os três testes a passar.

---

### C0.9 — Integração contínua (GitHub Actions)

**Objetivo.** Pipeline que reproduz as verificações locais e falha o merge quando
algo quebra.

**Trabalho.**
- Workflow com serviços PostgreSQL e Redis.
- Passos: `composer validate`, `composer install`, `pint --test`,
  `phpstan analyse`, `composer test`, `npm ci`, `npm run type-check`,
  `npm run lint`, `npm run build`.
- Cache de dependências Composer e npm.
- Sem segredos no workflow; nenhum passo de deploy.
- **A CI constrói e corre dentro da imagem do `Dockerfile`**, com
  `docker compose build` e `run`. Não instala PHP com uma action genérica.

**Ficheiros.** `.github/workflows/ci.yml`.

**Riscos.**
- **Três versões de PHP em vez de duas.** Se a CI instalar PHP à parte, passa a
  haver host (8.5.6), container (8.4.23) e runner, quando KI-002 e o ADR-007 só
  preveem e mitigam uma divergência. Daí a CI usar a mesma imagem.
- Tentação de adicionar deploy → proibido pela secção 16 do `agent.md`.

**Testes.** O próprio pipeline. Enquanto o remoto não estiver configurado, os
passos são executados localmente e a limitação fica registada em `progress.md`.

**Critério de conclusão.** Workflow válido e todos os passos verificados,
localmente ou no GitHub, com a origem da evidência declarada.

---

### C0.10 — Documentação do Marco 0 e fecho

**Objetivo.** Deixar o Marco 0 legível para quem chegar depois e verificar que os
entregáveis existem de facto.

**Trabalho.**
- `docs/architecture.md`, `docs/security.md`, `docs/product-scope.md`.
- README com instruções reais de arranque, testadas.
- ADRs em falta escritos.
- `docs/progress.md` e `docs/backlog.md` atualizados.
- Revisão independente do Marco 0 completo antes de o declarar fechado.

**Critério de conclusão.** Num clone limpo do repositório, executar pela ordem
exata os comandos do README leva a todos os serviços `healthy` e ao health check
de C0.8 a devolver 200 — sem recorrer a nenhum passo que não esteja escrito no
README.

---

## 4. Definição de "Marco 0 concluído"

O Marco 0 só é declarado COMPLETE quando **todos** os pontos abaixo forem
verdadeiros e verificados por execução, não por inspeção visual:

1. `docker compose up -d` levanta app, Nginx, PostgreSQL, Redis e Horizon, todos `healthy`.
2. Migrations aplicam-se em PostgreSQL dentro do container.
3. Uma página Inertia com Vue 3 e TypeScript é servida e o build de produção é gerado.
4. Um cliente regista-se, autentica-se e termina sessão.
5. Um não-staff recebe 403 em `/admin`; um staff entra.
6. O dashboard do Horizon nega acesso a não autorizados.
7. Um job é despachado e processado.
8. O health check reflete o estado real de PostgreSQL e Redis.
9. Pint, PHPStan, Pest, type-check, lint e build passam.
10. A CI executa a mesma sequência.
11. README, `docs/architecture.md`, `docs/security.md`, `progress.md` e `backlog.md` atualizados.

---

## 5. Fora do âmbito do Marco 0

Não implementar neste marco, mesmo que pareça barato:

- entidades de catálogo (`cinemas`, `movies`, `screenings`, ...);
- a home da secção 11 e restantes páginas públicas;
- `screening_seats`, reservas, bloqueio pessimista;
- checkout, pedidos, cupões;
- contrato `PaymentGateway`, gateway falso, webhooks;
- bilhetes, QR Code, scanner;
- relatórios, comissões, reembolsos;
- dados fictícios da secção 12 além do mínimo necessário à autenticação.

O modelo de papéis por empresa e cinema é deliberadamente mínimo em C0.6 e será
tratado no Marco 1.

---

## 6. Riscos transversais do Marco 0

| Risco | Impacto | Mitigação |
| --- | --- | --- |
| Daemon Docker indisponível | Bloqueia C0.3 em diante | Arrancar o Docker Desktop antes de cada ciclo; se falhar, declarar `BLOCKED_TECHNICAL` sem fingir verificações |
| Divergência PHP host 8.5 / container 8.4 | Testes passam num lado e falham no outro | A verificação que conta é a do container; CI fixa 8.4 |
| Coexistência Inertia/Vite com Livewire/Filament | Assets partidos | Pipelines separados; validação explícita em C0.6 |
| Painel ou Horizon abertos por omissão | Exposição de dados administrativos | Negar por omissão e cobrir com teste em C0.6 e C0.7 |
| Antecipar modelação do Marco 1 | Retrabalho e acoplamento | Fronteira explícita na secção 5 deste plano |
| Node 26 e Laravel 13 recentes | Incompatibilidades de pacotes | Versões fixas; incompatibilidades registadas em `known-issues.md` |

---

## 7. Decisões pendentes (não bloqueiam o Marco 0)

Registadas para não serem esquecidas nem decididas implicitamente:

1. Unidade monetária inteira do Kwanza — cêntimos ou unidade — a decidir com o
   modelo de dados no Marco 1.
2. Modelo definitivo de papéis e permissões por empresa e cinema (Marco 1/6).
3. Estratégia de identificadores públicos: UUID ou ULID, e onde substituem IDs
   sequenciais (Marco 1).
4. Fuso horário de apresentação: `Africa/Luanda`, com persistência em UTC.
5. Provedor de armazenamento compatível com S3 para cartazes (Marco 1).
