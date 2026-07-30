# ADR-007 — Docker como ambiente canónico de desenvolvimento e CI

**Estado.** Aceite
**Data.** 2026-07-28
**Ciclo.** C0.1

---

## Contexto

A inspeção do ambiente em C0.1 mostrou que a máquina de desenvolvimento tem PHP
8.5.6, Composer, Node 26 e o Docker CLI instalados, mas **não tem `psql`** e o
daemon do Docker estava parado (KI-001).

Sem Docker não existe PostgreSQL nem Redis. E sem PostgreSQL não é possível
verificar aquilo que define o produto: a secção 3 do `agent.md` estabelece o
PostgreSQL como fonte de verdade da disponibilidade dos assentos, e o Marco 2
depende de bloqueio pessimista (`SELECT ... FOR UPDATE`).

## Decisão

O ambiente canónico é o Docker Compose. Migrations, testes e verificações que
dependem de infraestrutura contam apenas quando executados dentro dos containers.

Serviços: `app` (PHP 8.4-FPM), `nginx`, `postgres` (17), `redis` (8) e, a partir
de C0.7, `horizon`.

A CI usa as mesmas versões de PHP, PostgreSQL e Redis que o Compose.

Quando o daemon estiver parado, o agente pode arrancá-lo. Se não for possível
arrancá-lo, o ciclo é declarado `BLOCKED_TECHNICAL` e as verificações dependentes
são registadas como **não executadas**, com o risco explícito. Nenhum resultado é
inferido ou simulado.

## Alternativas consideradas

**Híbrido com SQLite para testes locais rápidos, PostgreSQL só na CI.** Daria
feedback mais rápido no host. **Rejeitada, e é a alternativa mais perigosa das
três:** o SQLite não implementa bloqueio pessimista de linha da mesma forma, pelo
que os testes de concorrência do Marco 2 passariam localmente sem provar nada.
Testes que dão falsa confiança sobre reserva dupla de assentos são piores do que
não ter testes.

**Instalar PostgreSQL e Redis nativamente no host.** Evitaria a dependência do
daemon. Rejeitada: divergiria da produção, obrigaria a manter duas configurações
e não resolve a divergência de versão do PHP.

## Consequências

Aceitamos:

- depender do Docker Desktop estar a correr para qualquer verificação séria;
- ciclos mais lentos do que executar diretamente no host;
- gerir permissões de ficheiros entre macOS e containers.

Ganhamos:

- paridade entre desenvolvimento, CI e a futura produção;
- semântica real de PostgreSQL desde o primeiro dia, incluindo transações e
  bloqueio pessimista;
- Redis com o comportamento real de filas e locks.

## Limite explícito desta decisão

A imagem que existe hoje tem um único stage utilizável, `development`, e é a que
**menos** deve ir para produção: monta todo o projeto em leitura-escrita, incluindo
`.git` e o `.env` real, e traz `git`, `unzip` e o Composer na camada final.

Declarar o Docker "ambiente canónico" **não** autoriza reutilizar este
`docker-compose.yml` ou este stage em produção. A imagem de produção — sem bind
mount, com `COPY` do código, `composer install --no-dev --optimize-autoloader`,
sem ferramentas de build na camada final — é trabalho do Marco 7 e terá a sua
própria decisão registada (B-017).

## Limite descoberto em C0.9

Esta decisão dá paridade de **software**: mesma versão de PHP, mesmas extensões,
mesmas dependências. Não dá paridade de **sistema de ficheiros**.

Um bind mount herda as propriedades do volume de origem. Em macOS isso significa
insensibilidade a maiúsculas, mesmo dentro de um container Linux — e foi assim
que um diretório `Pages` onde o pacote esperava `pages` passou por toda a
verificação local, incluindo um clone limpo corrido dentro do container (KI-022).

Portanto: "verificado no container" não cobre nomes de ficheiros, permissões nem
ligações simbólicas. Para essas, a autoridade é a CI.

## Consequência operacional

Qualquer entrada em `docs/progress.md` que reporte testes deve declarar **onde**
foram executados: host ou container. Resultados obtidos no host não são
apresentados como validação do comportamento em PostgreSQL.
