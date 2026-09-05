<?php
require_once __DIR__ . '/../src/Auth.php';

$auth = new Auth();
$auth->exigirLogin();
$usuario = $auth->usuario();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel — Locadora LoucaWeb</title>
    <link rel="stylesheet" href="../public/css/style.css?v=<?= filemtime(__DIR__ . '/../public/css/style.css') ?>">
</head>
<body>
    <header class="topo">
        <div class="marca">
            <h1>LoucaWeb</h1>
        </div>

        <form class="form-cadastro" method="GET" action="cadastro.php">
            <button type="submit" class="botao botao-cadastro">Cadastro</button>
        </form>

        <form class="form-catalogo" method="GET" action="/Locadora-LoucaWeb/templates/filmes/filmes.php">
            <button type="submit" class="botao botao-catalogo">Catálogo</button>
        </form>
        
        <div class="acoes-topo">
            <form class="form-sair" method="POST" action="../public/logout-web">
                <button type="submit" class="botao botao-sair">Sair</button>
            </form>
        </div>
    </header>

    <main class="conteudo">
        <p class="perfil"><?= htmlspecialchars($usuario['perfil']) ?></p>
        <h2>Olá, <?= htmlspecialchars($usuario['nome']) ?></h2>
    </main>

    
</body>
</html>
