# Plano de Implementação por Pessoa (3 pessoas)

## FASE 0 — Estado atual e alinhamento (os 3 juntos, ~1 sessão)

Resolve as bases para os três partirem de um código sólido e idêntico. Boa parte do sistema **já está pronta** (ver seção "Já pronto" no fim); a Fase 0 serve só para destravar o que falta.

1. **Garantir `develop` atualizada** — o trabalho recente (tela de atores, catálogo, filtros, sinopse TMDB) já está na `develop`. Confirmar que `feature/ator` foi mergeada e commitar qualquer pendência antes de ramificar.
2. **Definir padrão de conexão (decisão)** — o padrão vigente é `$conn` vindo de `config/conexao.php`, injetado no construtor dos Models (`new Model($conn)`), com classes globais carregadas por `require_once`. **Manter esse padrão nos módulos novos.** A classe `App\Database\Connection` aponta para `config/database.php`, que **não existe** — tratar como legado e não depender dela nos módulos novos (ou criar o arquivo depois, se quiserem unificar).
3. **Rotas API** — continuar registrando em `public/index.php` seguindo o padrão das rotas existentes (`GET/POST /filmes`, `POST /login`). Já existe um `spl_autoload_register` para o namespace `App\`; modelos/web continuam com `require_once`.
4. **Estrutura de branches** — começar do consenso abaixo antes de ramificar.

---

## Estratégia de Git (para 3 pessoas trabalharem em paralelo)

```
main
 └── develop              ← branch de integração
     ├── feature/clientes         (Pessoa A)
     ├── feature/atores           (Pessoa B)
     └── feature/dvds-emprestimos (Pessoa C)
```

- Cada pessoa cria sua `feature/` a partir da `develop`.
- Commits pequenos e frequentes.
- Merge na `develop` por PR/discussão com o grupo (evitar merge direto na `main`).
- A Fase 0 deve estar na `develop` antes dos três ramificarem.

---

## 👤 PESSOA A — Módulo Clientes + Relatórios

**Arquivos:**
```
src/Models/cliente.php                       ← completar (hoje só tem listarTodos)
src/Controllers/ClienteController.php        ← implementar (hoje vazio)
src/Controllers/excluirclientescontroller.php ← refatorar p/ usar o Model (hoje SQL solto)
src/Services/RelatorioService.php            ← bônus (no fim)
```

**Contrato que EXPÕE (CONTRATOS.md):**
```php
public function buscarPorId(int $id): array|null
// ['id','nome','sobrenome','telefone','endereco'] ou null
public function existe(int $id): bool
```

**Contratos que CONSUME:** `Emprestimo::listarPorCliente` / `Devolucao::registrar` (Pessoa C) e `Filme::buscarPorId` (✅ pronto) — só no Relatório, usando versão fake se a Pessoa C não terminou.

**Passos:**
1. Completar `src/Models/cliente.php`: `buscarPorId`, `existe` + CRUD (`listar`, `criar`, `atualizar`, `deletar`).
2. Implementar `ClienteController` seguindo o padrão dos controllers de API (lê `php://input`, retorna JSON com códigos HTTP — referência: `AuthController`).
3. Registrar rotas em `public/index.php`: `GET/POST /clientes`, `GET/PUT/DELETE /clientes/{id}`.
4. Refatorar `cadastro.php`, `editarclientes.php` e `excluirclientescontroller.php` para usar o Model (hoje usam SQL solto).
5. **Sucesso =** cadastrar/editar/excluir cliente pela tela e via API (`curl`).
6. **Bônus:** `RelatorioService` — consultas agregadas (clientes com mais empréstimos, faturamento), usando os contratos das Pessoas B e C.

**Depende de:** nada para o CRUD. Só relatório depende de B e C.

---

## 🎬 PESSOA B — Módulo Atores e Catálogo (finalização)

O catálogo de filmes, filtros e sinopse **já estão prontos** (Pessoa B herdou o módulo). O que falta é o CRUD de atores e o vínculo ator↔filme na pivot `atores_filme` — telas que já estão linkadas mas ainda não existem.

**Arquivos:**
```
src/Models/AtorModel.php               ← adicionar criar/atualizar (hoje só listar)
src/Models/Filmes.php                  ← usar como está (não recriar); adicionar auxiliar p/ pivot se necessário
src/Controllers/AtorController.php     ← criar (cadastro/edição de atores)
templates/atores/cadastroAtor.php      ← criar (link já existe em templates/atores/atores.php)
templates/atores/cadastroFilmeAtor.php ← criar (vínculo na pivot atores_filme)
templates/atores/editarfilmes.php      ← criar (link já existe em atoresFilmes.php)
```

**Contratos (CONTRATOS.md):** expõe `Filme::buscarPorId` (✅ pronto) e `Filme::listarAtores` (✅ pronto); cria o CRUD da pivot `atores_filme` (base do `listarPorAtor` já existente).

**Passos:**
1. Estender `src/Models/AtorModel.php`: `criar()`, `atualizar()`, `deletar()`.
2. CRUD de atores: `AtorController` + `templates/atores/cadastroAtor.php`.
3. Vínculo ator↔filme: cadastrar/editar o personagem na pivot `atores_filme` (`cadastroFilmeAtor.php`, `editarfilmes.php`).
4. Verificar que a timeline de filmes do ator (`atoresFilmes.php`) continua funcionando.
5. Testar com `curl` (ex.: `GET /filmes/{id}` com a lista de atores viva).

**Depende de:** nada (independente).

---

## 💿 PESSOA C — Módulo DVDs, Empréstimos e Devoluções + Multa

**Arquivos:**
```
src/Models/Dvd.php                       ← adicionar buscarDisponivelPorFilme
src/Models/Emprestimo.php                ← criar
src/Models/FilmeEmprestimo.php           ← criar
src/Models/Devolucao.php                 ← criar
src/Models/FilmeDevolucao.php            ← criar
src/Controllers/EmprestimoController.php ← criar
src/Controllers/DevolucaoController.php  ← criar
src/Services/MultaService.php            ← criar (cálculo de multa por atraso)
templates/emprestimos/                   ← telas (listar, registrar, devolver)
```

**Contratos (CONTRATOS.md):**
```php
// DVD — tabela: dvds (id, id_filme, quantidade). Sem coluna status.
public function verificarDisponibilidade(int $idFilme): int        // ✅ pronto
public function buscarDisponivelPorFilme(int $idFilme): array|null  // a criar

// Empréstimo — tabela: emprestimos (id, data, id_cliente). Sem data_prevista/status.
public function buscarPorId(int $id): array|null
public function listarPorCliente(int $idCliente): array
public function estaAtrasado(int $idEmprestimo): bool
public function registrar(array $dados): int

// Devolução — tabela: devolucoes (id, id_emprestimo, data).
public function registrar(int $idEmprestimo): array
```

**Contratos que CONSUME:** `Cliente::existe()` (Pessoa A) e `Filme::buscarPorId` (✅ pronto). Usar versão fake de `Cliente::existe` se a Pessoa A ainda não terminou.

**Passos:**
1. Adicionar `Dvd::buscarDisponivelPorFilme()` (base no SQL de `verificarDisponibilidade`).
2. Model `Emprestimo` + pivot `filmes_emprestimo` (fluxo: validar cliente → localizar DVD disponível → gravar empréstimo + itens).
3. Model `Devolucao` + pivot `filmes_devolucao` (marcar itens como devolvidos).
4. `MultaService` — regra de atraso (prazo padrão de 7 dias), usando `estaAtrasado`; retorno da multa na devolução.
5. Controllers (`EmprestimoController`, `DevolucaoController`) + rotas: `POST /emprestimos`, `POST /devolucoes`, `GET /emprestimos/cliente/{id}` (registrar em `public/index.php`).
6. Telas em `templates/emprestimos/` (registrar empréstimo, devolver, listar por cliente) e link no painel.
7. Testar o fluxo completo com `curl`.

**Depende de:** Pessoa A (`Cliente::existe`) e Pessoa B (`Filme::buscarPorId` ✅ pronto).

---

## 📊 Resumo de Dependências e Prioridades

| Pessoa | Módulo | Depende de | Prioridade |
|--------|--------|-----------|-----------|
| — | Fundações (auth, catálogo, filtros, sinopse, atores, clientes) | — | ✅ pronto |
| A | Clientes (+ relatório bônus) | B/C só no relatório | Alta |
| B | Atores / finalização do catálogo | — | Média |
| C | DVDs / Empréstimos / Devoluções / Multa | A (`existe`) + B (`buscarPorId`) | Alta (mais complexo) |

Equilíbrio: **B** é a mais leve (base do catálogo já pronta, falta só CRUD de atores); **C** é a mais pesada (fluxo completo empréstimo/devolução). Se quiser reequilibrar: mover o **relatório** (Pessoa A) ou **CDV da pivot de atores** para a Pessoa B, ou dar a **finalização das telas de clientes** para B.

---

## ✅ Já pronto (não refazer)

- Autenticação (web + API): `src/Models/Auth.php`, `src/Controllers/AuthController.php`, `src/Models/Usuario.php`.
- Catálogo de filmes: tela com paginação, filtro por gênero e pesquisa (`templates/filmes/filmes.php`), detalhe com sinopse via TMDb (`detalharFilme.php`), cadastro de filme + DVD com busca no TMDb (`cadastrarFilme.php`).
- API de filmes: `GET /filmes`, `GET /filmes/{id}` em `public/index.php` (usam `Filmes` e `Dvd`).
- Models: `Filmes`, `Dvd` (parcial), `Generos`, `AtorModel` (parcial), `cliente` (parcial), `Auth`, `Usuario`.
- Controllers: `FilmesController::salvar`, `DVDController::inserirDVD`, `AuthController`.
- Telas de atores: `templates/atores/atores.php`, `atoresFilmes.php`.
- Telas de clientes: `templates/tabelaclientes.php`, `editarclientes.php`, `cadastro.php`, `excluirclientescontroller.php` (prontas mas com SQL solto — migrar para o Model na Pessoa A).