# Backlog

Trabalho identificado e ainda não executado. Não é uma lista de desejos: cada
item existe porque foi encontrado durante um ciclo real.

Prioridade: **P1** bloqueia o marco atual · **P2** necessário no marco seguinte ·
**P3** a decidir mais tarde.

---

## Marco 0 — Fundação

| ID | Prioridade | Item | Ciclo |
| --- | --- | --- | --- |
| ~~B-001~~ | — | ~~Esqueleto Laravel 13 e baseline de qualidade (Pint, PHPStan, Pest)~~ — concluído em C0.2 | C0.2 |
| ~~B-002~~ | — | ~~Docker Compose com PHP-FPM 8.4, Nginx, PostgreSQL 17 e Redis 8~~ — concluído em C0.3 | C0.3 |
| ~~B-003~~ | — | ~~Frontend Inertia, Vue 3, TypeScript, Tailwind e Vite~~ — concluído em C0.4 | C0.4 |
| B-035 | P3 | Subir o TypeScript para a linha 7 quando o `vue-tsc` a suportar (KI-011) | após C0.4 |
| B-036 | P2 | Automatizar a verificação em browser (zero erros de consola) na CI, hoje feita manualmente | C0.9 |
| B-037 | P3 | Skip-link e landmarks de navegação, quando existir cabeçalho real | Marco 1 |
| B-029 | P2 | Completar `lang/pt/validation.php` à medida que novos formulários introduzem regras (KI-014) | Marco 1 |
| ~~B-038~~ | — | ~~Limitador de taxa no registo e no pedido de recuperação~~ — feito em C0.5, incluindo redefinição | C0.5 |
| B-040 | P3 | Decidir `uncompromised()` na política de palavra-passe: introduz chamada HTTP externa num caminho crítico | Marco 3 |
| B-041 | P2 | Reavaliar a enumeração no registo quando existir verificação de email (KI-015) | Marco 3 |
| ~~B-042~~ | — | ~~A marca de staff nunca pode entrar em `#[Fillable]`~~ — cumprido em C0.6, com teste que tenta promover-se pelo registo público | C0.6 |
| B-045 | P3 | Reavaliar `unsafe-eval` no CSP do painel; depende de o Filament expor a build CSP-safe do Alpine | Marco 7 |
| B-046 | P2 | Testar que um Resource real do painel também nega acesso a não-staff, e o endpoint Livewire | Marco 1 |
| ~~B-043~~ | — | ~~Auditoria de eventos de autenticação~~ — concluído em C0.8 | C0.8 |
| ~~B-044~~ | — | ~~Verificação de `APP_ENV=local` fora de desenvolvimento~~ — feito em C0.8 por comando de implantação | C0.8 |
| B-039 | P2 | Verificação de email, adiada para o Marco 3 onde passa a ter consequência no checkout | Marco 3 |
| B-027 | P2 | Retirar a prop `ambiente` da rota raiz ao substituir o shell pela home real | Marco 1 |
| ~~B-028~~ | — | ~~`healthcheck` no serviço `node`~~ — feito em C0.5, depois de a sua ausência esconder um serviço morto (KI-013) | C0.5 |
| ~~B-004~~ | — | ~~Autenticação de clientes com Fortify e páginas Inertia próprias~~ — concluído em C0.5 | C0.5 |
| ~~B-005~~ | — | ~~Painel Filament em `/admin` com acesso negado por omissão~~ — concluído em C0.6 | C0.6 |
| ~~B-006~~ | — | ~~Redis, filas, Horizon com gate e scheduler~~ — concluído em C0.7 | C0.7 |
| B-048 | P2 | Persistir a auditoria de autenticação em `audit_logs`, e não só em ficheiro de log | Marco 6 |
| B-047 | P2 | Verificar chamadas a terceiros em cada nova dependência de interface; já apanhado duas vezes (KI-018, KI-020) | contínuo |
| ~~B-007~~ | — | ~~Health check de PostgreSQL e Redis~~ — concluído em C0.8 | C0.8 |
| B-008 | P1 | CI no GitHub Actions replicando as verificações locais | C0.9 |
| B-009 | P1 | `docs/architecture.md`, `docs/security.md`, `docs/product-scope.md` | C0.10 |
| B-010 | P2 | Configurar o remoto `git@github.com:melquivunge/bilhete.ao.git` e a branch `main` | antes de C0.9 |
| B-011 | P2 | Subir o nível do PHPStan por passos, em ciclo próprio | após C0.2 |
| B-015 | P2 | Reativar `checkModelProperties` do Larastan com modelos e migrations reais (KI-006) | Marco 1 |
| ~~B-016~~ | — | ~~Substituir `resources/views/welcome.blade.php`~~ — removido em C0.4; a raiz é agora uma página Inertia | C0.4 |
| ~~B-012~~ | — | ~~Redação de dados sensíveis nos logs~~ — concluído em C0.8, por tap (KI-021) | C0.8 |
| ~~B-013~~ | — | ~~ADR-005 — Inertia e Vue no frontend~~ — escrito em C0.4 | C0.4 |
| ~~B-014~~ | — | ~~Confirmar as tags exatas das imagens `php` e `node`~~ — `php:8.4.23-fpm-bookworm` fixado em C0.3; tag do Node fica com o serviço Node, em C0.4 | C0.3 |
| B-017 | P2 | Stage de produção no `Dockerfile` e decisão própria: sem bind mount, sem dependências de dev, sem ferramentas de build na camada final | Marco 7 |
| B-018 | P2 | `config/cors.php` explícito com origens restritas, antes de existir qualquer rota `api/*` | Marco 3 |
| B-019 | P3 | Propor "CORS" como item da lista da secção 10 do `agent.md`, onde hoje não consta | Marco 3 |

---

## Marco 1 — Catálogo

| ID | Prioridade | Item |
| --- | --- | --- |
| B-020 | P2 | Modelo definitivo de papéis e permissões por empresa e cinema; substitui o mínimo criado em C0.6 |
| B-021 | P2 | Decidir a unidade monetária inteira do Kwanza — cêntimos ou unidade — e aplicá-la ao modelo de dados |
| B-022 | P2 | Decidir identificadores públicos (UUID ou ULID) e onde substituem IDs sequenciais |
| B-023 | P2 | Fuso de apresentação `Africa/Luanda` com persistência em UTC |
| B-024 | P3 | Armazenamento compatível com S3 para cartazes, com cartazes abstratos ou placeholders licenciados |
| B-025 | P2 | ADR-002 — PostgreSQL como fonte de verdade das reservas |

---

## Marcos posteriores

| ID | Prioridade | Item | Marco |
| --- | --- | --- | --- |
| B-030 | P3 | ADR-003 — bloqueio pessimista na reserva de assentos | 2 |
| B-031 | P3 | Testes de concorrência provando que dois pedidos não reservam o mesmo assento | 2 |
| B-032 | P3 | ADR-004 — abstração do gateway de pagamento | 4 |
| B-033 | P3 | Gateway falso com os dez cenários da secção 8 do `agent.md` | 4 |
| B-034 | P3 | Revisão de segurança independente antes do piloto | 7 |
