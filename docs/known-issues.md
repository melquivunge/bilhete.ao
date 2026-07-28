# Problemas e limitações conhecidas

Apenas factos observados. Nada nesta lista é hipotético.

---

## KI-001 — Daemon do Docker parado

**Observado em.** C0.1, 2026-07-28.

**Sintoma.** `docker info` devolve
`Cannot connect to the Docker daemon at unix:///Users/melquiantonio/.docker/run/docker.sock`.
O Docker CLI 29.4.0 e o Compose v2.38.1 estão instalados.

**Impacto.** Sem daemon não há PostgreSQL nem Redis: o host não tem `psql`
instalado. Bloqueia o ciclo C0.3 e todas as verificações que dependem de base de
dados.

**Contorno.** Arrancar o Docker Desktop antes dos ciclos C0.3 em diante. Se não
arrancar, o ciclo é declarado `BLOCKED_TECHNICAL` e nenhuma verificação
dependente é declarada como executada.

**Estado.** Aberto.

---

## KI-002 — Divergência entre o PHP do host e o do container

**Observado em.** C0.1, 2026-07-28.

**Sintoma.** O host corre PHP 8.5.6. A imagem-alvo é PHP 8.4, escolhida por
margem de estabilidade face a pacotes de terceiros.

**Impacto.** Comportamento e resultados de testes podem divergir entre host e
container, sobretudo em depreciações introduzidas no 8.5.

**Contorno.** A verificação que conta é a executada dentro do container. A CI fixa
PHP 8.4. Resultados obtidos no host são declarados como tal em `progress.md`.

**Estado.** Aberto, aceite.

---

## KI-003 — Remoto Git não configurado

**Observado em.** C0.1, 2026-07-28.

**Sintoma.** `repositorio.md` indica `git@github.com:melquivunge/bilhete.ao.git`,
mas o repositório local não tinha Git inicializado e não tem remoto configurado.

**Impacto.** A CI do ciclo C0.9 não pode ser validada no GitHub enquanto isso não
acontecer; os passos terão de ser executados localmente e declarados como tal.

**Contorno.** Ver B-010 no backlog.

**Estado.** Aberto.

---

## KI-004 — Coexistência por validar entre Vite/Inertia e Livewire/Filament

**Observado em.** C0.1, 2026-07-28. Risco identificado por análise das
dependências, ainda não reproduzido.

**Sintoma.** Filament 5 depende de Livewire 4.1, com o seu próprio pipeline de
assets, a conviver com o pipeline Vite/Vue do site público.

**Impacto.** Potencial conflito de assets ou de rotas de autenticação entre os
dois pipelines.

**Contorno.** Manter os pipelines separados e validar explicitamente em C0.6.

**Âmbito.** Esta entrada cobre **apenas assets**. O risco de coexistência de rotas
e autenticação está em KI-005.

**Estado.** Por confirmar.

---

## KI-005 — Coexistência por validar entre as rotas do Fortify e as do Filament

**Observado em.** C0.1, 2026-07-28. Risco identificado por análise, ainda não
reproduzido.

**Sintoma.** Fortify regista rotas de autenticação na raiz (`/login`, `/register`)
e o painel Filament regista as suas sob `/admin`. Ambos assentam no guard `web`,
por decisão do ADR-006.

**Impacto.** Não é esperada colisão de caminhos. O risco real é de comportamento:
redirecionamentos após login a apontarem para o público errado, ou throttling de
um fluxo a afetar o outro.

**Contorno.** Confirmar em C0.6 com testes que cubram os dois fluxos de login em
simultâneo, incluindo o destino do redirecionamento de cada um.

**Estado.** Por confirmar.

---

## KI-006 — `checkModelProperties` do Larastan desligado

**Observado em.** C0.2, 2026-07-28.

**Sintoma.** Com `checkModelProperties: true`, o Larastan exige que
`UserFactory::definition()` declare `@return array<model property of \App\Models\User, mixed>`.
Ao usar essa sintaxe, o PHPStan responde
`PHPDoc tag @return has invalid value: Unexpected token "property", expected '>'`.
Nesta combinação Larastan 3.10 / PHPStan 2, o parser de PHPDoc não aceita o tipo
que a própria verificação exige.

**Impacto.** As propriedades de modelos não são verificadas estaticamente. Hoje o
impacto é nulo — o único modelo é o `User` do esqueleto e não há migrations
aplicadas. A partir do Marco 1, com modelos reais, passa a ser uma lacuna real.

**Contorno.** `checkModelProperties: false` no `phpstan.neon`, com a justificação
escrita no próprio ficheiro. Não foram usados `@phpstan-ignore` nem baseline.

**Resolução.** B-015: reativar no Marco 1, quando existirem modelos e migrations
reais, reavaliando a sintaxe com as versões então instaladas.

**Estado.** Aberto, contornado.

---

## KI-007 — PHPStan excede o limite de memória por omissão do PHP

**Observado em.** C0.2, 2026-07-28.

**Sintoma.** `phpstan analyse` termina com
`Allowed memory size of 134217728 bytes exhausted`, com o `memory_limit=128M` do
`php.ini` do host.

**Impacto.** Sem limite explícito, a análise falha ou passa consoante a máquina,
o que torna a verificação não reprodutível.

**Contorno.** `--memory-limit=1G` fixado no script `composer analyse`. O `php.ini`
do container define um limite compatível em C0.3.

**Estado.** Resolvido por configuração.

---

## KI-012 — CSP estático bloqueava os estilos injetados pelo Inertia

**Observado em.** C0.4, 2026-07-28, por revisão independente e reproduzido por
mim num browser real.

**Sintoma.** A página renderizava e todas as verificações passavam, mas a consola
do browser registava, em todas as visitas:

```text
Applying inline style violates the following Content Security Policy
directive 'default-src 'self''
```

**Causa.** O CSP era um `add_header` estático do Nginx. A barra de progresso do
Inertia cria um `<style>` em runtime, e o Vite em modo de desenvolvimento faz o
mesmo. Sem nonce, `default-src 'self'` bloqueia-os. O Nginx não consegue gerar um
nonce por pedido.

Duas descobertas durante a correção, ambas por leitura do código instalado e não
por suposição:

1. Pôr `<meta name="csp-nonce">` no HTML **não basta**. O `@inertiajs/core` lê o
   nonce de `config.get('nonce')`, alimentado pela opção `nonce` de
   `createInertiaApp`. É preciso passar-lho explicitamente.
2. Enquanto o Nginx e o Laravel enviavam ambos um cabeçalho CSP, o browser
   aplicava a interseção dos dois, e o mais restritivo continuava a bloquear.

**Correção.** CSP emitido por `app/Http/Middleware/ContentSecurityPolicy.php`,
com nonce por pedido via `Vite::useCspNonce()`, e o nonce passado ao
`createInertiaApp`. `unsafe-inline` **não** foi usado: resolveria o sintoma
desativando a proteção que o CSP existe para dar. Os cabeçalhos que não dependem
do pedido continuam no Nginx.

**Verificação.** Zero erros de consola em browser real, e quatro testes em
`tests/Feature/ContentSecurityPolicyTest.php`, incluindo um que compara o nonce
do cabeçalho com o publicado no HTML.

**Lição aplicada.** O critério de fecho dos ciclos de frontend passou a exigir
carregar a página num browser e não tolerar erros de consola. `build`,
`type-check`, `lint` e a suíte Pest passaram todos com este defeito presente.

**Estado.** Resolvido.

---

## KI-011 — `vue-tsc` incompatível com TypeScript 7

**Observado em.** C0.4, 2026-07-28.

**Sintoma.** Com `typescript@7.0.2`, o `npm run type-check` falha antes de
verificar seja o que for:

```text
Error [ERR_PACKAGE_PATH_NOT_EXPORTED]: Package subpath './lib/tsc' is not
defined by "exports" in node_modules/typescript/package.json
```

**Causa.** O `vue-tsc` 3.3.8 declara `peerDependencies: { typescript: '>=5.0.0' }`,
o que sugere compatibilidade com a 7. Na prática carrega `typescript/lib/tsc`,
caminho que a linha 7 deixou de exportar. A declaração de peer está otimista.

**Impacto.** Nenhuma verificação de tipos seria possível. Descoberto por execução;
se o `type-check` não fizesse parte do critério de fecho do ciclo, teria passado
despercebido até alguém confiar em tipos que nunca foram verificados.

**Contorno.** `typescript` fixado em `^5.9.3`, a última estável da linha 5.
Registado em ADR-005 e no backlog como B-020.

**Estado.** Aberto a montante, contornado.

---

## KI-009 — `.gitignore` por subpasta do Laravel perdidos no C0.2, com cache de views commitado

**Observado em.** C0.3, 2026-07-28, por revisão independente. Regressão
introduzida no C0.2.

**Sintoma.** `git ls-files storage/` devolvia quatro ficheiros de cache de views
Blade compiladas, commitados em `8be80e2`. `find storage -name .gitignore` não
devolvia nada.

**Causa.** Ao integrar o esqueleto no C0.2, passei `--exclude '.gitignore'` ao
rsync para proteger o `.gitignore` da raiz escrito no C0.1. Esse padrão não é
ancorado à raiz: excluiu os doze `.gitignore` que o Laravel coloca dentro de
`storage/`, `bootstrap/cache` e `database/`, cada um com `*` e `!.gitignore`.

**Impacto.** Duplo, e ambos reais. Artefactos de runtime entravam no histórico a
cada `git add -A` — hoje inofensivos, mas a partir do Marco 3 `storage/logs/`
passa a conter traces com payloads de webhook e respostas de gateway. E num clone
limpo o Git não recriaria as pastas de `storage/`, o que quebraria o critério de
fecho do C0.10 antes sequer de a aplicação arrancar.

**Correção.** Os doze `.gitignore` foram repostos a partir do esqueleto e os
quatro ficheiros removidos do índice com `git rm --cached`. Verificado:
`git ls-files storage/` devolve zero.

**Lição aplicada.** Um padrão de exclusão não ancorado aplica-se a todas as
profundidades. A verificação a fazer depois de integrar código externo é
`git ls-files`, não a inspeção visual da raiz.

**Estado.** Resolvido.

---

## KI-010 — Healthcheck do nginx falhava por resolução IPv6

**Observado em.** C0.3, 2026-07-28.

**Sintoma.** O serviço `nginx` ficava `unhealthy` enquanto respondia 200 a partir
do host. Dentro do container, `wget http://localhost/up` devolvia
`can't connect to remote host: Connection refused`.

**Causa.** `localhost` resolve primeiro para `::1`, e a diretiva `listen 80` do
Nginx só abre socket IPv4.

**Correção.** O healthcheck usa `http://127.0.0.1/up`. Verificado: o serviço passa
a `healthy`.

**Estado.** Resolvido.

---

## KI-008 — `php artisan test` sai com código 1 mesmo quando os testes passam

**Observado em.** C0.2, 2026-07-28.

**Sintoma.** `php artisan test` imprime
`{"tool":"pest","result":"passed","tests":3,"passed":3,...}` e termina com código
de saída 1. `./vendor/bin/pest` termina com 0 sobre exatamente os mesmos testes.

**Causa.** O comando `test` é fornecido pelo `nunomaduro/collision`, que invoca o
Pest com `--no-output`. Reproduzido isoladamente:

```text
php vendor/pestphp/pest/bin/pest --no-output --configuration=phpunit.xml   → 1
php vendor/pestphp/pest/bin/pest --configuration=phpunit.xml               → 0
```

Com Pest 5, `--no-output` faz o processo sair com 1 sem emitir erro. O Collision
instalado é o v8.9.5, a versão mais recente publicada, pelo que não existe
correção a montante disponível.

**Impacto.** Alto se passasse despercebido: qualquer pipeline que use
`php artisan test` falharia permanentemente, com o ecrã a dizer que os testes
passaram. Inversamente, um pipeline que ignorasse o código de saída aceitaria
testes vermelhos.

**Contorno.** O comando canónico de testes é `composer test`, que invoca `pest`
diretamente. `php artisan test` não é usado em lado nenhum: nem em scripts, nem
na documentação, nem na CI de C0.9.

**Estado.** Aberto a montante, contornado localmente. Reavaliar quando o Collision
ou o Pest publicarem versão que o corrija.
