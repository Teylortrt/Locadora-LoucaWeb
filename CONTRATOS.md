# Contratos de Integração — Sistema de Locadora

Este documento define os métodos que cada módulo expõe para os demais, para que as três pessoas do grupo possam desenvolver em paralelo sem depender da implementação final uma da outra. Assinatura combinada aqui = assinatura que não muda depois sem avisar o grupo.

As descrições abaixo refletem o schema atual em `database/locadora.sql`.

## Convenções gerais

- Métodos de busca por ID retornam `array` com os dados ou `null` se não existir. Nunca lançam exceção por "não encontrado".
- Datas sempre no formato `Y-m-d H:i:s` (string), tanto em parâmetros quanto em retornos.
- Valores monetários sempre `float`, já com 2 casas decimais.
- Nomes de método em `camelCase`; nomes de coluna do banco continuam em `snake_case` (como no `locadora.sql`).
- Todo array de retorno usa as chaves com o mesmo nome da coluna do banco (ex: `id_genero`, não `generoId`).

---

## Cliente (Pessoa A) → usado por Empréstimo (Pessoa C)

Tabela: `clientes` (`id`, `nome`, `sobrenome`, `telefone`, `endereco`)

```php
public function buscarPorId(int $id): array|null
// retorna: ['id' => 1, 'nome' => 'João', 'sobrenome' => 'Silva',
//           'telefone' => '51999999999', 'endereco' => 'Rua X, 123'] ou null

public function existe(int $id): bool
// true/false — usado pra validar antes de registrar um empréstimo
```

## Filme (Pessoa B) → usado por DVD e Relatórios (Pessoa C / Pessoa A)

Tabela: `filmes` (`id`, `id_genero`, `titulo`, `valor`)

```php
public function buscarPorId(int $id): array|null
// retorna: ['id' => 1, 'id_genero' => 3, 'titulo' => 'Filme X', 'valor' => 8.50] ou null

public function listarPorAtor(int $idAtor): array
// retorna: lista de filmes (mesmo formato acima) em que o ator participou,
// via tabela pivot `atores_filme`
```

## DVD (Pessoa C) → usado por Filme e Relatórios (Pessoa B / Pessoa A)

Tabela: `dvds` (`id`, `id_filme`, `quantidade`)

O schema **não possui** coluna de `status`. A disponibilidade de um DVD é inferida: uma cópia está **disponível** se não houver um registro ativo em `filmes_emprestimo` (ou seja, não pertence a um empréstimo que ainda não foi devolvido em `devolucoes`).

```php
public function verificarDisponibilidade(int $idFilme): int
// retorna: número de cópias livres daquele filme
// (soma de `quantidade` dos dvds do filme - cópias já emprestadas em
//  empréstimos sem devolução registrada)

public function buscarDisponivelPorFilme(int $idFilme): array|null
// retorna: um DVD com cópia livre pra alugar -> ['id' => 12, 'id_filme' => 4, 'quantidade' => 5]
// ou null se não houver nenhuma cópia disponível
```

## Empréstimo (Pessoa C) → usado por Devolução e Relatórios (Pessoa C / Pessoa A)

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
// true se ainda não houve devolução registrada e o empréstimo está além
// do prazo padrão (ex.: 7 dias corridos a partir de `data`)
// Obs.: ajustar a regra de prazo em conjunto com o grupo e registrar em `MultaService`
```

---

## Antes de codar

1. Revisem esta lista os três juntos e ajustem o que não fizer sentido pro que já foi modelado no `locadora.sql`.
2. Qualquer mudança de assinatura depois de combinada precisa ser avisada no grupo antes de dar push.
3. Quem depende de um método que ainda não foi implementado pode criar uma versão "fake" (retornando um array fixo) só pra não travar o próprio desenvolvimento, e trocar depois pela versão real.
