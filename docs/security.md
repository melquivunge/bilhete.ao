# Segurança

Estado em 2026-07-30, fim do Marco 0. Cada afirmação corresponde a código que
existe e a um teste que o prova, ou está marcada como **não implementado**.

Este documento não repete a secção 10 do `agent.md` — diz o que foi feito quanto
a ela.

---

## Controlos em vigor

| Risco (secção 10) | O que existe | Prova |
| --- | --- | --- |
| Autenticação | Fortify, guard `web`, palavra-passe com mínimo de 10 e `max:255` | `tests/Feature/Auth/` |
| Autorização | `canAccessPanel()` nega por omissão; gate `viewHorizon` | `tests/Feature/Admin/`, `tests/Feature/Filas/` |
| Mass assignment | `is_staff` fora do `#[Fillable]`; atribuição em massa **rebenta** fora de produção | `AcessoAoPainelTest` |
| Força bruta | 5/min por email+IP no login; 10/min por IP e 5/min por email em registo, recuperação e redefinição | `LimitesDePedidosTest` |
| Enumeração de contas | Mensagem idêntica no login; resposta uniforme na recuperação | `AutenticacaoTest`, `RecuperacaoPalavraPasseTest` |
| XSS | CSP com nonce por pedido no site público; sem `unsafe-inline` fora de `local` | `ContentSecurityPolicyTest` |
| CSRF | Grupo `web` e middleware próprio do Filament | — |
| Fixação de sessão | Regeneração no login, invalidação no logout | `SessaoTest` |
| Cookies | `HttpOnly`, `SameSite=lax`, `Secure` por omissão fora de desenvolvimento | `SessaoTest` |
| Vazamento em logs | Redação por tap em todos os canais | `ObservabilidadeTest` |
| Auditoria | Entrada, falha, bloqueio, saída, registo e redefinição | `ObservabilidadeTest` |
| Exposição de dados pessoais | Inertia partilha id e nome; o email nunca | `PartilhaDeIdentidadeTest` |
| Enumeração por `.php` | Só `index.php` é executado; o resto devolve 404 | verificado por `curl` |

## Princípios que a fundação estabelece

**Negar por omissão, com comparação estrita.** `canAccessPanel()` compara com
`=== true`. Um valor nulo, `1`, string vazia ou coluna ausente **nega**. O mesmo
no gate do Horizon. Quando o Marco 6 substituir o booleano por papéis, o rigor
tem de sobreviver à mudança.

**Privilégio nunca é atribuível em massa.** `is_staff` só é escrito pela Action
`PromoverUtilizadorAStaff`, com `forceFill`. Uma allowlist é por modelo, não por
rota: se a coluna entrasse no `#[Fillable]` para conveniência de um formulário do
painel, passaria a ser atribuível pelo `POST /register` público.

> **Regra para o Marco 1:** qualquer coluna de privilégio nova — papel por
> empresa, permissões por cinema — nasce fora do `#[Fillable]`, com uma Action
> dedicada como único escritor, e um teste espelho dos que já existem.

**Nenhuma credencial funcional em ficheiro versionado.** `DB_PASSWORD` e
`REDIS_PASSWORD` estão vazios no `.env.example`, e o Compose **recusa arrancar**
sem eles em vez de cair para uma senha por omissão. A CI verifica isto.

**Nenhuma chamada a terceiros sem necessidade.** Já foi apanhado duas vezes:
Filament enviava o nome do staff para `ui-avatars.com` (KI-018), Horizon carregava
uma fonte de `fonts.bunny.net` (KI-020). Ambos foram revelados pelo CSP, não por
revisão. Verificar isto é item permanente (B-047).

## Riscos aceites, com justificação

| Risco | Porquê aceite | Reavaliar |
| --- | --- | --- |
| Enumeração pelo registo (KI-015) | A alternativa exige verificação de email, adiada para o Marco 3. Fingir sucesso sem enviar nada deixaria o utilizador legítimo à espera de um email que nunca chega | Marco 3 (B-041) |
| Canal lateral de temporização (KI-016) | Igualar tempos com precisão é caro e imperfeito. O que o tornava explorável era volume ilimitado, e os três endpoints passaram a ter limitador | Marco 7 (B-034) |
| `unsafe-eval` no CSP dos painéis | O Alpine avalia expressões `x-*` com `new Function()`. Sem isso o painel não funciona. Mantém-se `default-src 'self'` e `connect-src 'self'` | Marco 7 (B-045) |
| `uncompromised()` fora da política de palavra-passe | Introduz chamada HTTP externa num caminho crítico e tornaria a suíte dependente da internet | Marco 3 (B-040) |

## Não implementado

- **Verificação de email** (B-039). O Marco 3 tem de garantir que nenhum bilhete é
  emitido para um endereço por confirmar.
- **Isolamento entre empresas e cinemas.** Não existe porque não existem empresas
  nem cinemas. É o núcleo do Marco 1.
- **CORS** (B-018). Não há rotas `api/*`. Tem de ser decidido **antes** de a
  primeira existir, não depois.
- **`audit_logs` em base de dados** (B-048). A auditoria vai para ficheiro:
  suficiente para investigar, insuficiente para relatórios.
- **Imagem de produção** (B-017).
- **Ramo `main` protegido** (B-049). Sem isso a CI é informativa.

## Antes de qualquer implantação

```bash
php artisan bilhete:verificar-producao
```

Verifica `APP_DEBUG`, `APP_ENV`, `SESSION_SECURE_COOKIE` e `APP_KEY`.

Existe fora do arranque por uma razão: a aplicação já recusa arrancar com
`APP_DEBUG=true` em produção, mas **não consegue detetar produção a correr com
`APP_ENV=local`** — nesse caso julga-se em desenvolvimento, relaxa o CSP e serve o
cookie sem `Secure`, e a única fonte de verdade sobre o ambiente é precisamente a
variável errada.

## Como criar a primeira conta de staff

```bash
docker compose exec app php artisan bilhete:criar-staff
```

A palavra-passe é pedida interativamente e **nunca** aceite como argumento: um
argumento ficaria no histórico da shell e na lista de processos. Há um teste que
impede alguém de acrescentar essa opção por conveniência.

## Proibições em vigor

Nenhum envio real de mensagens (`MAIL_MAILER=array`), nenhuma cobrança real,
nenhum gateway de pagamento ligado. A integração com o MULTICAIXA permanece
desativada enquanto não existirem documentação oficial, contrato e credenciais.
