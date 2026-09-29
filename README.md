# Locadora LoucaWeb

Sistema web de gerenciamento de uma locadora de filmes, desenvolvido em PHP puro. O projeto reúne autenticação, cadastro de clientes, catálogo e elenco, estoque de DVDs e fluxos de empréstimo e devolução.

## Visão geral

O sistema combina:

- autenticação de usuários via sessão;
- painel administrativo para navegação do sistema;
- catálogo de filmes em interface web;
- backend PHP com Models, Controllers e serviços;
- banco de dados relacional MySQL/MariaDB;
- integração com o TMDB para consultar informações de filmes e carregar o catálogo inicial.

O projeto foi pensado para funcionar em ambiente local com XAMPP/LAMP e utiliza PDO para acesso ao MySQL.

## Tecnologias

- PHP 8+
- MySQL / MariaDB
- HTML, CSS e JavaScript simples
- PDO para conexão e consultas ao banco
- Arquitetura em camadas com Models, Controllers e templates

## Funcionalidades

- Login e logout de usuários
- Proteção de páginas por autenticação
- Cadastro e gerenciamento de clientes
- Catálogo, detalhes de filmes, elenco e disponibilidade de cópias
- Registro de empréstimos, itens e devoluções
- Consulta e filtro de empréstimos atrasados usando a data prevista de entrega
- Endpoints PHP para operações do sistema e consultas em JSON
- Script de carga inicial de filmes, gêneros, elenco e estoque via TMDB

## Estrutura do projeto

```text
Locadora-LoucaWeb/
├── config/
│   ├── conexao.php
│   └── env.php
├── database/
│   └── locadora.sql
├── image/
│   ├── DER_locadora.mmd
│   └── DER_locadora.png
├── public/                 # Front controller, rotas e arquivos públicos
├── src/
│   ├── Controllers/
│   ├── Database/
│   ├── Models/
│   └── Services/
├── templates/
│   ├── emprestimos/
│   ├── filmes/
│   └── ...
├── CONTRATOS.md
├── PLANO_EXECUCAO.md
├── RELATORIO_PROJETO.md
├── README.md
├── seeder_api.php
└── ...
```

## Requisitos

- PHP 8 ou superior
- MySQL/MariaDB
- Servidor web Apache (XAMPP/LAMP recomendado)
- Extensões PHP `pdo_mysql` e `curl`
- Chave de API do TMDB para integração e carga do catálogo

## Configuração do ambiente

### 1. Clone ou baixe o projeto

```bash
cd /opt/lampp/htdocs
git clone <url-do-repositorio>
cd Locadora-LoucaWeb
```

### 2. Configure a conexão com o banco

O projeto usa as configurações locais de `config/conexao.php` (por padrão, MySQL em `127.0.0.1:3306`, banco `locadora` e usuário `root`). Ajuste host, porta, banco, usuário e senha conforme seu ambiente.

### 3. Crie o banco de dados

No phpMyAdmin ou via terminal, importe o script:

```bash
mysql -u root -p < database/locadora.sql
```

O script cria o schema `locadora`, suas tabelas, relações e usuários iniciais.

### 4. Configure a chave TMDB

Crie um arquivo `.env` na raiz do projeto com a chave da API:

```ini
TMDB_API_KEY=sua_chave_do_tmdb
```

O `.env` é lido por `config/env.php` e está ignorado pelo Git. Não compartilhe nem versione uma chave real.

### 5. Inicie o servidor local

A raiz do projeto usa `public/index.php` como front controller; o `.htaccess` define esse arquivo como página inicial. Acesse:

```text
http://localhost/Locadora-LoucaWeb/
```

A página inicial redireciona para a tela de login. Para usar as subrotas listadas abaixo, configure o Apache para encaminhar essas URLs a `public/index.php`; o `.htaccess` fornecido não contém regras de rewrite para elas.

## Acesso ao sistema

O acesso às páginas administrativas é protegido por sessão. Após o login, o usuário pode acessar:

- painel administrativo;
- catálogo de filmes;
- cadastro de clientes;
- empréstimos, devoluções e consultas de atraso.

## API

O front controller em `public/index.php` expõe rotas para o sistema. As rotas de consulta respondem em JSON; as rotas web de autenticação redirecionam para as páginas da aplicação.

### Rotas implementadas

```text
GET  /Locadora-LoucaWeb/public/filmes/{id}
GET  /Locadora-LoucaWeb/public/clientes?q={termo}
POST /Locadora-LoucaWeb/public/login-web
POST /Locadora-LoucaWeb/public/logout-web
POST /Locadora-LoucaWeb/public/emprestimos
POST /Locadora-LoucaWeb/public/devolucoes
```

O detalhe do filme inclui sinopse consultada no TMDB, elenco local e disponibilidade calculada. A consulta de clientes é usada no autocomplete de empréstimos. As rotas de login e logout fazem parte do fluxo web; empréstimos e devoluções são enviados aos respectivos controllers.

## Carga inicial via TMDB

O script `seeder_api.php` consulta filmes populares, importa gêneros e elenco e cria registros de estoque. Com o banco configurado e `TMDB_API_KEY` definido, execute-o uma vez a partir da raiz:

```bash
php seeder_api.php
```

A meta configurada é de 2.000 cópias de DVDs, não de 2.000 títulos. Cada filme recebe de 1 a 5 cópias, então o total pode ultrapassar a meta na última inserção. O script foi feito para carga inicial controlada: reexecutá-lo pode duplicar filmes e estoque.

## Banco de dados

O schema principal é `locadora`, definido em `database/locadora.sql`, com tabelas como:

- filmes
- generos
- clientes
- atores
- dvds
- `emprestimos` (inclui `data_prevista`, usada para identificar atraso)
- `filmes_emprestimo`
- `devolucoes` e `filmes_devolucao`
- `usuarios`

O estoque em `dvds.quantidade` representa cópias agregadas por registro, não unidades físicas identificadas individualmente. O elenco N:M entre filmes e atores é representado por `atores_filme`, que também armazena o personagem.

## Documentação do projeto

- [Relatório de modelagem, DDL, integração e defesa](RELATORIO_PROJETO.md)
- [Diagrama ER editável em Mermaid](image/DER_locadora.mmd)
- [Imagem do DER](image/DER_locadora.png)
- [Contratos entre módulos](CONTRATOS.md)
- [Plano de execução](PLANO_EXECUCAO.md)

## Observações

- O projeto foi desenvolvido em PHP puro, sem framework.
- A organização está em torno de classes de domínio e controllers.
- O acesso às páginas administrativas usa autenticação por sessão.
- O detalhe do filme consulta sinopse no TMDB; para essa consulta, o PHP precisa permitir requisições externas via `file_get_contents` (`allow_url_fopen`).

## Licença

Projeto desenvolvido para fins acadêmicos e de estudo.

---
