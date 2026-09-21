# Contratos de Integração — Sistema de Locadora (2 pessoas)

Este documento define os métodos que cada módulo expõe para os demais, para que as **duas pessoas** do grupo possam desenvolver em paralelo sem depender da implementação final uma da outra. Assinatura combinada aqui = assinatura que não muda depois sem avisar o grupo.

**Este contrato reflete o estado ATUAL do código** (`develop`). O que já existe não deve ser refeito com outro nome — os nomes reais abaixo têm precedência sobre qualquer versão antiga "de papel".

O schema vigente está em `database/locadora.sql`.

## Convenções gerais

- Métodos de busca por ID retornam `array` com os dados ou `null` se não existir. Nunca lançam exceção por "não encontrado".
- Datas sempre no formato `Y-m-d H:i:s` (string), tanto em parâmetros quanto em retornos.
- Valores monetários sempre `float`, já com 2 casas decimais.
- Nomes de coluna do banco em `snake_case` (como no `locadora.sql`).
- Todo array de retorno usa as chaves com o mesmo nome da coluna do banco (ex: `id_genero`, não `generoId`).
- **Padrão de implementação vigente:** os Models recebem a conexão pronta via construtor — `new Model($conn)` — com `$conn` vindo de `config/conexao.php`. Classes globais (sem `namespace`) carregadas por `require_once`. (Exceções legadas já existentes: `App\Controllers\AuthController`, `App\Models\Usuario` e `App\Database\Connection`, que ficam como estão.)
- Prazo padrão de empréstimo: **7 dias corridos** a partir da `data` (regra centralizada em `MultaService`).

---

## ✅ Já implementado (não refazer)

| Método | Onde | Status |
|---|---|---|
| `Filmes::buscarPorId(int $id)` | `src/Models/Filmes.php` | ✅ |
| `Filmes::listarAtores(int $idFilme)` | `src/Models/Filmes.php` | ✅ |
| `Filmes::listarTodos/listarPorPagina/filtrarFilmes/filtrarPorGenero/criar/contarTotal` | `src/Models/Filmes.php` | ✅ |
| `Ator::listarTodos/listarFilmeAtuado/listarAtorPorID` | `src/Models/AtorModel.php` | ✅ |
| `Generos::listarGeneros()` | `src/Models/Generos.php` | ✅ |
| `Dvd::verificarDisponibilidade(int $idFilme)` | `src/Models/Dvd.php` | ✅ |
| `Dvd::inserirDVD` (via `DVDController`) | `src/Controllers/DvdController.php` | ✅ |
| Autenticação (web + API) | `src/Models/Auth.php`, `src/Controllers/AuthController.php` | ✅ |

---

## Cliente (Pessoa A) → usado por Empréstimo (Pessoa B) e Relatórios (Pessoa A)

Tabela: `clientes` (`id`, `nome`, `sobrenome`, `telefone`, `endereco`)

> ⚠️ `src/Models/cliente.php` hoje **só tem** `listarTodos()`. Os métodos abaixo são **a criar** pela Pessoa A.

```php
public function buscarPorId(int $id): array|null
// retorna: ['id' => 1, 'nome' => 'João', 'sobrenome' => 'Silva',
//           'telefone' => '51999999999', 'endereco' => 'Rua X, 123'] ou null

public function existe(int $id): bool
// true/false — usado pra validar antes de registrar um empréstimo

// Interno do módulo (também da Pessoa A): listar(), criar(), atualizar(), deletar()
```

## Filme (Pessoa A — dono; ✅ pronto) → usado por Empréstimo (Pessoa B) e Relatórios (Pessoa A)

Tabela: `filmes` (`id`, `id_genero`, `titulo`, `valor`, `poster_url`)

```php
public function buscarPorId(int $id): array|null          // ✅ src/Models/Filmes.php
// retorna: ['id' => 1, 'id_genero' => 3, 'titulo' => 'Filme X', 'valor' => 8.50,
//           'poster_url' => '...', 'genero' => 'Ação']   // inclui JOIN com generos

public function listarPorAtor(int $idAtor): array
// ✅ equivalente real já existente: Ator::listarFilmeAtuado(int $idAtor) em src/Models/AtorModel.php
// retorna: lista de filmes (id, titulo, personagem) em que o ator participou, via pivot atores_filme
```

## DVD (Pessoa B — dono; parcial) → usado por Empréstimo (Pessoa B) e Relatórios (Pessoa A)

Tabela: `dvds` (`id`, `id_filme`, `quantidade`)

O schema **não possui** coluna de `status`. A disponibilidade de um DVD é inferida: uma cópia está **disponível** se não houver um registro ativo em `filmes_emprestimo` (ou seja, não pertence a um empréstimo que ainda não foi devolvido em `devolucoes`).

```php
public function verificarDisponibilidade(int $idFilme): int  // ✅ src/Models/Dvd.php
// retorna: número de cópias livres daquele filme
// (soma de `quantidade` dos dvds do filme - cópias já emprestadas em
//  empréstimos sem devolução registrada)

public function buscarDisponivelPorFilme(int $idFilme): array|null  // ⚠️ A CRIAR (Pessoa B)
// retorna: um DVD com cópia livre pra alugar -> ['id' => 12, 'id_filme' => 4, 'quantidade' => 5]
// ou null se não houver nenhuma cópia disponível
```

## Empréstimo (Pessoa B — dono; ⚠️ A CRIAR) → usado por Devolução (Pessoa B) e Relatórios (Pessoa A)

Tabela: `emprestimos` (`id`, `data`, `id_cliente`)
Itens do empréstimo em `filmes_emprestimo` (`id`, `id_dvd`, `id_emprestimo`).

O schema **não possui** `data_prevista` nem `status`. A "previsão" e o estado ativo/atrasado devem ser calculados a partir da `data` do empréstimo e da existência (ou não) de devolução registrada em `devolucoes`.

```php
public function buscarPorId(int $id): array|null
// retorna: ['id' => 1, 'id_cliente' => 5, 'data' => '2026-08-20 14:00:00'] ou null

public function listarPorCliente(int $idCliente): array
// retorna: lista de empréstimos (mesmo formato acima) daquele cliente,
// em ordem decrescente de `data`

public function estaAtrasado(int $idEmprestimo): bool
// true se ainda não houve devolução registrada e o empréstimo está além do
// prazo padrão de 7 dias corridos a partir de `data`
// Obs.: regra de prazo única em `MultaService`

public function registrar(array $dados): int  // id_do_emprestimo criado
// $dados = ['id_cliente' => 5, 'itens' => [['id_filme' => 4], ...]]
// responsável por localizar/diminuir a disponibilidade dos DVDs
// (usa Dvd::buscarDisponivelPorFilme) e gravar linhas em filmes_emprestimo
```

## Devolução (Pessoa B — dono; ⚠️ A CRIAR) → usado por Relatórios (Pessoa A)

Tabela: `devolucoes` (`id`, `id_emprestimo`, `data`)
Itens devolvidos em `filmes_devolucao` (`id`, `id_devolucao`, `id_filme_emprestimo`).

```php
public function registrar(int $idEmprestimo): array
// retorna: ['id' => 1, 'id_emprestimo' => 5, 'data' => '2026-08-27 14:00:00',
//           'multa' => 3.50]
// grava a devolução + itens e devolve o valor da multa calculada (0.00 se no prazo)
```

---

## Interfaces Pessoa A ⇄ Pessoa B

| Quem consome | Método que precisa | Dono | Status |
|---|---|---|---|
| B (Empréstimo) | `Cliente::existe()` | A | 🔜 a criar |
| B (Empréstimo) | `Filme::buscarPorId()` | A | ✅ pronto |
| A (Relatórios) | `Emprestimo::listarPorCliente()` / `buscarPorId()` | B | 🔜 a criar |
| A (Relatórios) | `Devolucao::registrar()` | B | 🔜 a criar |
| A/B (Empréstimo) | `Dvd::verificarDisponibilidade()` / `buscarDisponivelPorFilme()` | B | ✅ / 🔜 |

---

## Antes de codar

1. Revisem esta lista os **dois** juntos e ajustem o que não fizer sentido pro `locadora.sql`.
2. Qualquer mudança de assinatura depois de combinada precisa ser avisada no grupo antes de dar push.
3. Quem depende de um método que ainda não foi implementado pode criar uma versão "fake" (retornando um array fixo) só pra não travar o próprio desenvolvimento, e trocar depois pela versão real.
4. **Não recrie métodos já prontos** (seção "✅ Já implementado") com outro nome ou em outra classe — use os existentes.