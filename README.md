# 🎬 Locadora LoucaWeb - Backend API

Sistema de gerenciamento de locadora de DVDs desenvolvido em **PHP Puro** (sem frameworks) com arquitetura REST API.

## 📁 Estrutura do Projeto

```
locadora-backend/
│
├── public/                      # Ponto de entrada público do servidor web
│   ├── index.php                # Front Controller — recebe e roteia requisições
│   └── .htaccess                # Redirecionamento de URLs (Apache)
│
├── src/                         # Código-fonte da aplicação
│   ├── Controllers/             # Lógica de negócio e respostas às requisições
│   ├── Models/                  # Representação das tabelas do banco
│   ├── Services/                # Regras complexas (API externa, cálculo de multa)
│   └── Database/
│       └── Connection.php       # Conexão PDO Singleton com o schema `locadora`
│
├── config/
│   └── database.php             # Credenciais e configuração do banco
│
├── database/
│   └── locadora.sql             # Script DDL do banco de dados
│
└── README.md                    # Este arquivo
```

## 🚀 Como Rodar

### 1. Requisitos
- PHP 8.0+
- MySQL/MariaDB
- Apache com `mod_rewrite` habilitado (XAMPP/LAMP)

### 2. Instalação
```bash
# Clone ou baixe o repositório
cd /opt/lampp/htdocs/Locadora-LoucaWeb

# Importe o banco de dados
mysql -u root -p < database/locadora.sql

# Configure as credenciais do banco
# Edite: config/database.php
```

### 3. Acessar a API
```
http://localhost/Locadora-LoucaWeb/public/
```

## 🔗 Endpoints Disponíveis

| Método | Endpoint    | Descrição              | Status |
|--------|-------------|------------------------|--------|
| GET    | `/`         | Status da API          | ✅     |
| GET    | `/filmes`   | Listar todos os filmes | ✅     |

## 🏗️ Decisões de Modelagem

### Banco de Dados
- Schema: `locadora`
- Tabelas: `filmes`, `generos`, `clientes`, `atores`, `dvds`, `emprestimos`, `devolucoes`
- Relacionamentos N:N implementados via tabelas pivot

### Arquitetura
- **Padrão Singleton** para conexão do banco
- **PSR-4 Autoloading** manual (sem Composer)
- **Front Controller Pattern** centralizando requisições
- **API REST** retornando apenas JSON

### Segurança
- Uso de **PDO com Prepared Statements**
- Headers CORS configurados
- Tratamento de erros com status HTTP apropriados

---

**Desenvolvido para fins educacionais**  
Disciplina: Banco de Dados & Desenvolvimento Backend
