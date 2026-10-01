<?php
require_once __DIR__ . '/../../src/Models/Auth.php';
require_once __DIR__ . '/../../src/Models/AtorModel.php';

//Instancia a autenticação usando a conexão carregada pelo modelo
$auth = new Auth();
$auth->exigirEquipe();

$atorModel = new Ator($conn);

$id = (int) ($_GET['id'] ?? 0);

$listarFilmesAtuados = $atorModel->listarFilmeAtuado($id);
$ator = $atorModel->listarAtorPorID($id);

foreach($ator as $nome):
$ator = ($nome['nome']);
endforeach
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
            <form class="form-sair" method="GET" action="atores.php">
                <button type="submit" class="button">Voltar</button>
            </form>
        </div>
    </header>
<body>
    <main class="container conteudo">
        <header class="cabecalho-pagina"><h2><h2>Filmes registrados</h2><p>Relação de filmes de <?= htmlspecialchars($nome['nome']) ?> cadastrados na locadora.</p></header>
        <section class="painel-card">
            <table class="u-full-width"><thead><tr><th>titulo</th><th class="center">Personagem</th><th>Opções</th></thead><tbody>
            <?php foreach ($listarFilmesAtuados as $filmes) : ?>
                <tr>
                    <td><?= htmlspecialchars($filmes['titulo']) ?></td>
                    <td><?= htmlspecialchars($filmes['personagem']) ?> </td>
                    <td class="acao-coluna">
                        <form class="form-cadastro" method="GET" action="editarfilmes.php">
                            <input type="hidden" name="editar" value="editar">
                            <button type="submit" class="button button-primary">Editar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody></table>
        </section>
        <nav class="navegacao"><a href="cadastroFilmeAtor.php">Cadastrar filme deste ator</a>
    </main>
</body>
</html>