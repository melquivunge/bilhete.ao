# Progresso

Registo cronológico dos ciclos executados. Entrada mais recente no topo.

---

## 2026-07-30 — C0.6 · Painel administrativo Filament com acesso negado por omissão

**Marco.** 0 — Fundação.

**Tarefa.** Painel Filament em `/admin`, acessível apenas a staff, isolado da
autenticação de clientes.

**Resultado.** Concluído.

**Implementado.**
- Filament 5.7 com painel `admin`. Registo, recuperação de palavra-passe e página
  de perfil do Filament ficam **desligados** — superfície mínima.
- Marca `is_staff`, booleana, com valor por omissão `false` na base de dados **e**
  no modelo. Não é um sistema de papéis: esse é do Marco 1 (B-020), depende de
  cinemas existirem, e vai substituir esta coluna.
- `canAccessPanel()` compara estritamente com `true`. Nega perante `null`, `1`,
  string vazia ou coluna ausente.
- `is_staff` fora do `#[Fillable]`, com `PromoverUtilizadorAStaff` como único
  caminho de escrita (B-042, condição de fecho do ciclo).
- Comando `bilhete:criar-staff`, que pede a palavra-passe interativamente e nunca
  a aceita como argumento — um argumento ficaria no histórico da shell e na lista
  de processos.
- Avatares gerados localmente, em `data:` URI.

**Testes e verificações — dentro dos containers.**

| Verificação | Resultado |
| --- | --- |
| `composer test` | **85 testes, 356 asserções**, saída 0 |
| `composer check` | saída 0 |
| `npm run lint / type-check / format:check / build` | saída 0 |
| Browser: anónimo em `/admin` | redirecionado para `/admin/login` |
| Browser: **cliente autenticado no site público** em `/admin` | **HTTP 403** |
| Browser: staff em `/admin` | entra no painel, 0 erros de consola |

**KI-004 e KI-005 fechados**, abertos desde o C0.1: nenhuma rota registada duas
vezes, entradas separadas por contexto, e cada pipeline de assets carrega só os
seus.

**Revisão independente de segurança.** Aprovada com correções, sem achados
críticos. A fronteira central foi confirmada por leitura do código do Filament, e
não apenas do meu comentário: `canAccessPanel()` é chamado por um único middleware
que envolve todas as rotas autenticadas do painel, pelo que um Resource futuro não
o pode contornar por esquecimento.

O achado ALTO desmentiu uma coisa que eu tinha assumido: **o `/admin` não emitia
Content-Security-Policy nenhum** (KI-017). O array de middleware do Filament
substitui o grupo `web` em vez de o herdar, pelo que o middleware anexado em
`bootstrap/app.php` nunca chegava lá. Confirmei com `curl -I`: `/` devolvia a
política, `/admin/login` devolvia zero.

O mais incómodo é *porque* passou: eu tinha corrido o painel num browser real e
obtido zero erros de consola. Era verdade e não provava nada — não havia política
para violar. **Um controlo ausente é silencioso; só um teste que exija a sua
presença o deteta.** Foi essa a lição, e está agora em
`tests/Feature/Admin/SegurancaDoPainelTest.php`.

| Achado | O que fiz |
| --- | --- |
| `/admin` sem CSP (KI-017) | Middleware acrescentado à lista do painel, com política própria, e teste a exigi-lo |
| Mass assignment bloqueado em silêncio | `Model::preventSilentlyDiscardingAttributes()` fora de produção: passa a rebentar em vez de descartar |
| Divergência de middleware painel/site | Documentada em comentário no `AdminPanelProvider` |
| `/admin/login` sem teste de rate limiting | Teste próprio, para a proteção não depender de confiança no vendor |
| KI-004/KI-005 ainda "por confirmar" na documentação apesar de testados | Corrigido, com referência aos testes concretos |

**Um achado que não estava em relatório nenhum.** Ao acrescentar o CSP, o browser
bloqueou quatro pedidos a `ui-avatars.com`: o provedor de avatar por omissão do
Filament enviava **o nome de cada membro do staff** para um serviço de terceiros
em cada carregamento do painel (KI-018). Escrevi um provedor local que gera o SVG
em `data:` URI. Autorizar o domínio no CSP teria calado o sintoma e mantido a
fuga. Um controlo posto para conter XSS revelou uma fuga que ninguém procurava.

**Compromisso assumido e documentado.** A política do painel usa `unsafe-eval` e
`unsafe-inline`, porque o Alpine avalia as expressões `x-*` com `new Function()` e
sem isso o painel simplesmente não funciona. É mais fraca do que a do site
público. O que se mantém, e é o que importa, é `default-src 'self'` e
`connect-src 'self'`: um XSS no painel não consegue exfiltrar para fora do
domínio. Reavaliar no Marco 7 (B-045).

**Riscos restantes.**
- A garantia de que o middleware do painel envolve *todas* as rotas futuras vem da
  leitura do código do Filament, não de um teste contra um Resource real — não
  existe nenhum Resource ainda (B-046).
- `is_staff` só não é dívida se o Marco 1 o **substituir** por papéis por empresa e
  cinema, em vez de lhe acrescentar mais bandeiras booleanas.
- Contas de teste criadas durante a verificação foram removidas; a base de
  desenvolvimento está sem utilizadores.

**Próxima tarefa recomendada.** C0.7 — Redis, filas, Horizon com gate e scheduler.

---

## 2026-07-29 — C0.5 · Autenticação de clientes com Fortify

**Marco.** 0 — Fundação. Primeiro ciclo a tocar funcionalidade crítica.

**Tarefa.** Registo, entrada, saída e recuperação de palavra-passe para clientes,
com páginas Inertia escritas de raiz.

**Resultado.** Concluído.

**Implementado.**
- `laravel/fortify` 1.37 com guard `web` partilhado, conforme ADR-006.
- Apenas as funcionalidades que o Marco 0 pede: registo e recuperação. Gestão de
  perfil, 2FA e passkeys desligadas, e as respetivas migrations e ações
  **removidas** — esquema e código sem uso são dívida, não preparação.
- Quatro páginas Inertia escritas de raiz, sem starter kit, com um componente
  `CampoTexto` acessível: `label` associado, `aria-invalid`, `aria-describedby`,
  `role="alert"` no erro, `autocomplete` e alvos de toque de 44 px.
- Limitador de 5 tentativas por minuto por **email combinado com IP**: só por IP
  castigaria utilizadores atrás do mesmo NAT; só por email deixaria qualquer
  pessoa bloquear a conta de outra.
- `HandleInertiaRequests` partilha apenas `id` e `name`. O email nunca entra.
- `lang/pt/` com auth, passwords e as regras de validação em uso.

**Ficheiros principais.**

```text
app/Providers/FortifyServiceProvider.php
app/Http/Responses/RespostaUniformeDeRecuperacao.php
app/Http/Middleware/HandleInertiaRequests.php · ContentSecurityPolicy.php
app/Actions/Fortify/ · config/fortify.php · bootstrap/providers.php
resources/js/Pages/Auth/ (4 páginas) · Layouts/AuthLayout.vue · Components/CampoTexto.vue
resources/js/Pages/Inicio.vue · lang/pt/ · docker-compose.yml
tests/Feature/Auth/ (5 ficheiros)
```

**Testes e verificações — dentro dos containers.**

| Verificação | Resultado |
| --- | --- |
| `composer test` | **58 testes, 277 asserções**, saída 0 |
| `composer check` | saída 0 |
| `npm run lint / type-check / format:check / build` | saída 0 |
| Browser: registo → logout → login → credenciais erradas | percurso completo, **0 erros de consola** |
| Browser em modo build **e** em modo servidor Vite | ambos sem erros |

**Três defeitos reais encontrados, nenhum previsto por mim.**

1. **Enumeração de contas na recuperação.** O Fortify devolve o erro
   `passwords.user` — "não encontramos utilizador com esse endereço" — quando a
   conta não existe. Isso torna o formulário público um oráculo para descobrir
   quem tem conta. Corrigido ligando o contrato
   `FailedPasswordResetLinkRequestResponse` a `RespostaUniformeDeRecuperacao`, que
   devolve a mesma mensagem da de sucesso. A notificação continua a sair só para
   contas reais. Foi o **teste que escrevi que apanhou isto**, não uma revisão.

2. **Serviço `node` morto desde o C0.4** (KI-013), apontado pelo utilizador. O
   `npm ci` do comando do serviço exige lockfile, que não existia no primeiro
   arranque. Passou invisível porque todas as verificações usam
   `docker compose run --rm`, e porque o serviço não tinha `healthcheck` — a
   inconsistência que a revisão do C0.3 classificou como MENOR (B-028). Não era
   menor. Corrigido, e o servidor de desenvolvimento ficou finalmente exercitado.

3. **Mensagens entregues como chaves de tradução** (KI-014). A interface mostrava
   `auth.failed`. Só a verificação em browser o revelou: os meus testes comparavam
   a resposta com `__('auth.failed')`, e sem `lang/pt/` ambos os lados eram a
   mesma string errada. O teste media a igualdade de duas coisas erradas.

**Correção de rumo no CSP.** Ao pôr o servidor do Vite a correr, descobri que o
CSP do C0.4 o bloqueava: em modo de desenvolvimento o Vite injeta CSS por
JavaScript e não conhece o nonce. O browser deu a razão exata — pela
especificação, **um nonce faz o `unsafe-inline` ser ignorado**, pelo que os dois
juntos não funcionam. Em `local` o `style-src` usa `unsafe-inline` sem nonce; fora
de `local` é o inverso. Os testes correm em `testing` e continuam a exigir a
política estrita.

**Revisão independente de segurança.** Obrigatória, por autenticação estar na
lista de funcionalidades críticas. Veredicto: aprovado com correções, sem
achados críticos. O achado decisivo foi factual e contra mim: **o plano deste
ciclo prometia limitador em login, registo e recuperação, e só o login o tinha.**
Confirmei com `php artisan route:list` — `/register`, `/forgot-password` e
`/reset-password` chegavam com `['web', 'guest:web']` e nada mais. O critério de
fecho que eu próprio escrevi não estava cumprido.

| Achado | O que fiz |
| --- | --- |
| Sem limitador em registo, recuperação e redefinição | `LimitarPedidosSensiveis`: 10/min por IP e 5/min por email nos três endpoints, com cinco testes |
| Enumeração de contas ainda aberta no registo | **Aceite explicitamente** com mitigação, não corrigida — ver KI-015 e a justificação abaixo |
| `SESSION_SECURE_COOKIE` sem valor por omissão seguro | Passa a ser seguro fora de `local`/`testing`; variável documentada no `.env.example` |
| Política de palavra-passe com 8 caracteres e sem máximo | Mínimo de 10 e `max:255`, que fecha uma negação de serviço por payload gigante |
| Nome do ambiente exposto a visitantes anónimos | Prop removida da rota, da página e do teste |
| Canal lateral de temporização | **Aceite** — ver KI-016: era o volume ilimitado que o tornava explorável, e o volume acabou |
| Sem prova de invalidação de sessão no logout | `SessaoTest`, que verifica também os atributos reais do `Set-Cookie` |

**Duas decisões, não omissões.** A enumeração no registo fica aceite porque a
alternativa — aceitar sempre e comunicar por email — exige verificação de
endereço, adiada para o Marco 3; fingir sucesso sem enviar nada deixaria o
utilizador legítimo à espera de um email que nunca chega, o que é pior. E
`uncompromised()` não entrou na política de palavra-passe porque introduz uma
chamada HTTP externa num caminho crítico e tornaria a suíte dependente da
internet (B-040).

**Uma asserção minha que era falsa.** Escrevi um teste que comparava constantes
minhas em vez da configuração — tautológico. O PHPStan apanhou-o. Removi-o e
declarei no comentário o que fica sem cobertura, em vez de manter uma asserção
que dava confiança sem a merecer.

**Riscos restantes.**
- Verificação de email adiada para o Marco 3 (B-039).
- Sem auditoria de eventos de autenticação (B-043), exigida pela secção 10.
- A produção com `APP_ENV=local` por engano relaxaria o CSP (B-044, C0.8).
- `lang/pt/validation.php` cobre só as regras em uso (B-029).
- **B-042, condição de fecho do C0.6:** a marca de staff nunca pode entrar em
  `#[Fillable]` do `User`. Uma allowlist é por modelo, não por rota — se a coluna
  for acrescentada ali para conveniência dos formulários do Filament, passa a ser
  atribuível também pelo `/register` público, e o controlo de acesso ao painel
  torna-se decorativo. A promoção tem de passar por uma Action dedicada.

**Próxima tarefa recomendada.** C0.6 — painel Filament em `/admin`, que só fecha
com o teste a provar 403 a não-staff, e que valida KI-004 e KI-005.

---

## 2026-07-28 — C0.4 · Frontend com Vite, Vue 3, TypeScript, Tailwind e Inertia

**Marco.** 0 — Fundação.

**Tarefa.** Servir uma página Inertia com Vue 3 e TypeScript, com build de
produção a funcionar e Node a correr em container.

**Resultado.** Concluído. Toda a cadeia frontend e a suíte PHP passam, dentro dos
containers.

**Implementado.**
- Serviço `node` no Compose sobre `node:24-alpine`, com `node_modules` em volume
  próprio para não atravessar o bind mount, que em macOS degrada muito o I/O.
- `inertiajs/inertia-laravel` 3.2, middleware `HandleInertiaRequests` registado
  no `bootstrap/app.php`, template de raiz `resources/views/app.blade.php`.
- `resources/js/app.ts` com resolução de páginas por `import.meta.glob`, que
  falha alto e com o nome do componente quando a página não existe.
- Página `Inicio.vue`, escrita de raiz, mobile-first. **Não** é a home da secção
  11 do `agent.md`, que depende do catálogo e pertence ao Marco 1.
- `vite.config.ts`, `tsconfig.json` em modo estrito, ESLint 10 em flat config,
  Prettier, e `eslint-config-prettier` a arbitrar entre os dois.
- `welcome.blade.php` e `resources/js/app.js` removidos (B-016).
- Plugin de fontes do esqueleto removido: descarregava 'Instrument Sans' durante
  o build, uma dependência de rede evitável na CI. Pilha de fontes do sistema até
  a identidade tipográfica ser trabalho do Marco 1.

**Ficheiros principais.**

```text
docker-compose.yml (serviço node) · package.json · vite.config.ts · tsconfig.json
eslint.config.js · .prettierrc.json
resources/js/app.ts · resources/js/env.d.ts · resources/js/Pages/Inicio.vue
resources/views/app.blade.php · resources/css/app.css
app/Http/Middleware/HandleInertiaRequests.php · bootstrap/app.php · routes/web.php
tests/Feature/InertiaShellTest.php
docs/decisions/ADR-005-inertia-e-vue-no-frontend.md
```

**Testes e verificações — executados dentro dos containers.**

| Comando | Resultado |
| --- | --- |
| `npm run build` | assets gerados, saída 0 |
| `npm run type-check` | saída 0 |
| `npm run lint` | saída 0, com `--max-warnings=0` |
| `npm run format:check` | saída 0 |
| `composer check` | saída 0 |
| `composer test` | **10 testes, 45 asserções**, saída 0 |
| browser real (Playwright) | **0 erros de consola, 0 page errors** |
| `GET /` | 200, com `component":"Inicio"` no payload Inertia |
| assets servidos | `/build/assets/app-*.js` e `.css` referenciados no HTML |

Os três testes novos verificam que a raiz devolve uma resposta Inertia com o
componente esperado, que o locale é partilhado, e que **nenhum dado de
utilizador é partilhado por omissão** numa página pública — este último existe
porque tudo o que entrar em `share()` viaja em todas as respostas Inertia,
inclusive as anónimas.

**Problemas encontrados durante a execução.**

1. **TypeScript 7 quebra o `vue-tsc`** (KI-011). A 7 é a versão estável atual e o
   `vue-tsc` declara aceitar `>=5.0.0`, mas carrega `typescript/lib/tsc`, caminho
   que a 7 deixou de exportar. Fixei em `^5.9.3`. Tinha dito que tentaria a 7 e
   recuaria se partisse; foi o que aconteceu, e a razão está no ADR-005.
2. `@eslint/js` estava fixado por mim em `^10.8.0`, versão que não existe — a
   última é a 10.0.1. Copiei o número da versão do ESLint, que é outro pacote.
3. O ESLint acusava 16 avisos de indentação e quebras de linha, todos por
   competir com o Prettier. Resolvido por separação de papéis, com
   `eslint-config-prettier` aplicado por último — e não desligando regras à mão.
4. O CSS do esqueleto referenciava 'Instrument Sans' depois de eu remover o
   plugin que a carregava: teria ficado uma referência a uma fonte inexistente.

**Revisão independente.** Um revisor de frontend, que não escreveu o código.
Veredicto: aprovado com correções, com **um bloqueador**.

| Achado | Correção aplicada |
| --- | --- |
| **Bloqueador:** CSP estático do Nginx bloqueava os `<style>` que o Inertia injeta em runtime; erro de consola em todas as visitas (KI-012) | CSP passou para middleware Laravel com nonce por pedido, e o nonce é passado ao `createInertiaApp`. Sem `unsafe-inline` |
| `import.meta.glob` com `eager: true` metia todas as páginas no bundle de entrada | `eager` removido; o build passou a gerar `Inicio-*.js` como chunk próprio |
| Alias `@/*` no tsconfig sem correspondência no Vite — falharia no primeiro uso | `resolve.alias` no `vite.config.ts` |
| `npm run lint` cobria menos ficheiros que o `type-check` | `eslint .`, com os `ignores` já definidos na flat config |
| O critério de fecho não previa verificação em browser | Passou a exigir zero erros de consola, em todos os ciclos de frontend |
| Referência tripla-slash em `env.d.ts` duplicava o `types` do tsconfig | Removida |

**Verificação independente dos achados.** Reproduzi o bloqueador com Playwright
antes de lhe tocar: 1 erro de consola, exatamente como descrito. Fui além do
relatado em dois pontos, ambos apurados por leitura do código instalado:
o `@inertiajs/core` **não** lê a meta tag — exige a opção `nonce` em
`createInertiaApp`; e enquanto Nginx e Laravel enviavam ambos um CSP, o browser
aplicava a interseção e o mais restritivo continuava a bloquear.

**Regressões que eu próprio introduzi ao corrigir, e apanhei na reverificação.**
`node:url` no `vite.config.ts` sem `@types/node` instalado, o que partiu o
`type-check`; e um PHPDoc `@return string` redundante que o Pint recusou. Os
`@types/node` ficaram na linha 24, a condizer com o Node do container — os tipos
devem seguir o runtime, não a versão mais recente.

**Riscos restantes.**
- A verificação em browser é hoje manual; automatizá-la na CI é B-021.
- Coexistência Vite/Livewire ainda por validar; acontece em C0.6 (KI-004).
- `node_modules` não existe no host: editores configurados para o resolver
  localmente não terão tipos nem autocompletar.
- O servidor de desenvolvimento do Vite está configurado mas não foi exercitado;
  o caminho verificado é o do build.
- Preso ao TypeScript 5 até o `vue-tsc` suportar a 7 (B-020).

**Próxima tarefa recomendada.** C0.5 — autenticação de clientes com Fortify e
páginas Inertia próprias, incluindo os testes de rate limiting e de mass
assignment que fecham o ciclo.

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
