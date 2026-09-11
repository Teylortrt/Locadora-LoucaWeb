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
    <link rel="stylesheet" href="../public/css/normalize.css">
    <link rel="stylesheet" href="../public/css/skeleton.css">
    <link rel="stylesheet" href="../public/css/style.css?v=<?= filemtime(__DIR__ . '/../public/css/style.css') ?>">
</head>
<body>
    <header class="topo">
      <div class="container">
        <div class="marca">
            <span>Locadora</span>
            <h1>LoucaWeb</h1>
        </div>
        <form class="form-cadastro" method="GET" action="tabelaclientes.php">
            <button type="submit" class="button button-primary botao-cadastro">Clientes</button>
        </form>
        <div class="acoes-topo">
            <form class="form-sair" method="POST" action="../public/logout-web">
                <button type="submit" class="button botao-sair">Sair</button>
            </form>
        </div>
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
