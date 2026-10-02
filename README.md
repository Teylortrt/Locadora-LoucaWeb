# 🎬 Locadora LoucaWeb

> Sistema de Gerenciamento e Controle de Locação para Vídeo Locadora

[![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-MariaDB-4479A1?logo=mysql&logoColor=white)](https://www.mysql.com/)
[![License](https://img.shields.io/badge/Licença-Acadêmico-green)](#licença)

Sistema web desenvolvido em **PHP puro (POO/PDO)** para automatizar a operação de uma vídeo locadora com acervo de aproximadamente **2.000 DVDs**. O catálogo é populado automaticamente via integração com a **API REST do TMDB** (The Movie Database).

---

## 📋 Índice

- [Visão Geral](#-visão-geral)
- [Tecnologias](#-tecnologias)
- [Arquitetura](#-arquitetura)
- [Funcionalidades](#-funcionalidades)
- [Estrutura do Projeto](#-estrutura-do-projeto)
- [Instalação e Configuração](#-instalação-e-configuração)
- [Carga Inicial via TMDB](#-carga-inicial-via-tmdb)
- [Banco de Dados](#-banco-de-dados)
- [API REST](#-api-rest)
- [Documentação do Projeto](#-documentação-do-projeto)
- [Licença](#-licença)

---

## 🎯 Visão Geral

O projeto simula um cenário real de desenvolvimento de software, contemplando:

- **Engenharia de Requisitos** — levantamento de regras de negócio da locadora
- **Modelagem de Dados** — DER conceitual e modelo lógico/relacional
- **Implementação Backend** — PHP 8+ com arquitetura em camadas (Models, Controllers, Services)
- **Integração com API Externa** — consumo da API TMDB para carga automática do acervo
- **Interface Web** — catálogo público + painel administrativo para funcionários

### Fluxo de Acesso

```
Visitante → Catálogo Público (sem login) → [Área do Funcionário] → Login → Painel Administrativo
```

- **Clientes/Visitantes**: acessam o catálogo de filmes com busca, filtros e disponibilidade
- **Funcionários**: gerenciam clientes, empréstimos, devoluções, estoque e relatórios

---

## 🛠️ Tecnologias

| Componente | Tecnologia |
|---|---|
| **Backend** | PHP 8.0+ (Nativo, POO) |
| **Banco de Dados** | MySQL / MariaDB |
| **Acesso ao BD** | PDO (Prepared Statements) |
| **Frontend** | HTML5, CSS3 (Skeleton), JavaScript Vanilla |
| **API Externa** | TMDB (The Movie Database) via cURL |
| **Servidor** | Apache (XAMPP / LAMP) |
| **Versionamento** | Git / GitHub |

---

## 🏗️ Arquitetura

O sistema utiliza uma **arquitetura em camadas** inspirada no padrão MVC, sem framework:

```
┌─────────────────────────────────────────────────┐
│  Templates (Views)                              │
│  templates/*.php — HTML + PHP para renderização  │
├─────────────────────────────────────────────────┤
│  Controllers                                     │
│  src/Controllers/ — Regras de negócio e fluxo    │
├─────────────────────────────────────────────────┤
│  Models                                          │
│  src/Models/ — Acesso ao banco via PDO           │
├─────────────────────────────────────────────────┤
│  Services                                        │
│  src/Services/ — Integrações externas (TMDB)     │
├─────────────────────────────────────────────────┤
│  Front Controller                                │
│  public/index.php — Roteamento de requisições    │
└─────────────────────────────────────────────────┘
```

**Padrões aplicados:**
- **Prepared Statements (PDO)** — prevenção contra SQL Injection
- **Transações ACID** — atomicidade nas operações de empréstimo e carga
- **Sessões PHP** — autenticação e controle de acesso
- **Separação de responsabilidades** — Models não conhecem HTML, Views não acessam o banco

---

## ✨ Funcionalidades

### Área Pública (sem login)
- 📖 Catálogo de filmes com pôsteres, preços e disponibilidade
- 🔍 Pesquisa por título ou ator
- 🏷️ Filtro por gênero
- 📄 Paginação (25 filmes por página)
- 🪟 Modal com detalhes do filme e sinopse (via TMDB)

### Área Administrativa (login obrigatório)
- 🔐 Login/Logout com autenticação por sessão
- 👥 CRUD completo de clientes
- 🎬 Cadastro de filmes com integração TMDB
- 🎭 Associação de atores a filmes (relação N:M)
- 📀 Gestão de estoque de DVDs (quantidade editável por filme)
- 🗑️ Exclusão de filmes com validação de empréstimos ativos
- 📋 Painel de empréstimos com autocomplete de clientes e filmes
- 📤 Registro de devoluções (parcial ou total)
- ⚠️ Filtro de empréstimos atrasados
- 📊 Relatórios (filmes mais alugados, clientes atrasados)

---

## 📁 Estrutura do Projeto

```
Locadora-LoucaWeb/
├── config/
│   ├── conexao.php              # Conexão PDO com o MySQL
│   ├── database.php             # Configurações do banco
│   └── env.php                  # Carrega variáveis do .env
├── database/
│   ├── locadora.sql             # DDL completo (schema + dados iniciais)
│   └── seed_clientes.sql        # 100 clientes fictícios (incorporado ao .sql)
├── image/
│   ├── DER_locadora.mmd         # Diagrama ER editável (Mermaid)
│   └── DER_locadora.png         # Diagrama ER renderizado
├── public/
│   ├── index.php                # Front Controller (rotas e API JSON)
│   ├── css/                     # Stylesheets (Skeleton + custom)
│   └── js/                      # JavaScript (modal, autocomplete)
├── src/
│   ├── Controllers/
│   │   ├── AuthController.php
│   │   ├── ClienteController.php
│   │   ├── DevolucaoController.php
│   │   ├── DvdController.php
│   │   ├── EmprestimoController.php
│   │   └── FilmeController.php
│   ├── Models/
│   │   ├── Auth.php             # Autenticação e sessão
│   │   ├── AtorModel.php        # CRUD de atores e associações
│   │   ├── Cliente.php          # CRUD de clientes
│   │   ├── Dvd.php              # Estoque e disponibilidade
│   │   ├── EmprestimoModel.php  # Empréstimos com validação de cópias livres
│   │   ├── DevolucaoModel.php   # Devoluções
│   │   ├── Filmes.php           # Catálogo com filtros e paginação
│   │   ├── Generos.php          # Gêneros de filme
│   │   ├── PainelEmprestimo.php # Carrinho de sessão
│   │   └── Usuario.php          # Gestão de usuários
│   └── Services/
│       ├── TmdbClient.php       # Consulta de sinopse ao vivo
│       └── RelatorioService.php # Geração de relatórios
├── templates/
│   ├── catalogo.php             # 🔓 Catálogo público (sem login)
│   ├── login.php                # Tela de login
│   ├── painel.php               # Painel administrativo
│   ├── atores/                  # Listagem e associação de atores
│   ├── clientes/                # CRUD de clientes
│   ├── emprestimos/             # Painel de empréstimos
│   ├── filmes/                  # Catálogo admin, cadastro, estoque
│   └── relatorio/               # Relatórios operacionais
├── seeder_api.php               # Script de carga inicial (2.000 DVDs)
├── RELATORIO_PROJETO.md         # Relatório acadêmico de modelagem
├── CONTRATOS.md                 # Contratos entre módulos
├── PLANO_EXECUCAO.md            # Plano de execução
└── README.md                    # Este arquivo
```

---

## 🚀 Instalação e Configuração

### Pré-requisitos

- PHP 8.0 ou superior
- MySQL / MariaDB
- Apache (XAMPP ou LAMP recomendado)
- Extensões PHP: `pdo_mysql`, `curl`
- Chave de API do [TMDB](https://www.themoviedb.org/settings/api)

### 1. Clone o repositório

```bash
cd /opt/lampp/htdocs
git clone <url-do-repositorio>
cd Locadora-LoucaWeb
```

### 2. Crie o banco de dados

```bash
mysql -u root -p < database/locadora.sql
```

O script cria o schema `locadora`, todas as tabelas com suas relações, 100 clientes fictícios e 2 usuários iniciais para teste.

> ⚠️ **Atenção:** Não reimporte sobre um banco existente. O `CREATE TABLE IF NOT EXISTS` não atualiza tabelas já criadas e dados iniciais podem ser duplicados.

### 3. Configure a chave TMDB

Crie um arquivo `.env` na raiz do projeto:

```ini
TMDB_API_KEY=sua_chave_aqui
```

> O `.env` está no `.gitignore` — nunca versione chaves de API.

### 4. Ajuste a conexão (se necessário)

Edite `config/conexao.php` caso seu ambiente use host, porta ou credenciais diferentes do padrão (`127.0.0.1:3306`, `root`, sem senha).

### 5. Acesse o sistema

```
http://localhost/Locadora-LoucaWeb/
```

A página inicial exibe o **catálogo público**. Para acessar o painel administrativo, clique em **"Área do Funcionário"** e faça login.

---

## 🌐 Carga Inicial via TMDB

O script `seeder_api.php` popula automaticamente o banco consumindo a API do TMDB:

```bash
php seeder_api.php
```

### O que o script faz:

1. **Importa gêneros** do TMDB → tabela `generos`
2. **Percorre filmes populares** (paginação automática) → tabela `filmes`
3. **Busca elenco** de cada filme (top 10 atores) → tabelas `atores` + `atores_filme`
4. **Gera estoque** aleatório (1–5 cópias por filme) → tabela `dvds`
5. **Repete** até atingir ~2.000 DVDs no estoque

### Proteções implementadas:

- ✅ Transação ACID por filme (commit/rollback)
- ✅ Verificação de duplicatas por título
- ✅ Validação do HTTP status code da API
- ✅ Rate limiting (250ms entre páginas)
- ✅ Tratamento de erros cURL e JSON
- ✅ Liberação de cursors PDO (`closeCursor`)

> **Nota:** A meta é de **2.000 cópias em estoque**, não 2.000 títulos. Cada filme recebe 1–5 cópias, resultando em aproximadamente 400–600 títulos distintos.

---

## 🗄️ Banco de Dados

### Diagrama Entidade-Relacionamento

<div align="center">
  <img src="image/DER_locadora.png" alt="DER Locadora LoucaWeb" width="500">
</div>

O arquivo editável em Mermaid está em [`image/DER_locadora.mmd`](image/DER_locadora.mmd).

### Tabelas Principais

| Tabela | Descrição | Relações |
|---|---|---|
| `generos` | Categorias de filmes | 1:N com `filmes` |
| `filmes` | Catálogo de títulos | FK → `generos`; 1:N com `dvds` |
| `atores` | Cadastro de atores | N:M com `filmes` via `atores_filme` |
| `atores_filme` | Elenco (tabela associativa) | FKs → `filmes` + `atores`; armazena `personagem` |
| `dvds` | Estoque de cópias | FK → `filmes`; `quantidade` agregada |
| `clientes` | Base de clientes | 1:N com `emprestimos` |
| `emprestimos` | Registro de locações | FK → `clientes`; `data_prevista` para controle de atraso |
| `filmes_emprestimo` | Itens do empréstimo | FKs → `dvds` + `emprestimos` |
| `devolucoes` | Registro de devoluções | FK → `emprestimos` |
| `filmes_devolucao` | Itens devolvidos | FKs → `devolucoes` + `filmes_emprestimo` |
| `usuarios` | Funcionários do sistema | Autenticação com hash de senha |

### Cálculo de Disponibilidade

A disponibilidade de um DVD é calculada em tempo real:

```
Cópias livres = dvds.quantidade - COUNT(filmes_emprestimo sem filmes_devolucao)
```

Isso garante que o sistema nunca permita empréstimos acima do estoque real.

---

## 🔌 API REST

O front controller (`public/index.php`) expõe as seguintes rotas:

| Método | Rota | Descrição |
|---|---|---|
| `GET` | `/filmes/{id}` | Detalhes do filme (JSON) com sinopse TMDB, elenco e disponibilidade |
| `GET` | `/clientes?q={termo}` | Autocomplete de clientes (JSON) |
| `POST` | `/login-web` | Autenticação (redireciona) |
| `POST` | `/logout-web` | Encerrar sessão (redireciona) |
| `POST` | `/emprestimos` | Criar empréstimo |
| `POST` | `/devolucoes` | Registrar devolução |

---

## 📚 Documentação do Projeto

| Documento | Descrição |
|---|---|
| [`RELATORIO_PROJETO.md`](RELATORIO_PROJETO.md) | Relatório acadêmico: modelagem, DDL, integração e defesa |
| [`image/DER_locadora.mmd`](image/DER_locadora.mmd) | Diagrama ER editável (Mermaid) |
| [`image/DER_locadora.png`](image/DER_locadora.png) | Diagrama ER renderizado |
| [`CONTRATOS.md`](CONTRATOS.md) | Contratos de integração entre módulos |
| [`PLANO_EXECUCAO.md`](PLANO_EXECUCAO.md) | Plano de execução do projeto |
| [`database/locadora.sql`](database/locadora.sql) | DDL completo do banco de dados |
| [`seeder_api.php`](seeder_api.php) | Script de carga inicial documentado |

---

## 📄 Licença

Projeto desenvolvido para fins acadêmicos e de estudo.
