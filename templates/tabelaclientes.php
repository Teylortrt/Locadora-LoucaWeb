<?php
require_once __DIR__ . '/../src/Models/Auth.php';
require_once __DIR__ . '/../src/Models/cliente.php';
require_once __DIR__ . '/../src/Controllers/ClienteController.php';
// 3. Instancia as classes passando a variável $conn
$auth = new Auth($conn);
$auth->exigirLogin();

$clienteModel = new Cliente($conn);

// 4. Armazena o retorno do banco em uma variável para o HTML
$listaDeClientes = $clienteModel->listarTodos();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes — Locadora LoucaWeb</title>
    <link rel="stylesheet" href="../public/css/normalize.css">
    <link rel="stylesheet" href="../public/css/skeleton.css">
    <link rel="stylesheet" href="../public/css/style.css">
</head>
<body>
    <main class="container conteudo">
        <header class="cabecalho-pagina"><h2>Clientes registrados</h2><p>Relação de clientes cadastrados na locadora.</p></header>
        <section class="painel-card">
            <table class="u-full-width"><thead><tr><th>Nome</th><th>Sobrenome</th><th class="acao-coluna">Ações</th></tr></thead><tbody>
            <?php foreach ($listaDeClientes as $cliente) : ?>
                <tr>
                    <td><?= htmlspecialchars($cliente['nome']) ?></td>
                    <td><?= htmlspecialchars($cliente['sobrenome']) ?></td>
                    <td class="acao-coluna">
                        <form class="form-excluir" method="GET" action="../src/Controllers/excluirclientescontroller.php">
                            <input type="hidden" name="id" value="<?= (int) $cliente['id'] ?>">
                            <button type="submit" class="button botao-excluir">Excluir</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody></table>
        </section>
        <nav class="navegacao"><a href="cadastro.php">Cadastrar cliente</a> &nbsp;·&nbsp; <a href="painel.php">Voltar ao painel</a></nav>
    </main>
</body>
</html>
