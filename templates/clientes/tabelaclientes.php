<?php
require_once __DIR__ . '/../../config/conexao.php';
require_once __DIR__ . '/../../src/Models/Auth.php';
require_once __DIR__ . '/../../src/Models/Cliente.php';

$auth = new Auth();
$auth->exigirLogin();

$clienteModel = new \App\Models\Cliente($conn);
$listaDeClientes = $clienteModel->listar();
$mensagemSucesso = filter_input(INPUT_GET, 'sucesso', FILTER_UNSAFE_RAW);
$mensagemErro = filter_input(INPUT_GET, 'erro', FILTER_UNSAFE_RAW);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes — Locadora LoucaWeb</title>
    <link rel="icon" href="../../image/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="../../public/css/normalize.css">
    <link rel="stylesheet" href="../../public/css/skeleton.css">
    <link rel="stylesheet" href="../../public/css/style.css">
</head>
<body>
    <main class="container conteudo">
        <header class="cabecalho-pagina"><h2>Clientes registrados</h2><p>Relação de clientes cadastrados na locadora.</p></header>
        <?php if ($mensagemSucesso) : ?>
            <p role="status"><?= htmlspecialchars($mensagemSucesso, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <?php if ($mensagemErro) : ?>
            <p role="alert"><?= htmlspecialchars($mensagemErro, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <section class="painel-card tabela-clientes" aria-label="Lista de clientes cadastrados">
            <?php if (empty($listaDeClientes)) : ?>
                <p class="clientes-vazio">Nenhum cliente cadastrado ainda.</p>
            <?php else : ?>
                <table class="u-full-width">
                    <thead>
                        <tr>
                            <th scope="col">Nome</th>
                            <th scope="col">Sobrenome</th>
                            <th scope="col">Telefone</th>
                            <th scope="col">Endereço</th>
                            <th scope="col" class="acao-coluna">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($listaDeClientes as $cliente) : ?>
                            <tr>
                                <td><?= htmlspecialchars($cliente['nome']) ?></td>
                                <td><?= htmlspecialchars($cliente['sobrenome']) ?></td>
                                <td><?= htmlspecialchars($cliente['telefone']) ?></td>
                                <td><?= htmlspecialchars($cliente['endereco']) ?></td>
                                <!-- botao de ações: editar, histórico, excluir -->
                                <td class="acao-coluna">
                                    <details class="menu-acoes">
                                        <summary aria-label="Abrir ações de <?= htmlspecialchars($cliente['nome'] . ' ' . $cliente['sobrenome'], ENT_QUOTES, 'UTF-8') ?>" title="Ações">⋮</summary>
                                        <div class="menu-acoes-opcoes">
                                            <a href="../../templates/clientes/editarclientes.php?id=<?= (int) $cliente['id'] ?>">Editar cliente</a>
                                            <a href="../../templates/clientes/historico.php?id=<?= (int) $cliente['id'] ?>">Ver histórico</a>
                                            <form method="POST" action="../../src/Controllers/excluirclientescontroller.php" onsubmit="return confirm('Tem certeza que deseja excluir este cliente? O histórico de empréstimos também será removido.')">
                                                <input type="hidden" name="id" value="<?= (int) $cliente['id'] ?>">
                                                <button type="submit" class="opcao-excluir">Excluir cliente</button>
                                            </form>
                                        </div>
                                    </details>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
        <nav class="navegacao"><a href="../../templates/clientes/cadastro.php">Cadastrar cliente</a> &nbsp;·&nbsp; 
        <a href="../../templates/clientes/clientes.php">Voltar aos clientes</a></nav>
    </main>
</body>
</html>
