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

**Âmbito.** Esta entrada cobre **apenas assets**. O risco de coexistência de rotas
e autenticação está em KI-005.

**Resolução.** Confirmado em C0.6 por teste: o painel não carrega
`resources/js/app.ts` nem o payload `data-page=` do Inertia, e o site público não
carrega assets do Filament — `tests/Feature/Admin/CoexistenciaComOSitePublicoTest.php`.
Os dois pipelines convivem sem se pisarem.

**Estado.** Resolvido.

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

**Resolução.** Confirmado em C0.6 por teste: nenhuma rota é registada duas vezes
(a asserção percorre toda a tabela de rotas), as duas entradas coexistem com
componentes distintos, e autenticar-se pelo site público **não** dá acesso ao
painel — `tests/Feature/Admin/CoexistenciaComOSitePublicoTest.php`.

**Estado.** Resolvido.

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

## KI-022 — Verificar dentro do container NÃO protege contra diferenças de sistema de ficheiros

**Observado em.** C0.9, 2026-07-30, pela CI no GitHub.

**Sintoma.** Cinco testes falhavam na CI com
`Inertia page component file [Inicio] does not exist.` Localmente passavam todos.

**Causa.** O `inertia-laravel` procura os componentes em
`resource_path('js/pages')`, em minúscula. O diretório do projeto era `Pages`.

**O que torna isto importante, para além do bug.** O ADR-007 afirma que "a
verificação que conta é a executada dentro do container". Isso é verdade para
versões de PHP, extensões e dependências — e **falso para o sistema de
ficheiros**. Um bind mount de macOS herda a insensibilidade a maiúsculas do
volume de origem, mesmo visto de dentro de um container Linux.

Cheguei a clonar o repositório de raiz para `/tmp` e a correr o teste dentro do
container: **passou na mesma**. A causa só apareceu ao ler o valor por omissão
dentro de `vendor/`.

**Correção.** Diretório renomeado para `resources/js/pages`, alinhando com a
convenção do pacote, em vez de publicar `config/inertia.php` só para contrariar o
valor por omissão.

**Consequência para o método.** Diferenças de sistema de ficheiros — sensibilidade
a maiúsculas, permissões, ligações simbólicas — não são detetáveis em
desenvolvimento nesta máquina. Só a CI as apanha. É mais uma razão para o C0.9
não ser opcional, e para nenhuma alteração que dependa de nomes de ficheiros ser
declarada verificada antes de a CI passar.

**Estado.** Resolvido.

---

## KI-021 — Redação de logs configurada mas sem efeito no canal predefinido

**Observado em.** C0.8, 2026-07-30, ao inspecionar `storage/logs/laravel.log`.

**Sintoma.** Com o processador de redação declarado em `single`, `daily` e
`stderr`, um `Log::info('teste', ['password' => '...'])` continuava a escrever a
palavra-passe **em claro** no ficheiro.

**Causa.** O canal predefinido é o `stack`, e o `stack` agrega apenas os
*handlers* dos sub-canais. Os `processors` declarados nos sub-canais nunca chegam
a ser aplicados quando o registo passa pelo stack.

**Porque o meu teste não apanhou.** O teste verificava que a classe constava de
`config('logging.channels.X.processors')`. Passava — e a redação não acontecia.
**Configuração presente não é comportamento verificado.** É a mesma falha do
teste do CSP do Horizon (KI-019), no mesmo marco.

**Correção.** A redação passa a ser aplicada por *tap*
(`App\Logging\AplicarRedacao`), que o LogManager aplica a qualquer canal
resolvido, incluindo o stack. O teste passa a asserir o que sai do logger, e o
helper de teste aplica o mesmo tap — senão estaria a medir um logger que não
existe na aplicação.

**Estado.** Resolvido, verificado no ficheiro de log real.

---

## KI-019 — Painel do Horizon partido pela política CSP do site

**Observado em.** C0.7, 2026-07-30, em verificação no browser.

**Sintoma.** `/horizon` respondia 200 a um utilizador staff, mas o corpo da página
ficava **vazio**. Na consola: um script inline bloqueado por `script-src` com
nonce, e uma folha de estilo de `fonts.bunny.net` bloqueada.

**Causa.** O Horizon registra as suas rotas com o grupo `web` por nome, pelo que
herdou o `ContentSecurityPolicy` do site — o inverso do Filament, que substitui o
grupo (KI-017). Mas a política do site usa nonce em `script-src`, e o Horizon
executa um script inline com a sua configuração. Um nonce não cobre um script
inline que não o transporta.

**Porque o meu teste não apanhou.** Eu tinha escrito um teste a exigir a presença
do cabeçalho CSP em `/horizon`, e ele passava. Verificava que a política existia,
não que o painel funcionava. **Presença de um controlo não é o mesmo que
compatibilidade com o que ele protege.**

**Correção.** O middleware passa a detetar o caminho do Horizon e a aplicar a
política dos painéis. A deteção é feita no middleware, e não acrescentando outro
ao `config/horizon.php`, porque dois cabeçalhos CSP na mesma resposta são
aplicados como interseção — o painel ficaria bloqueado pela mais restritiva.

**Estado.** Resolvido, com teste que assere a política correta, não só a sua
existência.

---

## KI-020 — Horizon carregava uma fonte de fonts.bunny.net

**Observado em.** C0.7, 2026-07-30, **pelo CSP**, exatamente como KI-018.

**Sintoma.** O layout do Horizon incluía `<link>` para
`https://fonts.bunny.net/css?family=figtree`.

**Impacto.** O IP e o referer de cada membro do staff saíam para um terceiro em
cada carregamento do painel, e o painel dependia de um host externo.

**Correção.** Layout sobreposto em `resources/views/vendor/horizon/layout.blade.php`,
sem a fonte externa, com a pilha de fontes do sistema. Autorizar o domínio no CSP
teria calado o sintoma e mantido a chamada.

**Padrão que já se repetiu duas vezes.** Filament com `ui-avatars.com`, Horizon
com `fonts.bunny.net`. Pacotes administrativos assumem acesso à internet e a
terceiros. Vale a pena verificar isto em cada dependência de interface que entrar
no projeto — e o CSP é o instrumento que o revela.

**Estado.** Resolvido, com teste.

---

## KI-017 — O painel administrativo esteve sem Content-Security-Policy

**Observado em.** C0.6, 2026-07-30, por revisão independente de segurança.

**Sintoma.** `curl -I /admin/login` não devolvia cabeçalho
`Content-Security-Policy` nenhum, enquanto `/` devolvia.

**Causa.** O array passado a `->middleware()` do painel Filament **substitui** o
grupo `web` do Laravel, não o herda. O `ContentSecurityPolicy` foi anexado em
`bootstrap/app.php` com `$middleware->web(append: ...)`, e por isso nunca chegava
às rotas do painel, que o Filament registra com a sua própria lista.

**Porque a verificação em browser não apanhou.** No C0.6 corri o painel num
browser real e obtive zero erros de consola — o que era verdade e não provava
nada: não existia política para violar. Um controlo ausente é silencioso por
natureza; só um teste que exija a sua presença o deteta.

**Correção.** `ContentSecurityPolicy::class.':painel'` acrescentado à lista de
middleware do painel, com uma política própria. O painel precisa de
`unsafe-eval` e `unsafe-inline` porque o Alpine, que o Filament usa, avalia as
expressões `x-*` com `new Function()` — sem isso o painel deixa de funcionar. O
que se mantém, e é o essencial, é `default-src 'self'` e `connect-src 'self'`: um
XSS no painel não consegue exfiltrar para fora do domínio.

**Consequência a lembrar.** Middleware acrescentado ao grupo `web` **não** chega
ao painel sem ser duplicado no `AdminPanelProvider`. A sessão é partilhada; o
middleware não é. Está escrito em comentário no próprio ficheiro.

**Estado.** Resolvido, com teste em `tests/Feature/Admin/SegurancaDoPainelTest.php`.

---

## KI-018 — Filament enviava o nome do staff para ui-avatars.com

**Observado em.** C0.6, 2026-07-30, **pelo CSP que acabara de ser acrescentado**.

**Sintoma.** Com o CSP ativo no painel, o browser bloqueou quatro pedidos a
`https://ui-avatars.com/api/?name=...`.

**Causa.** O provedor de avatar por omissão do Filament constrói o avatar pedindo
uma imagem a esse serviço, passando o nome do utilizador no URL.

**Impacto.** O nome de cada membro do staff saía para um terceiro em cada
carregamento de página do painel, e o painel ficava dependente de um host externo
para renderizar. Dados pessoais a atravessar a fronteira da plataforma sem
necessidade.

**Correção.** `App\Filament\Avatares\AvatarLocalComIniciais` gera o avatar como
SVG em `data:` URI. Sem rede, sem terceiros. Autorizar o domínio no CSP teria
silenciado o sintoma e mantido a fuga.

**Nota.** Este achado não estava em nenhum relatório: apareceu porque um controlo
de segurança posto para conter XSS revelou uma fuga que ninguém tinha procurado.

**Estado.** Resolvido, com teste.

---

## KI-015 — Enumeração de contas pelo formulário de registo: risco aceite

**Observado em.** C0.5, 2026-07-29, por revisão independente de segurança.

**Sintoma.** `POST /register` com um email já registado devolve erro de validação
("já está em uso"); com um email novo, cria a conta. A diferença é um oráculo de
existência de conta.

**Estado: aceite, não corrigido.** Isto é uma decisão, não um esquecimento.

**Porquê.** O login e a recuperação foram fechados porque nada se perde ao
uniformizar as respostas. No registo, a alternativa seria aceitar sempre o pedido
e comunicar por email que a conta já existe — o que exige um transporte de email
real e verificação de endereço, ambos adiados para o Marco 3 por decisão anterior.
Fingir sucesso sem enviar nada deixaria o utilizador legítimo à espera de um email
que nunca chega, o que é pior do que o oráculo.

**Mitigação em vigor.** `LimitarPedidosSensiveis` limita `POST /register` a dez
pedidos por minuto por IP e cinco por email. Não elimina a enumeração; torna-a
lenta o suficiente para deixar de ser prática à escala de uma lista.

**Reavaliar.** No Marco 3, quando existir verificação de email: aí a via
"aceitar sempre e notificar" passa a ser realizável, e esta entrada deve ser
revisitada. Registado como B-041.

---

## KI-016 — Canal lateral de temporização em login e recuperação: risco aceite

**Observado em.** C0.5, 2026-07-29, por revisão independente.

**Sintoma.** As mensagens são idênticas, mas os tempos não: num login com email
inexistente o `Hash::check` nunca corre; numa recuperação com conta existente há
gravação de token e construção de notificação. A diferença é mensurável.

**Estado: aceite, com mitigação.** Igualar tempos com precisão exige trabalho
artificial em todos os caminhos e nunca fica perfeito. O que tornava este canal
explorável era ter amostras ilimitadas — e isso deixou de existir: os três
endpoints passaram a ter limitador. Sem volume, promediar o ruído de rede deixa
de ser prático.

**Reavaliar.** No Marco 7, na revisão de segurança anterior ao piloto (B-034).

---

## KI-013 — Serviço `node` morto durante um dia sem que nada o assinalasse

**Observado em.** C0.5, 2026-07-29, apontado pelo utilizador a partir do Docker
Desktop.

**Sintoma.** O container `bilhete-ao-node-1` estava parado desde o C0.4. Os logs
mostravam `npm error` seguido de `sh: vite: not found`.

**Causa.** O comando do serviço era
`[ -d node_modules/.bin ] || npm ci; exec npm run dev`. No primeiro arranque do
projeto ainda não existia `package-lock.json`, o `npm ci` falhou — exige lockfile
— e o `npm run dev` seguinte não encontrou o Vite. O container saiu e ficou assim.

**Porque passou despercebido.** Todas as verificações do C0.4 e do C0.5 correram
com `docker compose run --rm node`, que cria um container efémero e não usa o
serviço de longa duração. Os resultados eram reais; o serviço é que estava morto.
E, ao contrário dos outros quatro serviços, o `node` não tinha `healthcheck` — a
revisão do C0.3 apontou essa inconsistência como MENOR (B-028). Não era menor: era
a diferença entre um serviço morto ser visível ou invisível.

**Correção.** `npm install` em vez de `npm ci`, condicionado à ausência do
binário do Vite, e `healthcheck` que interroga `/@vite/client`. Verificado: o
serviço passa a `healthy` e o servidor de desenvolvimento responde — caminho que
o C0.4 tinha deixado como risco não exercitado.

**Lição aplicada.** Um serviço sem healthcheck não é uma inconsistência
cosmética. E verificar com containers efémeros não verifica o serviço declarado.

**Estado.** Resolvido.

---

## KI-014 — Mensagens entregues ao utilizador como chaves de tradução

**Observado em.** C0.5, 2026-07-29, em verificação no browser.

**Sintoma.** Ao recusar um login, a interface mostrava literalmente `auth.failed`.
O mesmo aconteceria com `passwords.sent` e com todas as regras de validação.

**Causa.** O locale da aplicação é `pt` e o Laravel 11+ não traz ficheiros de
idioma; sem `lang/pt/`, o tradutor devolve a própria chave.

**Porque nenhum teste apanhou.** Os testes comparavam a resposta com
`__('auth.failed')`. Sem tradução, ambos os lados eram a string `auth.failed` e a
asserção passava — o teste media a igualdade de duas coisas erradas. O teste de
enumeração continuava válido (as mensagens eram de facto iguais), mas nada
provava que fossem legíveis.

**Correção.** `lang/pt/auth.php`, `lang/pt/passwords.php` e `lang/pt/validation.php`
com as regras em uso. Novos testes assertam as mensagens **no payload Inertia**,
que é o que o browser recebe, e recusam qualquer mensagem que comece por
`auth.`, `validation.` ou `passwords.`.

**Limitação assumida.** `validation.php` cobre as regras usadas até aqui, não as
mais de cem do Laravel. O que falta recai no fallback em inglês — visível, e
portanto corrigível. Ver B-029.

**Estado.** Resolvido para os fluxos de autenticação.

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
