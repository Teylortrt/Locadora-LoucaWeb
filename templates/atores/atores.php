<?php
require_once __DIR__ . '/../../src/Models/Auth.php';
require_once __DIR__ . '/../../src/Models/AtorModel.php';

//Instancia a autenticação usando a conexão carregada pelo modelo
$auth = new Auth();
$auth->exigirLogin();

$atorModel = new Ator($conn);

$listarAtores = $atorModel->listarTodos()

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atores — Locadora LoucaWeb</title>
    <link rel="icon" href="../image/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="../../public/css/normalize.css">
    <link rel="stylesheet" href="../../public/css/skeleton.css">
    <link rel="stylesheet" href="../../public/css/style.css">
</head>
 <header class="topo">
        <div class="container">
            <div class="marca">
                <span>Locadora</span>
                <h1>LoucaWeb</h1>
            </div>
            <form class="form-sair" method="GET" action="../filmes/painel.php">
                <button type="submit" class="button">Voltar</button>
            </form>
        </div>
    </header>
<body>
    <main class="container conteudo">
        <header class="cabecalho-pagina"><h2>Atores registrados</h2><p>Relação de atores cadastrados na locadora.</p></header>
        <section class="painel-card">
            <table class="u-full-width"><thead><tr><th>Nome</th><th class="center">Ações</th></tr></thead><tbody>
            <?php foreach ($listarAtores as $ator) : ?>
                <tr>
                    <td><?= htmlspecialchars($ator['nome']) ?></td>
                    <td class="acao-coluna">
                        <form class="form-cadastro" method="GET" action="atoresFilmes.php">
                            <input type="hidden" name="id" value="<?= (int) $ator['id'] ?>">
                            <button type="submit" class="button button-primary">Filmes Atuados</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody></table>
        </section>
        <nav class="navegacao"><a href="cadastroAtor.php">Cadastrar ator</a>
    </main>
</body>
</html>
