<?php
require_once __DIR__ . '/../../src/Models/Auth.php';

$auth = new Auth();
$auth->exigirLogin();
$usuario = $auth->usuario();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Opções de clientes — Locadora LoucaWeb</title>
    <link rel="icon" href="../../image/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="../../public/css/normalize.css">
    <link rel="stylesheet" href="../../public/css/skeleton.css">
    <link rel="stylesheet" href="../../public/css/style.css?v=<?= filemtime(__DIR__ . '/../../public/css/style.css') ?>">
</head>
<body>
    <header class="topo">
        <div class="container">
            <div class="marca"><h1>LoucaWeb</h1></div>
            <a class="button" href="../painel.php">Voltar ao painel</a>
        </div>
    </header>
    <main class="container conteudo">
        <section class="painel-card">
            <p class="perfil"><?= htmlspecialchars($usuario['perfil']) ?></p>
            <h2>Opções de clientes</h2>
            <p class="descricao">Olá, <?= htmlspecialchars($usuario['nome']) ?>. Escolha uma opção:</p>
            <nav class="menu-clientes" aria-label="Opções de clientes">
                <a class="item-menu-cliente" href="cadastro.php"><strong>Cadastrar cliente</strong><span>Adicionar um novo cliente</span></a>
                <a class="item-menu-cliente" href="tabelaclientes.php"><strong>Tabela de clientes</strong><span>Consultar e editar clientes cadastrados</span></a>
                <a class="item-menu-cliente" href="historico.php"><strong>Histórico de empréstimos</strong><span>Consultar empréstimos e devoluções</span></a>
            </nav>
        </section>
    </main>
</body>
</html>
