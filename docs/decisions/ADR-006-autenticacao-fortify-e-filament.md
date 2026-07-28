# ADR-006 — Autenticação separada: Fortify para clientes, Filament para staff

**Estado.** Aceite
**Data.** 2026-07-28
**Ciclo.** C0.1

---

## Contexto

O Marco 0 exige autenticação, mas o sistema tem dois públicos com riscos
diferentes:

- **clientes**, que compram bilhetes no site público em Inertia/Vue;
- **staff** — operadores de cinema e administradores — que usam o painel Filament
  e acedem a dados de vendas, sessões e, mais tarde, pagamentos e reembolsos.

A secção 11 do `agent.md` exige uma interface visualmente original e proíbe
copiar layouts existentes. A secção 10 exige isolamento entre empresas e cinemas e
que um funcionário não consiga obter privilégios de administrador.

## Decisão

Dois caminhos de autenticação distintos, sobre o mesmo modelo `User`.

**Clientes.** Laravel Fortify fornece o backend — registo, login, logout,
recuperação de palavra-passe, throttling. As páginas são escritas por nós em
Inertia, Vue 3 e TypeScript.

**Staff.** O painel Filament usa o seu próprio fluxo de autenticação em `/admin`,
com sessão administrativa separada e `canAccessPanel()` a **negar por omissão**.

Nenhum starter kit é instalado.

**Arquitetura de guards, fixada agora e não em C0.6.** Clientes e staff partilham
o guard `web` e o modelo `User`. Não são criados guards nem providers separados.

A fronteira entre os dois públicos é de **autorização**, não de autenticação:
`canAccessPanel()` nega por omissão e só concede a quem for explicitamente staff.

Escolhemos autorização em vez de guards separados porque um segundo guard sobre a
mesma tabela de utilizadores dá uma sensação de separação que não corresponde a
uma fronteira real — o mesmo registo continua a servir os dois públicos — e
multiplica a configuração de sessão, throttling e recuperação de palavra-passe
sem reduzir o risco. Uma única regra de negação, coberta por teste, é mais fácil
de auditar do que duas configurações paralelas que podem divergir.

A consequência é que a negação por omissão passa a ser o único ponto de falha
entre um cliente e o painel administrativo. É por isso que o teste que a prova é
condição de fecho de C0.6, e não um extra.

No Marco 0, a distinção entre cliente e staff é mantida no mínimo necessário para
negar acesso ao painel. O modelo completo de papéis e permissões por empresa e
cinema é do Marco 1 e está registado como B-020.

## Alternativas consideradas

**Starter kit Vue com Inertia e TypeScript.** Traria login, registo e recuperação
prontos, poupando trabalho em C0.5. Rejeitada: instala layout e componentes
genéricos que teriam de ser reescritos para cumprir o requisito de originalidade
visual, e o custo de os remover depois é maior do que o de escrever as páginas
agora.

**Apenas autenticação do Filament no Marco 0, adiando a de cliente.** Menor
superfície imediata. Rejeitada: deixaria o Marco 0 incompleto face ao `agent.md`,
que lista autenticação como entregável, e empurraria o risco para o Marco 3, onde
já haveria checkout a depender dele.

**Um único fluxo de autenticação para clientes e staff.** Rejeitada: junta numa
só superfície dois níveis de privilégio muito diferentes e torna mais fácil um
erro de autorização escalar de cliente a administrador.

## Consequências

Aceitamos:

- escrever e manter as páginas de autenticação do cliente;
- gerir a coexistência entre as rotas do Fortify, na raiz, e as do Filament, sob
  `/admin` — sem colisão esperada, mas por confirmar em C0.6 (KI-005);
- que, partilhando guard, um erro em `canAccessPanel()` expõe o painel a
  clientes; é um risco concentrado num só ponto, e é deliberado que assim seja,
  para que esse ponto seja pequeno e testável;
- que o modelo mínimo de staff do C0.6 seja substituído no Marco 1 (B-020).

Ganhamos:

- uma superfície administrativa que nega acesso por omissão, em vez de o conceder;
- controlo total sobre a aparência e o texto das páginas de cliente;
- separação clara entre o risco do site público e o do painel.

## Verificação obrigatória

O ciclo C0.6 só fecha com um teste que prove que um cliente autenticado recebe 403
em `/admin`. Sem esse teste, o ciclo não é dado como concluído.
