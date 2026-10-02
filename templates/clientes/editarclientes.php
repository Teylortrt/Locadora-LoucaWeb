<?php
require_once __DIR__ . '/../../config/conexao.php';
require_once __DIR__ . '/../../src/Models/Auth.php';

$auth = new Auth();
$auth->exigirLogin();
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header('Location: tabelaclientes.php');
    exit;
}

$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $sobrenome = trim($_POST['sobrenome'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $endereco = trim($_POST['endereco'] ?? '');
    if ($nome === '' || $sobrenome === '' || $telefone === '' || $endereco === '') {
        $erro = 'Preencha todos os campos para salvar as alterações.';
    } else {
        $stmt = $conn->prepare('UPDATE clientes SET nome = :nome, sobrenome = :sobrenome, telefone = :telefone, endereco = :endereco WHERE id = :id');
        $stmt->execute([':nome' => $nome, ':sobrenome' => $sobrenome, ':telefone' => $telefone, ':endereco' => $endereco, ':id' => $id]);
        header('Location: editarclientes.php?id=' . $id . '&salvo=1');
        exit;
    }
}

$stmt = $conn->prepare('SELECT id, nome, sobrenome, telefone, endereco FROM clientes WHERE id = :id');
$stmt->execute([':id' => $id]);
$cliente = $stmt->fetch();
if (!$cliente) {
    http_response_code(404);
    exit('Cliente não encontrado.');
}
$esc = static fn($valor) => htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar cliente — LoucaWeb</title>
    <link rel="icon" href="../../image/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="../../public/css/normalize.css">
    <link rel="stylesheet" href="../../public/css/skeleton.css">
    <link rel="stylesheet" href="../../public/css/style.css?v=<?= filemtime(__DIR__ . '/../../public/css/style.css') ?>">
</head>
<body>
    <header class="topo"><div class="container"><div class="marca"><h1>LoucaWeb</h1></div><a class="button" href="tabelaclientes.php">Voltar aos clientes</a></div></header>
    <main class="container conteudo pagina-cliente">
        <header class="cabecalho-pagina"><span class="cliente-identificador">Cliente #<?= (int) $cliente['id'] ?></span><h2><?= $esc($cliente['nome'] . ' ' . $cliente['sobrenome']) ?></h2><p>Atualize as informações de cadastro deste cliente.</p></header>
        <section class="painel-card formulario-cliente">
            <h3>Dados do cliente</h3>
            <?php if (isset($_GET['salvo'])): ?><p class="mensagem sucesso" role="status">Alterações salvas com sucesso.</p><?php endif; ?>
            <?php if ($erro): ?><p class="mensagem" role="alert"><?= $esc($erro) ?></p><?php endif; ?>
            <form method="POST">
                <div class="row"><div class="six columns"><label for="nome">Nome</label><input class="u-full-width" type="text" id="nome" name="nome" maxlength="45" value="<?= $esc($cliente['nome']) ?>" required></div><div class="six columns"><label for="sobrenome">Sobrenome</label><input class="u-full-width" type="text" id="sobrenome" name="sobrenome" maxlength="45" value="<?= $esc($cliente['sobrenome']) ?>" required></div></div>
                <div class="row"><div class="six columns"><label for="telefone">Telefone</label><input class="u-full-width" type="tel" id="telefone" name="telefone" maxlength="20" value="<?= $esc($cliente['telefone']) ?>" required></div><div class="six columns"><label for="endereco">Endereço</label><input class="u-full-width" type="text" id="endereco" name="endereco" maxlength="100" value="<?= $esc($cliente['endereco']) ?>" required></div></div>
                <button type="submit" class="button-primary">Salvar alterações</button>
            </form>
        </section>
    </main>
</body>
</html>
