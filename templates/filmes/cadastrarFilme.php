<?php

// Carrega as dependências necessárias para autenticação e acesso aos filmes.
require_once __DIR__ . '/../../src/Models/Auth.php';
require_once __DIR__ . '/../../src/Models/Filmes.php';
require_once __DIR__ . '/../../src/Models/Generos.php';
require_once __DIR__ . '/../../src/Controllers/FilmeController.php';


// Garante que somente usuários autenticados acessem o catálogo.
$auth = new Auth();
$auth->exigirLogin();

$generosModel = new Generos($conn);
$filmeModel = new Filmes($conn);
$filmeController = new FilmesController($conn);

// Lista os gêneros disponíveis para o formulário de cadastro.
$listarGeneros = $generosModel->listarGeneros();

// Processa o formulário de cadastro de filme quando submetido.
$filmeController->adicionarFilme($_POST);


?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>catalogo</title>
    <link rel="stylesheet" href="../../public/css/normalize.css">
    <link rel="stylesheet" href="../../public/css/skeleton.css">
    <link rel="stylesheet" href="../../public/css/style.css?v=<?= filemtime(__DIR__ . '/../../public/css/style.css') ?>">
    <style>
        
        .paginacao { margin-top: 20px; }
        .paginacao a { padding: 5px 10px; border: 1px solid #ccc; text-decoration: none; color: #333; margin-right: 5px;}
        .paginacao a.ativo { background-color: #007BFF; color: white; border-color: #007BFF; }
    </style>
</head>
<body>

    <header class="topo">
        <div class="container">
            <div class="marca">
                <span>Locadora</span>
                <h1>LoucaWeb</h1>
            </div>

            <form class="acoes-topo" method="GET" action="filmes.php">
                <button type="submit" class="button">Voltar</button>
            </form>
        </div>
    </header>

    <main class="container conteudo cadastrar-filme">
        <div class="row">
            <div class="eight columns offset-by-two">
                <div class="card">
                    <h2>Adicionar Filme</h2>
                    <form method="POST" action="">
                        <div class="row">
                            <div class="six columns">
                                <label for="titulo">Título</label>
                                <input class="u-full-width" type="text" id="titulo" name="titulo" required>
                            </div>
                            <div class="six columns">
                                <label for="id_genero">Gênero</label>
                                <select class="u-full-width" id="id_genero" name="id_genero" required>
                                    <?php
                                    //listar nome dos generos no select
                                    foreach ($listarGeneros as $genero) {
                                        echo "<option value=\"{$genero['id']}\">" . htmlspecialchars($genero['genero']) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <label for="poster_url">URL do Poster</label>
                        <input class="u-full-width" type="url" id="poster_url" name="poster_url">

                        <label for="valor">Valor</label>
                        <input class="u-full-width" type="number" step="0.01" id="valor" name="valor" required>

                        <button class="button-primary" type="submit">Adicionar Filme</button>
                    </form>
                </div>
            </div>
        </div>
    </main>            
</body>
</html>


