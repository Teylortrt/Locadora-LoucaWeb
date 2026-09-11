<?php
// 1. Carrega a conexão (isso também inicia a sessão automaticamente)
// 2. Carrega as classes
require_once __DIR__ . '/../src/Models/Auth.php';
require_once __DIR__ . '/../src/Models/Filmes.php';

// 3. Instancia as classes passando a variável $conn
$auth = new Auth($conn);
$auth->exigirLogin();

$filmeModel = new Filmes($conn);

// 4. Armazena o retorno do banco em uma variável para o HTML
$listaDeFilmes = $filmeModel->listarTodos();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Filmes — Locadora LoucaWeb</title>
    <link rel="stylesheet" href="../public/css/normalize.css">
    <link rel="stylesheet" href="../public/css/skeleton.css">
    <link rel="stylesheet" href="../public/css/style.css">
</head>
<body>
    <main class="container conteudo">
        <header class="cabecalho-pagina"><h2>Catálogo de filmes</h2><p>Confira os títulos disponíveis na locadora.</p></header>
        <section class="painel-card">
            <table class="u-full-width"><thead><tr><th>Título</th><th>Valor</th></tr></thead><tbody>
                <?php foreach ($listaDeFilmes as $filme): ?>
                    <tr><td><?= htmlspecialchars($filme['titulo']) ?></td><td>R$ <?= number_format($filme['valor'], 2, ',', '.') ?></td></tr>
                <?php endforeach; ?>
            </tbody></table>
        </section>
        <nav class="navegacao"><a href="painel.php">Voltar ao painel</a></nav>
    </main>
</body>
</html>
