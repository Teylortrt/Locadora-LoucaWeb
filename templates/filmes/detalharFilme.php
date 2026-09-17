<?php
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/conexao.php';
require_once __DIR__ . '/../../src/Models/Auth.php';
require_once __DIR__ . '/../../src/Models/Filmes.php';
require_once __DIR__ . '/../../src/Models/Dvd.php';
require_once __DIR__ . '/../../src/Services/TmdbClient.php';

$auth = new Auth();
$auth->exigirLogin();

$id = (int) ($_GET['id'] ?? 0);

$filmeModel = new Filmes($conn);
$filme = $id > 0 ? $filmeModel->buscarPorId($id) : null;

if ($filme):
    $sinopse = null;
    try {
        $sinopse = (new TmdbClient())->buscarSinopse($filme['titulo']);
    } catch (Throwable $e) {
        $sinopse = null;
    }
    $atores          = $filmeModel->listarAtores($id);
    $disponibilidade = (new Dvd($conn))->verificarDisponibilidade($id);
endif;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Detalhes do Filme</title>
    <link rel="icon" href="../../image/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="../../public/css/normalize.css">
    <link rel="stylesheet" href="../../public/css/skeleton.css">
    <link rel="stylesheet" href="../../public/css/style.css?v=<?= filemtime(__DIR__ . '/../../public/css/style.css') ?>">
</head>
<body>
    <header class="topo">
        <div class="container">
            <div class="marca"><span>Locadora</span><h1>LoucaWeb</h1></div>
            <form class="form-cadastro" method="GET" action="filmes.php">
                <button type="submit" class="button">Voltar</button>
            </form>
        </div>
    </header>

    <main class="container conteudo">
        <?php if (!$filme): ?>
            <div class="card">
                <h2>Filme não encontrado</h2>
                <p><a href="filmes.php">Voltar ao catálogo</a></p>
            </div>
        <?php else:
            $valor = 'R$ ' . number_format((float) $filme['valor'], 2, ',', '.'); ?>
            <div class="ficha-filme">
                <?php if (!empty($filme['poster_url'])): ?>
                    <img class="ficha-poster" src="<?= htmlspecialchars($filme['poster_url']) ?>"
                         alt="Poster de <?= htmlspecialchars($filme['titulo']) ?>" loading="lazy">
                <?php else: ?>
                    <div class="poster-falso">Sem Imagem</div>
                <?php endif; ?>

                <div class="ficha-infos">
                    <h1><?= htmlspecialchars($filme['titulo']) ?></h1>
                    <p class="modal-genero">Gênero: <?= htmlspecialchars($filme['genero']) ?></p>
                    <p class="modal-valor"><?= $valor ?></p>

                    <?php if ($disponibilidade > 0): ?>
                        <span class="badge disponivel">Disponível &middot; <?= (int) $disponibilidade ?> cópia(s)</span>
                    <?php else: ?>
                        <span class="badge indisponivel">Indisponível</span>
                    <?php endif; ?>

                    <hr>
                    <h2>Sinopse</h2>
                    <?php if ($sinopse): ?>
                        <p><?= htmlspecialchars($sinopse) ?></p>
                    <?php else: ?>
                        <p><em>Sinopse indisponível.</em></p>
                    <?php endif; ?>

                    <h2>Elenco</h2>
                    <?php if (!empty($atores)): ?>
                        <ul class="elenco">
                            <?php foreach ($atores as $a): ?>
                                <li><?= htmlspecialchars($a['nome']) ?>
                                    <?php if (!empty($a['personagem'])): ?>
                                        — <em><?= htmlspecialchars($a['personagem']) ?></em>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p><em>Nenhum ator cadastrado.</em></p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>