# Progresso

Registo cronológico dos ciclos executados. Entrada mais recente no topo.

---

## 2026-07-28 — C0.3 · Docker Compose com PHP-FPM, Nginx, PostgreSQL e Redis

**Marco.** 0 — Fundação.

**Tarefa.** Tornar o Docker o ambiente canónico: aplicação a correr, ligada a
PostgreSQL e Redis, com migrations e testes a executar dentro dos containers.

**Resultado.** Concluído. Todos os critérios de fecho verificados por execução,
depois de aplicadas as correções de duas revisões independentes.

**Implementado.**
- `Dockerfile` com stages `base` e `development` sobre `php:8.4.23-fpm-bookworm`,
  tag de patch fixa para não divergir do `platform.php` do `composer.json`.
- Sete extensões, com **asserção dentro do próprio build**: se faltar uma, a
  imagem não é construída. `pcntl` e `posix` incluídas agora, embora só sejam
  exigidas pelo Horizon no C0.7.
- `docker-compose.yml` com `app`, `nginx`, `postgres` e `redis`, healthchecks
  significativos e `depends_on` com `condition: service_healthy`.
- Healthcheck da `app` interroga o pool do PHP-FPM por `ping.path`, via
  `cgi-fcgi`; verifica que o pool responde, não que o processo existe.
- Portas publicadas apenas em `127.0.0.1`. Utilizador não-root no container.
- Script de init que cria `bilhete_testing`, a base que o `phpunit.xml` já exigia.
- `SESSION_DRIVER=database` sobre PostgreSQL, conforme decidido no plano.

**Ficheiros principais.**

```text
Dockerfile · docker-compose.yml · .dockerignore
docker/nginx/default.conf · docker/php/php.ini · docker/php/www.conf
docker/postgres/initdb/01-create-testing-database.sh
.env.example · .gitignore + 12 .gitignore repostos em storage/, bootstrap/, database/
```

**Testes e verificações — executados dentro dos containers.**

| Verificação | Resultado |
| --- | --- |
| `docker compose config` | saída 0 |
| `docker compose build` | imagem construída; asserção das 7 extensões passou |
| `docker compose up -d` + `ps` | `app`, `nginx`, `postgres`, `redis` todos `healthy` |
| `php -v` no container | PHP **8.4.23**, igual ao `platform.php` do `composer.json` |
| `php -m` | `bcmath intl pcntl pdo_pgsql posix redis zip` |
| `php artisan migrate --force` | 3 migrations aplicadas em PostgreSQL |
| bases de dados | `bilhete` e `bilhete_testing` — script de init verificado em volume novo |
| `composer test` no container | 3 testes, 3 asserções, saída 0 |
| Redis via facade | escrita e leitura com password ativa |
| `GET /` e `GET /up` | 200 |
| `GET /qualquer.php` | 404 — só o front controller é executado |
| Cabeçalhos | `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `CSP` presentes; `X-Powered-By` ausente; `Server: nginx` sem versão |
| `docker compose config` sem `DB_PASSWORD` | falha com mensagem explícita, como desenhado |

O volume do PostgreSQL foi destruído e recriado de propósito, para que o script
de init fosse verificado a sério e não apenas escrito. Continha as três migrations
de esqueleto criadas minutos antes, sem dados reais.

**Revisão independente.** Dois revisores, ambos sem terem escrito o código:
infraestrutura e segurança. Ambos aprovaram com correções, sem bloqueadores.
Os dois encontraram, de forma independente, o mesmo problema mais grave.

| Achado | Correção aplicada |
| --- | --- |
| Cache de views Blade commitado e os 12 `.gitignore` do Laravel em falta (KI-009) | Repostos; ficheiros removidos do índice; `git ls-files storage/` devolve zero |
| `bilhete_testing` nunca era criada, apesar de o `phpunit.xml` já a exigir | Script de init, verificado em volume novo |
| Tag `php:8.4-fpm-bookworm` flutuante contra `platform.php` fixo | Fixada em `php:8.4.23-fpm-bookworm` |
| `POSTGRES_PASSWORD` com fallback funcional em ficheiro versionado | `${DB_PASSWORD:?...}`: falha alto em vez de arrancar com senha pública |
| Redis sem password, prestes a entrar no caminho de locks e pagamentos | `requirepass` obrigatório, healthcheck autenticado |
| Qualquer `.php` sob a document root era executável | Só `index.php`; o resto devolve 404 |
| Sem cabeçalhos de segurança no Nginx | `nosniff`, `DENY`, `Referrer-Policy`, CSP |
| CI arriscava uma terceira versão de PHP | Plano do C0.9: a CI constrói e corre a mesma imagem |
| Node do host reintroduziria a divergência que evitámos no PHP | Plano do C0.4: Node em container, decidido no ADR-005 |
| Imagem de desenvolvimento podia ser reutilizada em produção | Limite explícito escrito no ADR-007; B-017 para o Marco 7 |
| Fuga por exceção não apanhada no health check com `APP_DEBUG=true` | Plano do C0.8 reforçado |

**Verificação independente dos achados.** Confirmei por execução antes de aplicar:
`git ls-files storage/` devolvia mesmo os quatro ficheiros; `find storage -name
.gitignore` não devolvia nada; `bilhete_testing` não existia; a tag
`php:8.4.23-fpm-bookworm` existe no Docker Hub. Todos procediam.

**Problema encontrado por mim durante a execução.** O healthcheck do `nginx`
ficava `unhealthy` enquanto o serviço respondia 200 ao host: dentro do container
`localhost` resolve para `::1` e o Nginx só escuta em IPv4 (KI-010).

**Riscos restantes.**
- A imagem só serve desenvolvimento. Não há stage de produção, e o ADR-007 agora
  diz isso explicitamente (B-017).
- `.dockerignore` ainda não é exercitado por build nenhum: o stage
  `development` não copia código, depende do bind mount.
- CORS por decidir antes de existir qualquer rota `api/*` (B-018).
- Nada foi testado em Linux; o ambiente declarado é macOS.

**Próxima tarefa recomendada.** C0.4 — Vite, Vue 3, TypeScript, Tailwind e
Inertia, com Node em container e o ADR-005 escrito no mesmo ciclo.

---

## 2026-07-28 — C0.2 · Esqueleto Laravel 13 e baseline de qualidade

**Marco.** 0 — Fundação.

**Tarefa.** Instalar a aplicação Laravel 13 e pôr as ferramentas de qualidade a
correr desde o primeiro commit de código.

**Resultado.** Concluído. As quatro verificações da Etapa D aplicáveis a este
ciclo passam.

**Implementado.**
- `laravel/laravel ^13.0` instalado (Laravel Framework 13.23.0) e integrado no
  repositório preservando o `README.md` e o `.gitignore` já escritos em C0.1.
- `composer.json` adaptado ao projeto: nome, licença proprietária, `php: ^8.4` e
  `config.platform.php = 8.4.23`, para que a resolução de dependências no host
  corresponda ao PHP do container, e não ao PHP 8.5 local.
- Pest 5 com `pest-plugin-laravel`, em vez do PHPUnit puro do esqueleto.
- Larastan 3 sobre PHPStan 2, nível 6.
- Pint com preset Laravel mais `declare_strict_types`, `strict_comparison` e
  `strict_param`; baseline aplicada a todo o esqueleto.
- `.env.example` reescrito para PostgreSQL e Redis, com `SESSION_DRIVER=database`
  e `MAIL_MAILER=array`.
- Testes de exemplo do esqueleto substituídos por `tests/Feature/ApplicationBootTest.php`.
- Scripts `composer lint`, `fix`, `analyse`, `test` e `check`.

**Ficheiros principais.**

```text
composer.json · composer.lock · phpunit.xml · pint.json · phpstan.neon
.env.example · .gitignore
tests/Pest.php · tests/Feature/ApplicationBootTest.php
database/factories/UserFactory.php
app/ · bootstrap/ · config/ · database/ · routes/ · public/ · resources/
```

**Testes e verificações — executados no host, com PHP 8.5.6.**

| Comando | Resultado |
| --- | --- |
| `composer validate --strict` | `./composer.json is valid` |
| `./vendor/bin/pint --test` | `passed` |
| `./vendor/bin/phpstan analyse --memory-limit=1G` | `passed`, 0 erros |
| `./vendor/bin/pest` | `passed`, 3 testes, 3 asserções, código de saída 0 |
| `composer check` (as quatro em sequência) | código de saída 0 |

`php artisan test` **não** é usado: imprime `passed` mas sai com código 1 (KI-008).

Nada correu em containers: o daemon do Docker continua parado (KI-001). Nenhuma
afirmação sobre PostgreSQL ou Redis foi verificada neste ciclo, e nenhum teste
toca na base de dados.

**Problemas encontrados durante a execução, e como foram resolvidos.**

1. `config.platform.php = 8.4.0` impedia a instalação: o `symfony/process` exigido
   pelo Pest 5 requer `>= 8.4.1`. Consultei o `php.net` e fixei em 8.4.23, o patch
   8.4 mais recente, em vez de escolher um número arbitrário.
2. PHPStan esgotava os 128 MB do `php.ini` do host. Fixei `--memory-limit=1G` no
   script `analyse`, para o resultado não depender da configuração de quem executa.
3. PHPStan acusava `Call to an undefined method Pest\PendingCalls\TestCall::get()`.
   A extensão PHPStan que o Pest fornece só cobre expectations de ordem superior,
   não o `$this` dentro dos closures. Reescrevi o teste com a função
   `Pest\Laravel\get()`, importada explicitamente: elimina o `$this` não tipado em
   vez de o silenciar.
4. Com `checkModelProperties: true`, o Larastan exige em `UserFactory::definition()`
   a sintaxe `array<model property of ..., mixed>`, que o parser de PHPDoc desta
   combinação de versões rejeita. Desliguei a verificação, com justificação no
   `phpstan.neon`, e registei KI-006 e B-015 para a reativar no Marco 1, quando
   existirem modelos e migrations reais. Não usei `@phpstan-ignore` nem baseline.
5. `composer check` falhava no fim apesar de tudo passar. A causa não era o
   `check`: `php artisan test` imprime `passed` e sai com código 1. O comando
   `test` vem do Collision, que invoca o Pest com `--no-output`; com Pest 5 esse
   argumento faz o processo sair com 1 sem qualquer mensagem de erro. O Collision
   já está na última versão (8.9.5), logo não há correção a montante. Passei a
   invocar `pest` diretamente no script `composer test`. Registado em KI-008.
   Este é precisamente o tipo de falha que a secção 16 do `agent.md` visa: um
   pipeline que reporta sucesso no ecrã e insucesso ao sistema.

**Decisões tomadas.**
- A base de dados de teste no `phpunit.xml` é PostgreSQL, não o SQLite em memória
  que o esqueleto traz. Um `SELECT ... FOR UPDATE` não tem equivalente fiel em
  SQLite, e testes de concorrência verdes em SQLite dariam falsa confiança sobre
  reserva dupla de assentos. Nenhum teste toca na base de dados antes de C0.3.
- A suíte `Unit` do `phpunit.xml` foi removida por não ter conteúdo; volta quando
  existir um teste unitário real.
- `APP_TIMEZONE` foi retirado do `.env.example`: o `config/app.php` do Laravel 13
  fixa `'timezone' => 'UTC'` e não lê o ambiente, pelo que a variável seria
  configuração morta.
- Nenhuma pasta de domínio (`app/Domain/...`) foi criada: nenhuma teria conteúdo.

**Riscos restantes.**
- Tudo continua por verificar em containers (KI-001).
- `resources/views/welcome.blade.php` ainda é a página por omissão do Laravel;
  é substituída pelo shell Inertia em C0.4.
- O esqueleto traz `laravel/pao`, que formata a saída das ferramentas em JSON.
  É o default do Laravel 13 e foi mantido, mas explica o aspeto da saída.
- PHPStan está no nível 6 com `checkModelProperties` desligado; a subida de rigor
  é B-011 e B-015.

**Próxima tarefa recomendada.** C0.3 — Docker Compose com PHP-FPM 8.4, Nginx,
PostgreSQL 17 e Redis 8, com as sete extensões confirmadas por `php -m` e as
migrations aplicadas dentro do container.

---

## 2026-07-28 — C0.1 · Inspeção do repositório e plano do Marco 0

**Marco.** 0 — Fundação.

**Tarefa.** Inspecionar o repositório, fixar as decisões que condicionam o Marco 0
e produzir o plano de implementação do ambiente inicial, dividido em ciclos
pequenos.

**Resultado.** Concluído. Nenhum código de aplicação foi escrito, por desenho:
a secção 18 do `agent.md` define a inspeção e o plano como primeira tarefa.

**Estado encontrado.** O repositório continha apenas `agent.md` e
`repositorio.md`, não era um repositório Git e não tinha aplicação, testes,
Docker, CI nem documentação. O toolchain local tem PHP 8.5.6, Composer 2.9.2,
Node 26.0.0, Docker CLI 29.4.0 e Git 2.50.1; não tem `psql`, e o daemon do Docker
estava parado.

**Ficheiros principais.**

```text
README.md
.gitignore
docs/plan-marco-0.md
docs/progress.md
docs/backlog.md
docs/known-issues.md
docs/decisions/README.md
docs/decisions/ADR-001-monolito-laravel-modular.md
docs/decisions/ADR-006-autenticacao-fortify-e-filament.md
docs/decisions/ADR-007-docker-ambiente-canonico.md
```

**Testes executados.** Nenhum. Não existe código executável no repositório, logo
não há suíte de testes, migrations, build ou lint a correr. Os comandos da Etapa D
do `agent.md` passam a aplicar-se a partir de C0.2.

**Verificações efetivamente executadas neste ciclo.**

| Verificação | Comando | Resultado |
| --- | --- | --- |
| Estado do Git | `git status` | `fatal: not a git repository` — confirmou repositório não versionado |
| Conteúdo do repositório | `ls -A` | apenas `agent.md` e `repositorio.md` |
| Toolchain | `php -v`, `composer --version`, `node -v`, `npm -v`, `docker --version`, `docker compose version`, `git --version`, `psql --version` | versões registadas acima; `psql` ausente |
| Daemon Docker | `docker info` | não foi possível ligar ao daemon |
| Versões de pacotes | consulta ao Packagist (`repo.packagist.org/p2/...`) para `laravel/framework`, `filament/support`, `inertiajs/inertia-laravel` | Laravel v13.23.0 exige PHP `^8.3`; Filament 5.7.3 aceita `illuminate/contracts ^13.0`; Inertia 3.2 aceita Laravel `^13.0` |

**Decisões tomadas.** Registadas em ADR-001, ADR-006 e ADR-007: monólito Laravel
13 modular sobre PHP 8.4, autenticação separada entre clientes (Fortify) e staff
(Filament) partilhando o guard `web`, e Docker como ambiente canónico de
desenvolvimento e CI.

**Revisão independente.** O plano foi revisto por um agente de arquitetura que não
o escreveu, com instrução explícita de o criticar. Veredicto: aprovado com
correções, sem bloqueadores. Seis achados importantes, todos aceites e corrigidos:

| Achado | Correção aplicada |
| --- | --- |
| Faltavam `ext-pcntl` e `ext-posix`, exigidas pelo Horizon, no Dockerfile de C0.3 — rebentaria só em C0.7 | Extensões acrescentadas a C0.3 e verificadas por `php -m` no critério de conclusão |
| A arquitetura de guards Fortify/Filament ficava para descobrir em C0.6, depois de C0.5 já ter código | Decidida agora no ADR-006: guard `web` partilhado, fronteira por autorização |
| ADR-006 citava KI-004 para risco de rotas, mas KI-004 só cobre assets | Criada KI-005 para rotas e autenticação; âmbito de KI-004 delimitado |
| ADR-005 agendado para o Marco 1 apesar de o frontend ser implementado em C0.4 | Movido para C0.4 (B-013) |
| Node 26 rotulado "LTS" sem verificação, contrariando o critério de estabilidade aplicado ao PHP | Verificado: 26.5.0 é `lts: false`; container passa a Node 24 (Krypton) |
| Mass assignment não endereçado em C0.5, apesar da secção 10 do `agent.md` | Acrescentado aos riscos, aos testes e ao critério de conclusão de C0.5 |

Achados menores também corrigidos: critério subjetivo de C0.10 substituído por um
executável; driver de sessão fixado em PostgreSQL em vez de "a decidir";
dependências duras separadas das de mera ordem; `MAIL_MAILER=array` em vez de
`log`, que escreveria tokens de recuperação em claro; regra de fechar cada ciclo
com um commit.

**Verificação independente dos achados.** Não aceitei o relatório sem o confirmar.
Verifiquei diretamente no Packagist que `laravel/horizon` v5.48.1 exige `ext-pcntl`
e `ext-posix`, e em `nodejs.org` que a 24.18.0 é `lts: "Krypton"` e a 26.5.0 é
`lts: false`. Ambos os achados procediam. Verifiquei também no Docker Hub a
existência das tags `postgres:17-alpine` e `redis:8-alpine`, que o plano citava
como "imagem oficial" sem evidência.

**Riscos restantes.**
- O daemon do Docker está parado; C0.3 fica bloqueado até arrancar.
- Laravel 13, PHP 8.4/8.5 e Node 26 são recentes; podem surgir incompatibilidades
  de pacotes de terceiros ainda não observadas.
- O remoto Git indicado em `repositorio.md` ainda não foi configurado, logo a CI
  do ciclo C0.9 não pode ser validada no GitHub até isso acontecer.
- Nada foi executado dentro de containers, portanto nenhuma afirmação sobre o
  comportamento em PostgreSQL ou Redis foi verificada.
- O host corre Node 26 e o container correrá Node 24; tal como em KI-002 para o
  PHP, o que vale é o que corre no container.
- O repositório Git foi inicializado na branch `main`, mas **não foi feito
  nenhum commit**: aguarda autorização. Enquanto não houver commit, a fronteira
  entre C0.1 e C0.2 não é observável em `git log`.

**Próxima tarefa recomendada.** C0.2 — instalar o esqueleto Laravel 13 e a
baseline de qualidade (Pint, PHPStan, Pest), com `composer validate`,
`pint --test`, `phpstan analyse` e `pest` a passar.
