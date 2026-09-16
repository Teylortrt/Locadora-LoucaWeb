# Locadora LoucaWeb

Sistema web de gerenciamento de uma locadora de filmes, desenvolvido em PHP puro com foco em autenticação, catálogo de filmes e interface administrativa.

## Visão geral

A aplicação combina:

- autenticação de usuários via sessão;
- painel administrativo para navegação do sistema;
- catálogo de filmes em interface web;
- estrutura de backend para operações em PHP;
- banco de dados relacional para armazenar filmes, clientes, usuários e movimentações da locadora.

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
- Visualização de catálogo de filmes
- Estrutura de banco para filmes, gêneros, atores, DVDs e empréstimos
- Endpoints de API para acesso aos dados em JSON

## Estrutura do projeto

```text
Locadora-LoucaWeb/
├── config/
│   ├── conexao.php
│   └── env.php
├── database/
│   └── locadora.sql
├── image/
├── public/
│   ├── css/
│   ├── index.php
│   └── ...
├── src/
│   ├── Controllers/
│   ├── Models/
│   ├── Services/
│   └── Database/
├── templates/
│   ├── cadastro.php
│   ├── editarclientes.php
│   ├── login.php
│   ├── painel.php
│   ├── tabelaclientes.php
│   └── filmes/
├── README.md
├── seeder_api.php
└── ...
```

## Requisitos

- PHP 8 ou superior
- MySQL/MariaDB
- Servidor web Apache (XAMPP/LAMP recomendado)
- Extensão PDO habilitada para MySQL

## Configuração do ambiente

### 1. Clone ou baixe o projeto

```bash
cd /opt/lampp/htdocs
git clone <url-do-repositorio>
cd Locadora-LoucaWeb
```

### 2. Crie o banco de dados

No phpMyAdmin ou via terminal, importe o script:

```bash
mysql -u root -p < database/locadora.sql
```

Se necessário, ajuste as credenciais de conexão no arquivo:

- config/conexao.php

Verifique também se o banco, usuário, senha e porta do MySQL coincidem com o ambiente local.

### 3. Inicie o servidor local

Acesse a pasta do projeto no navegador em:

```text
http://localhost/Locadora-LoucaWeb/public/
```

A página inicial do sistema redireciona para a tela de login.

## Acesso ao sistema

A aplicação possui fluxo web com autenticação por sessão. A partir da tela de login, o usuário pode acessar:

- painel administrativo;
- catálogo de filmes;
- cadastro de clientes;
- navegação pelas páginas do sistema.

## API

O projeto também expõe rotas simples em PHP para consulta de dados em JSON.

### Exemplos

```text
GET /Locadora-LoucaWeb/public/filmes
POST /Locadora-LoucaWeb/public/login
POST /Locadora-LoucaWeb/public/cadastrar
```

A rota `/filmes` retorna a listagem de filmes em formato JSON.

## Banco de dados

O schema principal é `locadora`, com entidades como:

- filmes
- generos
- clientes
- atores
- dvds
- emprestimos
- devolucoes
- usuarios

O script SQL em `database/locadora.sql` cria a estrutura e também insere usuários iniciais para uso no sistema.

## Observações

- O projeto foi desenvolvido em PHP puro, sem framework.
- A organização está em torno de classes de domínio e controllers.
- O acesso às páginas internas usa autenticação por sessão.

## Licença

Projeto desenvolvido para fins acadêmicos e de estudo.

---
