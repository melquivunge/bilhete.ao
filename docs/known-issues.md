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
