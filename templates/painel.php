<?php
require_once __DIR__ . '/../src/Models/Auth.php';

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
    <link rel="icon" href="../image/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="../public/css/normalize.css">
    <link rel="stylesheet" href="../public/css/skeleton.css">
    <link rel="stylesheet" href="../public/css/style.css?v=<?= filemtime(__DIR__ . '/../public/css/style.css') ?>">
</head>
<body>
    <header class="topo">
      <div class="container">
        <div class="marca">
            <h1>LoucaWeb</h1>
        </div>
        <nav class="botoes-painel" aria-label="Navegação principal">
            <a class="button" href="filmes/filmes.php">Catálogo</a>
            <a class="button" href="clientes/clientes.php">Clientes</a>
            <form class="form-sair" method="POST" action="../public/logout-web">
                <button type="submit" class="button botao-sair">Sair</button>
            </form>
        </nav>
      </div>
    </header>

    <main class="container conteudo">
        <section class="painel-card">
            <p class="perfil"><?= htmlspecialchars($usuario['perfil']) ?></p>
            <h2>Olá, <?= htmlspecialchars($usuario['nome']) ?></h2>
            <p class="descricao">Use o menu para acessar as operações da locadora.</p>
        </section>
    </main>

    
</body>
</html>
