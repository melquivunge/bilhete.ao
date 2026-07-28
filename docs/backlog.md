# Backlog

Trabalho identificado e ainda não executado. Não é uma lista de desejos: cada
item existe porque foi encontrado durante um ciclo real.

Prioridade: **P1** bloqueia o marco atual · **P2** necessário no marco seguinte ·
**P3** a decidir mais tarde.

---

## Marco 0 — Fundação

| ID | Prioridade | Item | Ciclo |
| --- | --- | --- | --- |
| B-001 | P1 | Esqueleto Laravel 13 e baseline de qualidade (Pint, PHPStan, Pest) | C0.2 |
| B-002 | P1 | Docker Compose com PHP-FPM 8.4, Nginx, PostgreSQL 17 e Redis 8 | C0.3 |
| B-003 | P1 | Frontend Inertia, Vue 3, TypeScript, Tailwind e Vite | C0.4 |
| B-004 | P1 | Autenticação de clientes com Fortify e páginas Inertia próprias | C0.5 |
| B-005 | P1 | Painel Filament em `/admin` com acesso negado por omissão | C0.6 |
| B-006 | P1 | Redis, filas, Horizon com gate e scheduler | C0.7 |
| B-007 | P1 | Health check de PostgreSQL e Redis sem exposição de topologia | C0.8 |
| B-008 | P1 | CI no GitHub Actions replicando as verificações locais | C0.9 |
| B-009 | P1 | `docs/architecture.md`, `docs/security.md`, `docs/product-scope.md` | C0.10 |
| B-010 | P2 | Configurar o remoto `git@github.com:melquivunge/bilhete.ao.git` e a branch `main` | antes de C0.9 |
| B-011 | P2 | Subir o nível do PHPStan por passos, em ciclo próprio | após C0.2 |
| B-012 | P2 | Redação de dados sensíveis nos logs, conforme secção 10 do `agent.md` | C0.8 |
| B-013 | P1 | ADR-005 — Inertia e Vue no frontend, escrito no ciclo que o implementa | C0.4 |
| B-014 | P2 | Confirmar as tags exatas das imagens `php` e `node` no momento do build | C0.3 |

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
