<?php
require_once __DIR__ . '/../src/Models/Auth.php';

$auth = new Auth();
if ($auth->logado()) {
    $destino = ($auth->usuario()['perfil'] ?? '') === 'cliente'
        ? 'filmes/filmes.php'
        : 'painel.php';
    header('Location: ' . $destino);
    exit;
}

$mensagensErro = [
    'dados' => 'Confira os dados, use uma senha com pelo menos 8 caracteres e confirme-a corretamente.',
    'email' => 'Este e-mail já está associado a uma conta.',
    'indisponivel' => 'Não foi possível criar o acesso agora. Tente novamente.',
];
$erro = $mensagensErro[$_GET['erro'] ?? ''] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar acesso de cliente — Locadora LoucaWeb</title>
    <link rel="icon" href="../image/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="../public/css/normalize.css">
    <link rel="stylesheet" href="../public/css/skeleton.css">
    <link rel="stylesheet" href="../public/css/style.css">
</head>
<body>
    <main class="tela-central">
        <div class="container">
            <form class="card six columns offset-by-three" method="post" action="../public/cadastro-cliente-web">
                <div class="marca">
                    <span>Locadora</span>
                    <h1>LoucaWeb</h1>
                </div>

                <h2>Criar acesso de cliente</h2>
                <div class="row">
                    <div class="six columns">
                        <label for="nome">Nome</label>
                        <input class="u-full-width" type="text" id="nome" name="nome" maxlength="45" required autocomplete="given-name">
                    </div>
                    <div class="six columns">
                        <label for="sobrenome">Sobrenome</label>
                        <input class="u-full-width" type="text" id="sobrenome" name="sobrenome" maxlength="45" required autocomplete="family-name">
                    </div>
                </div>
                <label for="telefone">Telefone</label>
                <input class="u-full-width" type="tel" id="telefone" name="telefone" maxlength="20" required autocomplete="tel">

                <label for="endereco">Endereço</label>
                <input class="u-full-width" type="text" id="endereco" name="endereco" maxlength="100" required autocomplete="street-address">

                <label for="email">E-mail</label>
                <input class="u-full-width" type="email" id="email" name="email" maxlength="100" required autocomplete="email">

                <label for="senha">Senha</label>
                <input class="u-full-width" type="password" id="senha" name="senha" minlength="8" required autocomplete="new-password">

                <label for="confirmacao_senha">Confirme a senha</label>
                <input class="u-full-width" type="password" id="confirmacao_senha" name="confirmacao_senha" minlength="8" required autocomplete="new-password">

                <button type="submit" class="button-primary u-full-width">Criar acesso</button>

                <?php if ($erro !== ''): ?>
                    <p class="mensagem erro"><?= htmlspecialchars($erro) ?></p>
                <?php endif; ?>
                <p><a href="login.php">Já tenho acesso</a></p>
            </form>
        </div>
    </main>
</body>
</html>