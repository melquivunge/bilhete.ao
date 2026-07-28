# ADR-005 — Inertia e Vue no frontend, com Node em container

**Estado.** Aceite
**Data.** 2026-07-28
**Ciclo.** C0.4

---

## Contexto

A secção 2 do `agent.md` fixa Inertia.js, Vue 3, TypeScript, Tailwind e Vite. O
ADR-001 já registou essa escolha ao nível da stack. Falta decidir a **forma
concreta**: como as páginas se resolvem, onde corre o Node, e como o frontend é
verificado.

Três factos condicionam a decisão:

1. O painel administrativo é Filament, que traz Livewire 4 e o seu próprio
   pipeline de assets (KI-004). Os dois têm de coexistir.
2. O host tem Node 26, do canal Current. O LTS ativo é o Node 24.
3. A secção 11 do `agent.md` exige uma interface visualmente original e proíbe
   copiar o layout de plataformas existentes.

## Decisão

**Um único ponto de entrada Inertia** em `resources/js/app.ts`, com as páginas
resolvidas por `import.meta.glob` sobre `resources/js/Pages/`. Uma página que não
exista falha alto, com o nome do componente na mensagem, em vez de devolver
`undefined` e rebentar mais à frente sem contexto.

**O template de raiz é `resources/views/app.blade.php`**, e é o único ficheiro
Blade do site público. Todo o resto é Vue.

**Node corre em container**, num serviço `node` do Compose sobre `node:24-alpine`,
com `node_modules` em volume próprio. O host tem Node 26, mas isso é irrelevante:
tal como no PHP, o que conta é o que corre no container. Sem este serviço, nada
impediria alguém de correr `npm run dev` no Node do host e reintroduzir a
divergência que evitámos para o PHP.

**Separação de responsabilidades nas ferramentas:** o ESLint trata de correção, o
Prettier de formatação. O `eslint-config-prettier` é aplicado por último e desliga
as regras de estilo do ESLint que competiriam com o Prettier.

**TypeScript fica na linha 5.x**, e não na 7, que é a versão estável mais recente.
O `vue-tsc` 3.3 declara aceitar `typescript >= 5.0.0`, mas na prática falha com a
7, que removeu `./lib/tsc` dos seus exports:
`ERR_PACKAGE_PATH_NOT_EXPORTED`. Verificado por execução, não presumido.

**Sem fontes externas.** O esqueleto do Laravel carregava 'Instrument Sans' por um
plugin que a descarrega durante o build. Foi removido: a identidade tipográfica é
trabalho do Marco 1, e uma dependência de rede em tempo de build é um modo de
falha evitável na CI.

## Alternativas consideradas

**Blade com Livewire também no site público**, aproveitando o que o Filament já
traz e dispensando um segundo pipeline. Rejeitada: o `agent.md` fixa Inertia e
Vue, e a seleção visual de assentos do Marco 2 é interação de cliente com estado
suficiente para justificar um framework de componentes a sério.

**Vue com renderização no servidor (SSR).** Rejeitada por agora: acrescenta um
processo Node em produção e complexidade de deploy que o Marco 0 não precisa de
suportar. Reavaliar se o SEO das páginas de filmes o exigir, no Marco 1.

**Node no host, sem serviço no Compose.** Mais simples de arrancar. Rejeitada pela
razão que motivou o ADR-007: divergência silenciosa de versões entre quem
desenvolve, a CI e a produção.

## Consequências

Aceitamos:

- dois pipelines de assets no mesmo projeto, Vite/Vue e Livewire/Filament, a
  validar em C0.6 (KI-004);
- ficar na linha 5 do TypeScript até o `vue-tsc` suportar a 7 (B-020);
- que `node_modules` não exista no host, o que quebra editores configurados para
  o resolver localmente.

Ganhamos:

- uma só forma de escrever páginas, sem mistura de Blade e Vue no site público;
- verificação real do frontend: `type-check`, `lint`, `format:check` e `build`,
  todos a correr em container;
- controlo total sobre o HTML, sem herdar layout de nenhum starter kit —
  requisito de originalidade visual da secção 11.

## Fronteira

A página `Inicio.vue` deste ciclo é um shell de verificação. **Não** é a home da
secção 11 do `agent.md` — seletor de localização, hero, filmes em cartaz,
cinemas, rodapé institucional — que depende do catálogo e pertence ao Marco 1.
