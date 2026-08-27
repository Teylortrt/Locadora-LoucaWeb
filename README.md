## 📁 Organização do Projeto

```text
Locadoura-loucaweb/
├── app/
│   ├── Http/
│   │   └── Controllers/   # Controladores e regras do sistema
│   └── Models/            # Modelos e acesso aos dados
│
├── database/
│   └── migrations/        # Estrutura do banco de dados
│
├── resources/
│   └── views/              # Páginas e interfaces do sistema
│
├── routes/
│   └── web.php             # Rotas da aplicação
│
├── public/                 # Arquivos públicos
├── config/                 # Configurações do Laravel
├── storage/                # Logs e arquivos gerados
├── vendor/                 # Dependências do Composer
├── .env                    # Configurações locais
└── artisan                 # Comandos do Laravel
```

### Principais diretórios

* **app/** → lógica principal da aplicação.
* **Controllers/** → recebem as requisições e controlam as ações.
* **Models/** → representam os dados e fazem a comunicação com o banco.
* **database/** → arquivos relacionados ao banco de dados.
* **resources/views/** → páginas do sistema utilizando Blade.
* **routes/** → definição das rotas da aplicação.
* **public/** → arquivos acessíveis publicamente.
* **.env** → configurações do ambiente, como banco de dados.
