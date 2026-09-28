# Relatório de Modelagem e Integração — Locadora LoucaWeb

**Projeto:** Sistema de gerenciamento de locadora de filmes  
**Banco de dados:** MySQL/MariaDB, schema `locadora`  
**Integração externa:** The Movie Database (TMDB)  
**Data:** 27 de setembro de 2026

> Este relatório descreve o estado encontrado nos arquivos do projeto. O script `database/locadora.sql` é a referência do modelo físico atual; recomendações são identificadas como tais e não devem ser confundidas com funcionalidades já implementadas.

## 1. Modelo Conceitual (DER)

O diagrama abaixo apresenta as entidades, seus principais atributos e as cardinalidades derivadas das chaves estrangeiras do DDL. `||` indica exatamente um; `o{` indica zero ou muitos. Os campos marcados como PK são chaves primárias e os campos FK são chaves estrangeiras. A fonte editável Mermaid está em [image/DER_locadora.mmd](image/DER_locadora.mmd).

<div style="page-break-before: always; text-align: center;">
	<img src="image/DER_locadora.png" alt="Diagrama Entidade-Relacionamento da Locadora LoucaWeb" width="500" height="904">
</div>

### Leitura das cardinalidades

- **Gênero–Filme (1:N):** cada filme referencia exatamente um gênero; um gênero pode classificar zero ou vários filmes.
- **Filme–DVD (1:N):** cada registro de estoque aponta para um filme; um filme pode ter zero ou vários registros em `dvds`. A coluna `quantidade` agrega cópias, em vez de cadastrar cada mídia física individualmente.
- **Filme–Ator (N:M):** resolvido pela entidade associativa `atores_filme`. Ela também guarda `personagem`, atributo que pertence à participação do ator naquele filme, e não ao ator isoladamente.
- **Cliente–Empréstimo (1:N):** cada empréstimo pertence a exatamente um cliente; o cliente pode não ter empréstimos ou possuir vários.
- **Empréstimo–DVD (N:M):** resolvido por `filmes_emprestimo`, que representa os itens vinculados a um empréstimo. O identificador de `dvds` referencia o registro de estoque (`id_dvd`), e não uma cópia individual serializada.
- **Empréstimo–Devolução (1:N no DDL atual):** cada devolução pertence a um empréstimo. Como `devolucoes.id_emprestimo` não tem restrição `UNIQUE`, o schema permite registrar várias devoluções para o mesmo empréstimo.
- **Devolução–Item emprestado (N:M no DDL):** `filmes_devolucao` liga devoluções aos itens de `filmes_emprestimo`; isso permite representar itens devolvidos em momentos diferentes. A tabela não tem restrições `UNIQUE` que impeçam duplicidade de vínculo.
- **Usuários:** `usuarios` não possui FK para outras tabelas no schema atual; relacionamentos com ações de empréstimo não são registrados no DDL.

## 2. Modelo Lógico e DDL SQL

O modelo relacional está implementado em [database/locadora.sql](database/locadora.sql). Todas as tabelas usam InnoDB e chaves primárias inteiras com `AUTO_INCREMENT`. As FKs preservam a integridade referencial e estão definidas com `ON DELETE NO ACTION` e `ON UPDATE NO ACTION`, salvo a unicidade de e-mail em `usuarios`.

| Relação | Atributos e tipos principais | Chaves e observações |
|---|---|---|
| `generos` | `id INT`, `genero VARCHAR(45)` | PK `id`; catálogo de gêneros. |
| `filmes` | `id INT`, `id_genero INT`, `titulo VARCHAR(100)`, `valor DECIMAL(8,2)`, `poster_url VARCHAR(255) NULL` | PK `id`; FK `id_genero` → `generos.id`. Valor monetário usa decimal, evitando ponto flutuante. |
| `atores` | `id INT`, `nome VARCHAR(100)` | PK `id`. |
| `atores_filme` | `id INT`, `id_filme INT`, `id_ator INT`, `personagem VARCHAR(100)` | PK `id`; FKs para `filmes` e `atores`; resolve N:M e registra o personagem. |
| `dvds` | `id INT`, `id_filme INT`, `quantidade INT` | PK `id`; FK `id_filme` → `filmes.id`; quantidade agregada por registro. |
| `clientes` | `id INT`, `nome VARCHAR(45)`, `sobrenome VARCHAR(45)`, `telefone VARCHAR(20)`, `endereco VARCHAR(100)` | PK `id`; telefone textual para preservar caracteres e zeros à esquerda. |
| `emprestimos` | `id INT`, `data DATETIME`, `data_prevista DATETIME NULL`, `id_cliente INT` | PK `id`; FK `id_cliente` → `clientes.id`. |
| `filmes_emprestimo` | `id INT`, `id_dvd INT`, `id_emprestimo INT` | PK `id`; FKs para `dvds` e `emprestimos`; itens do empréstimo. |
| `devolucoes` | `id INT`, `id_emprestimo INT`, `data DATETIME` | PK `id`; FK para `emprestimos`. |
| `filmes_devolucao` | `id INT`, `id_devolucao INT`, `id_filme_emprestimo INT` | PK `id`; FKs para `devolucoes` e `filmes_emprestimo`; registra quais itens foram devolvidos. |
| `usuarios` | `id INT`, `nome VARCHAR(100)`, `email VARCHAR(100)`, `senha VARCHAR(255)`, `perfil ENUM`, `ativo TINYINT(1)`, `criado_em DATETIME` | PK `id`; `email` é único; senha armazenada em campo de tamanho compatível com hash. |

### Mapeamento das relações

As relações 1:N são implementadas pela FK no lado N: `filmes.id_genero`, `dvds.id_filme`, `emprestimos.id_cliente`, `filmes_emprestimo.id_dvd` e `.id_emprestimo`, `devolucoes.id_emprestimo`, e as duas FKs de `filmes_devolucao`. A relação N:M entre filme e ator é decomposta em duas relações 1:N por `atores_filme`. Empréstimos e DVDs também formam uma relação N:M por meio de `filmes_emprestimo`.

O banco usa `DECIMAL(8,2)` para preço, `DATETIME` para eventos com data e hora, `VARCHAR` para dados textuais de tamanho variável e `INT` para identificadores e quantidades. O script cria também dois usuários iniciais; as credenciais de teste e sua forma de distribuição devem ser verificadas antes de qualquer implantação pública.

### Decisões e melhorias recomendadas

- `emprestimos.data_prevista` é uma decisão intencional do modelo: registra o prazo de entrega de cada empréstimo para distinguir itens dentro do prazo dos ainda pendentes e atrasados. O `EmprestimoModel` preenche esse campo com base no prazo em dias; `calcularAtraso()` compara a data prevista com o momento atual e considera os itens sem vínculo em `filmes_devolucao`. A tela de empréstimos oferece o filtro “Apenas atrasados” e destaca esses resultados. Se `data_prevista` for nula em registros antigos, a consulta usa sete dias após a data do empréstimo como prazo de compatibilidade. O SQL, o código e a finalidade estão alinhados; `CONTRATOS.md` e `PLANO_EXECUCAO.md` agora documentam essa decisão. A lista geral não exibe atualmente uma coluna com a data prevista, portanto essa visualização direta pode ser acrescentada como melhoria de interface.
- `dvds.quantidade` não controla cópias identificadas individualmente. Se for necessário rastrear código de barras, avaria ou situação de cada disco, recomenda-se uma tabela de unidades físicas, uma linha por cópia, ligada ao filme.
- Recomenda-se adicionar validações `CHECK (quantidade > 0)` e `CHECK (valor >= 0)` (conforme a versão do MySQL/MariaDB utilizada), além de restrições únicas nas tabelas associativas para impedir duplicação do mesmo ator no mesmo filme e do mesmo item na mesma devolução.
- `atores.nome` não é único e o seeder procura ator por nome; variações de grafia podem gerar duplicatas. Uma chave externa do TMDB para atores/filmes permitiria identificar registros de forma mais confiável.
- A tabela `usuarios` não registra qual funcionário abriu um empréstimo. Uma FK opcional para o usuário responsável pode ser adicionada caso essa auditoria faça parte dos requisitos.

## 3. Backend PHP e integração com API

A integração de carga inicial está em [seeder_api.php](seeder_api.php), na classe `SeederFilmesAPI`. Ela usa PDO para persistir dados e cURL para consultar os endpoints REST do TMDB. A chave é lida de `$_ENV['TMDB_API_KEY']`, carregada pelo arquivo `config/env.php`; o script interrompe a execução se a chave estiver ausente.

### Fluxo implementado

1. Consulta os gêneros em `/genre/movie/list` no idioma `pt-BR`. Para cada gênero, procura o nome na tabela local e reutiliza ou insere o registro. Cria um mapa entre o ID do gênero no TMDB e o ID local.
2. Consulta páginas de `/movie/popular` com idioma `pt-BR`, avançando a paginação enquanto existirem resultados e a meta de DVDs não for alcançada.
3. Para cada filme, consulta `/movie/{id}/credits`, limita a resposta aos dez primeiros integrantes do elenco, e cria/vincula os atores em `atores` e `atores_filme`.
4. Monta os dados do filme: título truncado a 100 caracteres, gênero convertido para o ID local (com fallback aleatório), preço fixo de R$ 10,00 e URL do pôster quando disponível.
5. Dentro de uma transação PDO por filme, insere o filme, associa o elenco e cria um registro em `dvds` com quantidade aleatória entre 1 e 5. Em falha, executa rollback; em sucesso, commit.
6. Soma a quantidade inserida ao contador de cópias e aguarda 0,25 segundo entre páginas. Ao final, imprime a quantidade total efetivamente gerada.

O script termina chamando `$seeder->popular(2000)`. Portanto, **2.000 é a meta de cópias em estoque, não de títulos**. Como a quantidade é sorteada entre 1 e 5 e a condição de parada é verificada antes da próxima inserção de filme, a última inserção pode fazer o total ultrapassar 2.000. A quantidade real é informada na saída do script.

### Execução e cuidados

1. Configurar `TMDB_API_KEY` no `.env`, sem publicar a chave no repositório.
2. Criar/importar o banco conforme `database/locadora.sql` e confirmar que as credenciais em `config/conexao.php` correspondem ao ambiente.
3. Executar a partir da raiz do projeto, em terminal com PHP, PDO MySQL, cURL habilitado, conexão ao banco e acesso à internet: `php seeder_api.php`.
4. Conferir a mensagem final e validar no banco o total em `SUM(dvds.quantidade)`.

O script foi concebido para carga inicial controlada. Não há verificação por ID externo do TMDB para impedir que reexecuções insiram novamente os filmes; a operação repetida pode duplicar o catálogo. Recomenda-se guardar o ID externo com índice `UNIQUE`, tornar a carga idempotente e validar a resposta HTTP/status e o JSON recebido. O cliente atual trata falha de transporte cURL, mas não configura timeout nem verifica códigos HTTP antes de decodificar a resposta. A pausa de 0,25s é uma espera fixa entre páginas, não um tratamento completo de `429 Too Many Requests`.

## 4. Documentação e defesa

### Justificativa das escolhas

**Filmes com mais de um DVD.** Um título é uma obra do catálogo e pode ter mais de uma cópia disponível para locação. A relação 1:N filme–estoque evita cadastrar o mesmo título diversas vezes apenas para representar cópias. No modelo atual, `dvds.quantidade` armazena esse total de forma compacta. O empréstimo aponta para um registro de estoque por `id_dvd` e a disponibilidade é inferida pelos itens emprestados ainda não devolvidos. Essa escolha simplifica a gestão do estoque; em contrapartida, não identifica nem acompanha avarias de cada unidade física.

**Elenco.** Um filme pode ter vários atores e um ator pode participar de vários filmes. Por isso, a relação é N:M e precisa da tabela associativa `atores_filme`. O campo `personagem` descreve a participação específica daquele ator naquele filme. A tabela separada evita listas de atores serializadas em uma coluna e permite consultas relacionais por filme ou por ator.

**Empréstimos, prazos e devoluções.** `data_prevista` guarda o prazo de entrega calculado ao registrar o empréstimo. Compará-la com a data atual permite identificar atraso; verificar se os itens continuam sem registro em `filmes_devolucao` evita tratar itens já devolvidos como pendentes. A tabela `filmes_devolucao` também possibilita representar devolução parcial. A existência de mais de uma linha em `devolucoes` por empréstimo é permitida pelo DDL atual; regras de negócio como impedir dupla devolução do mesmo item precisam de validação adicional ou restrições únicas.

### Roteiro de apresentação (5 a 7 minutos)

1. **Contexto (30 s):** apresentar o objetivo da aplicação e o stack PHP, MySQL/MariaDB e PDO.
2. **DER (2 min):** mostrar as entidades principais; explicar as cardinalidades 1:N e as tabelas associativas dos relacionamentos N:M.
3. **Decisões de modelagem (1 min):** distinguir título de cópia, esclarecer que o estoque usa quantidade agregada e explicar por que personagem pertence à relação filme–ator.
4. **DDL (1 min):** destacar PKs, FKs, tipos importantes (`DECIMAL`, `DATETIME`, `VARCHAR`) e integridade referencial.
5. **Integração TMDB (1 a 2 min):** apresentar chave por ambiente, paginação, mapeamento de gêneros, importação do elenco, transação por filme e meta de 2.000 cópias.
6. **Limitações e fechamento (1 min):** mencionar arredondamento para cima da meta, possível duplicação em reexecução, ausência de identificação individual das mídias e a possibilidade de exibir a data prevista na lista geral de empréstimos.

### Perguntas prováveis da banca

- **Por que 2.000 DVDs não são 2.000 filmes?** Porque o contador soma `quantidade` da tabela `dvds`; o mesmo filme pode possuir várias cópias.
- **Como o sistema evita IDs de gênero incompatíveis?** O seeder traduz IDs do TMDB para IDs locais após reutilizar ou criar os gêneros no banco.
- **Por que existe `atores_filme`?** Para representar N:M e guardar o personagem específico da participação.
- **O sistema controla cada disco individualmente?** Não. `quantidade` é agregada; rastreamento de número de série ou condição exigiria entidade de cópia física.
- **A carga sempre termina exatamente em 2.000?** Não necessariamente. A quantidade por título varia de 1 a 5, e a última inserção pode ultrapassar a meta.

## Referências do projeto

- [DDL do banco](database/locadora.sql)
- [Seeder e integração TMDB](seeder_api.php)
- [Modelo de filmes](src/Models/Filmes.php)
- [Modelo de disponibilidade de DVDs](src/Models/Dvd.php)
- [Contratos de integração](CONTRATOS.md)
- [Plano de execução](PLANO_EXECUCAO.md)
