<?php
// 1. Carrega a conexão (isso também inicia a sessão automaticamente)
// 2. Carrega as classes
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Filmes.php';

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
    <title>Lista de Filmes</title>
</head>
<body>
    <h1>Catálogo de Filmes</h1>
    
    <ul>
        <?php foreach ($listaDeFilmes as $filme): ?>
            <li>
                <?= htmlspecialchars($filme['titulo']) ?> - R$ <?= number_format($filme['valor'], 2, ',', '.') ?>
            </li>
        <?php endforeach; ?>
    </ul>
</body>
</html>