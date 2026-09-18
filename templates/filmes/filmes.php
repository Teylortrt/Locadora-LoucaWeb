<?php

// ===================================================
// PARTE 1: DEPENDÊNCIAS E AUTENTICAÇÃO
// ===================================================
require_once __DIR__ . '/../../src/Models/Auth.php';
require_once __DIR__ . '/../../src/Models/Filmes.php';

$auth = new Auth();
$auth->exigirLogin();

// Calcula a raiz da aplicação para o JavaScript montar a URL da API (GET /filmes/{id}).
$diretorioProjeto = str_replace('\\', '/', dirname(__DIR__, 2));
$documentRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
$raizApp = '/' . trim(str_replace($documentRoot, '', $diretorioProjeto), '/');

$filmeModel = new Filmes($conn);

// ===================================================
// PARTE 2: PAGINAÇÃO (catálogo completo)
// ===================================================
$totalRegistros = $filmeModel->contarTotal();
$registrosPorPagina = 25;

$paginaAtual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($paginaAtual < 1) {
    $paginaAtual = 1;
}

$offset = ($paginaAtual - 1) * $registrosPorPagina;
$totalPaginas = ceil($totalRegistros / $registrosPorPagina);
$filmes = $filmeModel->listarPorPagina($registrosPorPagina, $offset);

// ===================================================
// PARTE 3: FILTRO POR PESQUISA
// ===================================================
$pesquisa = trim($_GET['pesquisa'] ?? '');
$parametroPesquisa = $pesquisa !== ''
    ? '&pesquisa=' . urlencode($pesquisa)
    : '';
$filtrarFilmes = $filmeModel->filtrarFilmes($pesquisa);

// ===================================================
// PARTE 4: FILTRO POR GÊNERO botões
// ===================================================
$genero = trim($_GET['genero'] ?? '');
if ($genero !== '') {
    $totalRegistrosGenero = $filmeModel->contarPorGenero((int)$genero);
    $totalPaginasGenero = ceil($totalRegistrosGenero / $registrosPorPagina);
    $offsetGenero = ($paginaAtual - 1) * $registrosPorPagina;
    $filtrarPorGenero = $filmeModel->filtrarPorGenero((int)$genero, $registrosPorPagina, $offsetGenero);
} else {
    $filtrarPorGenero = [];
    $totalPaginasGenero = 0;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>catalogo</title>
    <link rel="icon" href="../../image/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="../../public/css/normalize.css">
    <link rel="stylesheet" href="../../public/css/skeleton.css">
    <link rel="stylesheet" href="../../public/css/style.css?v=<?= filemtime(__DIR__ . '/../../public/css/style.css') ?>">
    <script src="../../public/js/filmes.js" defer></script>
</head>
<body data-raiz="<?= htmlspecialchars($raizApp) ?>">

    <!-- =================================================== -->
    <!-- PARTE 5: CABEÇALHO  -->
    <!-- =================================================== -->
    <header class="topo">
        <div class="container">
            <div class="marca">
                <span>Locadora</span>
                <h1>LoucaWeb</h1>
            </div>

            <form class="form-cadastro" method="GET" action="cadastrarFilme.php">
                <button type="submit" class="button button-primary">Adicionar Filmes</button>
            </form>

            <form class="form-sair" method="GET" action="../painel.php">
                <button type="submit" class="button">Voltar</button>
            </form>
        </div>
    </header>

    <main class="container conteudo catalogo-pagina">
        <h1>Catálogo</h1>

        <!-- =================================================== -->
        <!-- PARTE 6: BOTÕES DE GÊNERO -->
        <!-- =================================================== -->
        <div class="botoes-genero">
            <form method="GET" action="">
                <button type="submit" name="genero" value="1" class="button button-primary">Ação</button>
                <button type="submit" name="genero" value="2" class="button button-primary">Aventura</button>
                <button type="submit" name="genero" value="3" class="button button-primary">Animação</button>
                <button type="submit" name="genero" value="4" class="button button-primary">Comédia</button>
                <button type="submit" name="genero" value="5" class="button button-primary">crime</button>
                <button type="submit" name="genero" value="6" class="button button-primary">Documentário</button>
                <button type="submit" name="genero" value="7" class="button button-primary">Drama</button>
                <button type="submit" name="genero" value="8" class="button button-primary">Fantasia</button>
                <button type="submit" name="genero" value="" class="button button-primary">Todos</button>
            </form>
        </div>

        <!-- =================================================== -->
        <!-- PARTE 7: BARRA DE PESQUISA -->
        <!-- =================================================== -->
        <div class="barra-pesquisa">
            <form method="GET" action="">
                <input class="u-full-width" type="text" name="pesquisa" placeholder="Pesquisar por título..." value="<?= htmlspecialchars($_GET['pesquisa'] ?? '') ?>">
                <button type="submit" class="button button-primary">Pesquisar</button>
            </form>
        </div>

        <!-- =================================================== -->
        <!-- PARTE 8: EXIBIÇÃO DE RESULTADOS                        -->
        <!-- Prioridade: 1º gênero, 2º pesquisa, 3º catálogo padrão -->
        <!-- =================================================== -->

        <?php if ($genero !== ''): ?>

    <!-- ---------- 8.1: RESULTADO FILTRADO POR GÊNERO ---------- -->
    <div class="resultado-pesquisa">
        <?php if (!empty($filtrarPorGenero)): ?>
            <div class="catalogo">
                <?php foreach ($filtrarPorGenero as $filme): ?>
                    <a href="detalharFilme.php?id=<?= $filme['id'] ?>" class="link-filme" data-filme-id="<?= $filme['id'] ?>">
                    <div class="cartao-filme">
                        <?php if (!empty($filme['poster_url'])): ?>
                            <img class="poster-imagem"
                                src="<?= htmlspecialchars($filme['poster_url']) ?>"
                                alt="Poster de <?= htmlspecialchars($filme['titulo']) ?>"
                                loading="lazy">
                        <?php else: ?>
                            <div class="poster-falso">Sem Imagem</div>
                        <?php endif; ?>

                        <div class="detalhes-filme">
                            <h3><?= htmlspecialchars($filme['titulo']) ?></h3>
                            <p>R$ <?= htmlspecialchars($filme['valor']) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Paginação do gênero -->
            <div class="paginacao">
                <?php if ($paginaAtual > 1): ?>
                    <a href="?genero=<?= urlencode($genero) ?>&pagina=1">Primeira</a>
                    <a href="?genero=<?= urlencode($genero) ?>&pagina=<?= $paginaAtual - 1 ?>">Anterior</a>
                <?php endif; ?>

                <?php
                $inicioG = max(1, $paginaAtual - 6);
                $fimG = min($totalPaginasGenero, $paginaAtual + 6);
                for ($i = $inicioG; $i <= $fimG; $i++): ?>
                    <a href="?genero=<?= urlencode($genero) ?>&pagina=<?= $i ?>" class="<?= ($i == $paginaAtual) ? 'ativo' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>

                <?php if ($paginaAtual < $totalPaginasGenero): ?>
                    <a href="?genero=<?= urlencode($genero) ?>&pagina=<?= $paginaAtual + 1 ?>">Próximo</a>
                    <a href="?genero=<?= urlencode($genero) ?>&pagina=<?= $totalPaginasGenero ?>">Última</a>
                <?php endif; ?>

                <p>Total de Páginas: <?= $totalPaginasGenero ?></p>
            </div>

        <?php else: ?>
            <p>Nenhum filme encontrado nesse gênero.</p>
        <?php endif; ?>
    </div>

<?php elseif ($pesquisa !== ''): ?>
    
            <!-- ---------- 8.2: RESULTADO DA PESQUISA POR TEXTO ---------- -->
            <div class="resultado-pesquisa">
                <h2>Resultados da pesquisa:</h2>

                <?php if (!empty($filtrarFilmes)): ?>
                    <div class="catalogo">
                        <?php foreach ($filtrarFilmes as $filme): ?>
                            <a href="detalharFilme.php?id=<?= $filme['id'] ?>" class="link-filme" data-filme-id="<?= $filme['id'] ?>">
                            <div class="cartao-filme">
                                <?php if (!empty($filme['poster_url'])): ?>
                                    <img class="poster-imagem" src="<?= htmlspecialchars($filme['poster_url']) ?>"
                                        alt="Poster de <?= htmlspecialchars($filme['titulo']) ?>" loading="lazy">
                                <?php else: ?>
                                    <div class="poster-falso">Sem Imagem</div>
                                <?php endif; ?>
                                <div class="detalhes-filme">
                                    <h3><?= htmlspecialchars($filme['titulo']) ?></h3>
                                    <p>R$ <?= htmlspecialchars($filme['valor']) ?></p>
                                </div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p>Nenhum filme encontrado.</p>
                <?php endif; ?>
            </div>

        <?php else: ?>

            <!-- ---------- 8.3: CATÁLOGO COMPLETO (padrão, sem filtro) ---------- -->
            <div class="catalogo">
                <?php foreach ($filmes as $filme): ?>
                    <a href="detalharFilme.php?id=<?= $filme['id'] ?>" class="link-filme" data-filme-id="<?= $filme['id'] ?>">
                    <div class="cartao-filme">
                        <?php if (!empty($filme['poster_url'])): ?>
                            <img class="poster-imagem"
                                src="<?= htmlspecialchars($filme['poster_url']) ?>"
                                alt="Poster de <?= htmlspecialchars($filme['titulo']) ?>"
                                loading="lazy">
                        <?php else: ?>
                            <div class="poster-falso">Sem Imagem</div>
                        <?php endif; ?>

                        <div class="detalhes-filme">
                            <h3><?= htmlspecialchars($filme['titulo']) ?></h3>
                            <p>R$ <?= htmlspecialchars($filme['valor']) ?></p>
                        </div>
                    </div>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- ---------- 8.4: PAGINAÇÃO (só aparece no catálogo padrão) ---------- -->
            <div class="paginacao">
                <?php if ($paginaAtual > 1): ?>
                    <a href="?pagina=1">Primeira</a>
                    <a href="?pagina=<?= $paginaAtual - 1 ?>">Anterior</a>
                <?php endif; ?>

                <?php
                $inicio = max(1, $paginaAtual - 6);
                $fim = min($totalPaginas, $paginaAtual + 6);
                for ($i = $inicio; $i <= $fim; $i++): ?>
                    <a href="?pagina=<?= $i ?>" class="<?= ($i == $paginaAtual) ? 'ativo' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>

                <?php if ($paginaAtual < $totalPaginas): ?>
                    <a href="?pagina=<?= $paginaAtual + 1 ?>">Próximo</a>
                    <a href="?pagina=<?= $totalPaginas ?>">Última</a>
                <?php endif; ?>

                <p>Total de Páginas: <?= $totalPaginas ?></p>
            </div>

        <?php endif; ?>

    </main>
    <div id="modal-filme" class="modal-overlay" hidden>
        <div class="modal-conteudo" role="dialog" aria-modal="true">
            <button type="button" id="modal-fechar" class="modal-fechar" aria-label="Fechar">&times;</button>
            <div id="modal-corpo"><p>Carregando...</p></div>
        </div>
    </div>
</body>
</html>