# Plano de Implementação por Pessoa

## FASE 0 — Unificação da Fundação (os 3 juntos, ~1 sessão)

Resolve as bases para todos partirem de um código sólido e idêntico.

1. **Commit do estado atual** — o repo está mid-migração Laravel→PHP puro, com tudo unstaged. Commitar antes de ramificar.
2. **`Connection.php`** — ✅ **já foi criada** nesta sessão (`src/Database/Connection.php`). Agora `POST /login` e `/cadastrar` deixam de crashar.
3. **Padronizar conexão** — decidir usar `Connection::getInstance()` nos Models. O `config/conexao.php` (função `conexao()`) fica só pro `src/Auth.php` legado; não usar nos novos Models.
4. **Verificar porta do banco** — `3312` em `config/database.php` é fora do padrão; confirmar se a porta local é essa. Fazer um teste de conexão real.
5. **Estrutura de branches** — começar do consenso abaixo antes de ramificar.

---

## Estratégia de Git (para trabalhar em paralelo)

```
main
 └── develop              ← branch de integração
     ├── feature/clientes         (Pessoa A)
     ├── feature/filmes           (Pessoa B)
     └── feature/dvds-emprestimos (Pessoa C)
```

- Cada um cria sua `feature/` a partir da `develop`.
- Commits pequenos e frequentes.
- Merge na `develop` por PR/discussão com o grupo.
- A Fase 0 (incluindo `Connection.php`) deve ser mergeada primeiro, pois é base de todos.

---

## 👤 PESSOA A — Módulo Clientes + Relatórios

**Arquivos a criar:**
```
src/Models/Cliente.php
src/Controllers/ClienteController.php
src/Services/RelatorioService.php   (bônus, no fim)
```

**Contrato (CONTRATOS.md atualizado):**
```php
public function buscarPorId(int $id): array|null
// ['id','nome','sobrenome','telefone','endereco'] ou null
public function existe(int $id): bool
```

**Passos:**
1. Model `Cliente` com os métodos do contrato + `listar()`, `criar()`, `atualizar()`, `deletar()` (CRUD completo).
2. `ClienteController` seguindo o padrão do `AuthController` (lê `php://input`, retorna JSON com códigos HTTP).
3. Registrar rotas em `public/index.php`: `GET/POST /clientes`, `GET/PUT/DELETE /clientes/{id}`.
4. **Bônus (se sobrar tempo):** `RelatorioService` — consultas agregadas (clientes com mais empréstimos, faturamento), usando os contratos das Pessoas B e C (com versões fake se necessário).
5. Testar cada endpoint com `curl`.

**Depende de:** nada para o CRUD. Só relatório depende de B e C.

---

## 🎬 PESSOA B — Módulo Filmes, Atores e Gêneros

**Arquivos a criar:**
```
src/Models/Filme.php
src/Models/Ator.php
src/Models/Genero.php
src/Controllers/FilmeController.php
src/Controllers/AtorController.php
src/Controllers/GeneroController.php
```

**Contrato (CONTRATOS.md atualizado):**
```php
public function buscarPorId(int $id): array|null
// ['id','id_genero','titulo','valor'] ou null
public function listarPorAtor(int $idAtor): array
```

**Passos:**
1. Models `Genero` (simples), `Ator` (simples), `Filme` (contrato + `listar()`, `criar()`, `atualizar()`).
2. **`FilmeController::index()` — PRIORIDADE ALTA** — a rota `GET /filmes` já existe em `public/index.php:51-55` e hoje crasha (fatal error). Implementar primeiro.
3. CRUD de atores e gêneros.
4. Gerenciar a **pivot `atores_filme`** — adicionar/remover atores de um filme (base do `listarPorAtor`).
5. Registrar rotas em `index.php`.
6. Testar com `curl`.

**Depende de:** nada (independente).

---

## 💿 PESSOA C — Módulo DVDs, Empréstimos e Devoluções + Infraestrutura

**Arquivos a criar:**
```
src/Database/Connection.php        ← ✅ já criado nesta sessão
src/Models/Dvd.php
src/Models/Emprestimo.php
src/Models/Devolucao.php
src/Models/FilmeEmprestimo.php
src/Controllers/EmprestimoController.php
src/Controllers/DevolucaoController.php
src/Services/MultaService.php      (cálculo de multa por atraso)
```

**Contratos (CONTRATOS.md atualizado):**
```php
// DVD — tabela: dvds (id, id_filme, quantidade). Sem coluna status.
public function verificarDisponibilidade(int $idFilme): int
// nº de cópias livres (sum quantidade - emprestadas sem devolução)
public function buscarDisponivelPorFilme(int $idFilme): array|null
// ['id','id_filme','quantidade'] ou null

// Empréstimo — tabela: emprestimos (id, data, id_cliente). Sem data_prevista/status.
public function buscarPorId(int $id): array|null
public function listarPorCliente(int $idCliente): array
public function estaAtrasado(int $idEmprestimo): bool
```

**Passos:**
1. Model `Dvd`.
2. Model `Emprestimo` + pivot `filmes_emprestimo` (fluxo: registrar empréstimo → localizar DVD disponível → criar linha no pivot).
3. Model `Devolucao` + pivot `filmes_devolucao`.
4. `MultaService` (regra de atraso, usando `estaAtrasado`).
5. Controllers + rotas.
6. Testar com `curl`.

**Depende de:** A (`Cliente::existe`) e B (`Filme::buscarPorId`).

---

## 📊 Resumo de Dependências e Prioridades

| Pessoa | Módulo | Depende de | Prioridade |
|--------|--------|-----------|-----------|
| **—** | `Connection.php` | — | ✅ pronto |
| A | Clientes (+relatório) | B/C só no relatório | Média |
| B | Filmes/Atores/Gêneros | — | Alta (`/filmes` já crasha) |
| C | DVDs/Empréstimos/Devoluções | A (`existe`) + B (`buscarPorId`) | Alta (mais complexo) |

Equilíbrio: **A** é a mais leve (CRUD simples + relatório bônus); **C** é a mais pesada (fluxo completo empréstimo/devolução). Se quiser reequilibrar, dá pra mover o relatório para B ou a infraestrutura para A.
