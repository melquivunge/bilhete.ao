# Âmbito do produto

O Bilhete.ao vende bilhetes de cinema em Luanda, e mais tarde de eventos.

A experiência que define o produto:

```text
filme → cinema → data → sessão → assentos → checkout → pagamento → bilhete com QR Code
```

---

## O que já existe

Nada disso. O Marco 0 construiu a fundação técnica: aplicação, base de dados,
filas, autenticação de clientes, painel administrativo, saúde e integração
contínua. **Não há um único filme, cinema, sessão ou bilhete no sistema.**

Um cliente consegue hoje criar conta, entrar, sair e recuperar a palavra-passe.
Um membro do staff consegue entrar no painel. É tudo.

## Quem usa

| Papel | O que faz | Existe? |
| --- | --- | --- |
| **Cliente** | Encontra filmes e sessões, escolhe lugares, paga, recebe o bilhete | conta e autenticação apenas |
| **Operador de cinema** | Gere salas, sessões e preços; valida bilhetes à entrada | acesso ao painel apenas |
| **Administrador** | Acompanha vendas, pagamentos, reembolsos e comissões | acesso ao painel apenas |

O modelo de papéis por empresa e cinema é do Marco 1. Hoje existe apenas uma
marca booleana de staff, deliberadamente mínima.

## Decisões de produto já tomadas

**Cinemas e filmes são fictícios.** A primeira versão usa Cine Luanda
Entretenimento, Cine Luanda Talatona e a Sala Kilimanjaro, com cinco filmes
inventados. Não se usam marcas, logótipos, cartazes ou programação de empresas
reais sem autorização.

**Português de Angola, valores em Kz.** O locale é `pt` e as mensagens do sistema
estão traduzidas. A interface é mobile-first: em Luanda a maioria dos acessos vem
de rede móvel, e isso condiciona decisões técnicas — por exemplo, as páginas Vue
são carregadas por chunks separados, para abrir a home não descarregar o site
inteiro.

**Interface visualmente original.** Não se copia o layout de plataformas
existentes. Por isso não foi usado nenhum starter kit de autenticação: as páginas
foram escritas de raiz.

**Pagamentos simulados até haver contrato.** A integração real com o MULTICAIXA só
avança quando existirem documentação oficial, contrato e credenciais. Até lá o
sistema usa um gateway falso, com o contrato desenhado para os dois serem
intermutáveis.

## Decisões de produto por tomar

Estas condicionam o modelo de dados e não se decidem bem sem cinemas para modelar.
Entram todas no início do Marco 1:

| Decisão | Consequência de errar |
| --- | --- |
| Unidade monetária do Kwanza — cêntimos ou unidade inteira (B-021) | Entra em todos os preços e nunca mais sai sem migração de dados |
| Identificadores públicos: UUID ou ULID, e onde substituem IDs sequenciais (B-022) | A secção 10 proíbe expor IDs que facilitem enumeração; mudar depois quebra URLs |
| Fuso de apresentação `Africa/Luanda`, com persistência em UTC (B-023) | Uma sessão às 21:30 tem de ser 21:30 em Luanda |
| Modelo de papéis por empresa e cinema (B-020) | **Substitui** a marca booleana atual. Acrescentar-lhe bandeiras em vez de a substituir vira dívida estrutural |

## Caminho até ao piloto

| Marco | Entrega | Estado |
| --- | --- | --- |
| **0 — Fundação** | Docker, Laravel, PostgreSQL, Redis, Inertia, Filament, autenticação, CI | **concluído** |
| 1 — Catálogo | empresas, cinemas, salas, assentos, filmes, sessões, preços, páginas públicas | por fazer |
| 2 — Reserva | seleção visual de lugares, bloqueio pessimista, reserva de 8 minutos, expiração | por fazer |
| 3 — Checkout | pedido, itens, totais, taxas, cupões, expiração | por fazer |
| 4 — Pagamento simulado | contrato do gateway, gateway falso, webhooks idempotentes, reembolsos | por fazer |
| 5 — Bilhetes | emissão, QR Code, página do bilhete, scanner, validação única | por fazer |
| 6 — Operação | relatórios, cancelamentos, reembolsos, comissões, permissões por cinema | por fazer |
| 7 — Piloto | carga, segurança, observabilidade, backups, acessibilidade, integração real | por fazer |

## O que define o produto como sério

Duas regras atravessam todos os marcos e não são negociáveis:

**O servidor decide se um assento está disponível.** Nunca o navegador. A
disponibilidade vive em PostgreSQL, com bloqueio pessimista, e o Marco 2 só fecha
com testes que provem que dois pedidos simultâneos não reservam o mesmo lugar.

**Uma compra confirma-se por webhook, não por redirecionamento.** O cliente
chegar à página de sucesso não prova que pagou. O Marco 4 trata webhooks como
autenticados, idempotentes e auditáveis.

São estas duas que separam uma plataforma de bilhetes de uma demonstração bonita.
