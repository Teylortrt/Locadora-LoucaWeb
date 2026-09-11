<?php
require_once __DIR__ . '/../src/Models/Auth.php';

$auth = new Auth();

if ($auth->logado()) {
    header('Location: painel.php');
    exit;
}

$erro = isset($_GET['erro']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Locadora LoucaWeb</title>
    <link rel="stylesheet" href="../public/css/normalize.css">
    <link rel="stylesheet" href="../public/css/skeleton.css">
    <link rel="stylesheet" href="../public/css/style.css">
</head>
<body>
    <main class="tela-central">
        <div class="container">
        <form class="card six columns offset-by-three" method="post" action="../public/login-web">
            <div class="marca">
                <span>Locadora</span>
                <h1>LoucaWeb</h1>
            </div>

            <div>
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" required autocomplete="username">
            </div>

            <div>
                <label for="senha">Senha</label>
                <input type="password" id="senha" name="senha" required autocomplete="current-password">
            </div>

            <button type="submit" class="button-primary u-full-width">Entrar</button>

            <?php if ($erro): ?>
                <p class="mensagem erro">E-mail ou senha inválidos.</p>
            <?php endif; ?>
        </form>
        </div>
    </main>
</body>
</html>
