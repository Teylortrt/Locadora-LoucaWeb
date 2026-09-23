<?php
require_once __DIR__ . '/../src/Models/Auth.php';
require_once __DIR__ . '/../src/Models/EmprestimoModel.php';
require_once __DIR__ . '/../src/Controllers/EmprestimoController.php';

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
        <form class="form-cadastro" method="GET" action="cadastro.php">
            <button type="submit" class="botao botao-cadastro">Cadastro</button>
        </form>

        <form class="form-catalogo" method="GET" action="filmes/filmes.php">
            <button type="submit" class="botao botao-catalogo">Catálogo</button>
        </form>

        <form class="form-cadastro" method="GET" action="emprestimos/emprestimos.php">
            <button type="submit" class="botao botao-cadastro">Emprestimos</button>
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

        <!-- Aréa de emprestimos rápidos -->
        <section class="painel-card">
                <header class="cabecalho-pagina"><h2>Realizar um emprestimo</h2><p>Preencha os dados para fazer um empréstimo</p></header>
                <form method="POST">
                    <div class="row"><div class="twelve columns"><label for="nome">Nome do cliente</label><input class="u-full-width" type="text" id="nome" name="nome" required></div>
                    <div class="twelve columns"><label for="sobrenome">Nome do filme</label><input class="u-full-width" type="text" id="sobrenome" name="sobrenome" required></div></div>
                    <div class="twelve columns"><label for="endereco">Tempo de locação</label><input class="u-full-width" type="text" id="endereco" name="endereco" required></div></div>
                    <button type="submit" class="button-primary">Realizar emprestimo</button>
                </form>
        </section>

    </main>

    
</body>
</html>
