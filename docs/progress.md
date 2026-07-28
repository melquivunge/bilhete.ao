# Progresso

Registo cronológico dos ciclos executados. Entrada mais recente no topo.

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
`pint --test`, `phpstan analyse` e `php artisan test` a passar.
