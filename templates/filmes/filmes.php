<?php

// Carrega as dependências necessárias para autenticação e acesso aos filmes.
require_once __DIR__ . '/../../src/Models/Auth.php';
require_once __DIR__ . '/../../src/Models/Filmes.php';

// Garante que somente usuários autenticados acessem o catálogo.
$auth = new Auth();
$auth->exigirLogin();

// Calcula a raiz da aplicação para o JavaScript montar a URL da API (GET /filmes/{id}).
$diretorioProjeto = str_replace('\\', '/', dirname(__DIR__, 2));
$documentRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
$raizApp = '/' . trim(str_replace($documentRoot, '', $diretorioProjeto), '/');

$filmeModel = new Filmes($conn);

// Obtém a quantidade total para calcular o número de páginas.
$totalRegistros = $filmeModel->contarTotal();

// Usa uma string vazia quando não há pesquisa informada na URL.
$pesquisa = trim($_GET['pesquisa'] ?? '');
$parametroPesquisa = $pesquisa !== ''
    ? '&pesquisa=' . urlencode($pesquisa)
    : '';
$filtrarFilmes = $filmeModel->filtrarFilmes($pesquisa);

// Define quantos filmes serão exibidos por página.
$registrosPorPagina = 25;

// Converte o parâmetro da URL para inteiro e usa a primeira página como padrão.
$paginaAtual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($paginaAtual < 1) {
    $paginaAtual = 1;
}

// Cálculo do Offset (Deslocamento)
// Ex: Página 1 = (1 - 1) * 5 = 0 (Busca a partir do 0)
// Ex: Página 2 = (2 - 1) * 5 = 5 (Busca a partir do 5)
$offset = ($paginaAtual - 1) * $registrosPorPagina;

// Arredonda para cima para incluir uma página com os registros restantes.
$totalPaginas = ceil($totalRegistros / $registrosPorPagina);

// Busca apenas os filmes necessários para a página atual.
$filmes = $filmeModel->listarPorPagina($registrosPorPagina, $offset);
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
        
        <div class="barra-pesquisa">
            
            <form method="GET" action="">
                <input class="u-full-width" type="text" name="pesquisa" placeholder="Pesquisar por título..." value="<?= htmlspecialchars($_GET['pesquisa'] ?? '') ?>">
                <button type="submit" class="button button-primary">Pesquisar</button>
            </form>

            <?php if ($pesquisa !== ''): ?>
            <!-- Resultados da Pesquisa -->
                <div class="resultado-pesquisa">
                    <h2>Resultados da pesquisa:</h2>
        <!--EXECUTANTANDO A PESQUISA-->
                    <?php if (!empty($filtrarFilmes)): ?>
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
                    <?php else: ?>
                        <p>Nenhum filme encontrado.</p>
                    <?php endif; ?>

                    <?php if ($totalPaginas > 1): ?>
                    <div class="paginacao">
                    <?php if ($paginaAtual > 1): ?>
                        <a href="?pagina=1<?= $parametroPesquisa ?>">Primeira</a>
                        <a href="?pagina=<?= $paginaAtual - 1 ?><?= $parametroPesquisa ?>">Anterior</a>
                    <?php endif; ?>

                    <?php
                    $inicio = max(1, $paginaAtual - 6);
                    $fim = min($totalPaginas, $paginaAtual + 6);

                    for ($i = $inicio; $i <= $fim; $i++): ?>
                        <a href="?pagina=<?= $i ?><?= $parametroPesquisa ?>" class="<?= ($i == $paginaAtual) ? 'ativo' : '' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($paginaAtual < $totalPaginas): ?>
                        <a href="?pagina=<?= $paginaAtual + 1 ?><?= $parametroPesquisa ?>">Próximo</a>
                        <a href="?pagina=<?= $totalPaginas ?><?= $parametroPesquisa ?>">Última</a>
                    <?php endif; ?>

                    <p>Total de Páginas: <?= $totalPaginas ?></p>
                    </div>
                    <?php endif; ?>
                </div>
                
        <!-- Se não houver pesquisa, exibe o catálogo completo -->
            <?php else: ?>
                <!-- Listagem de Dados em Grid -->
                <div class="catalogo">
                    <?php foreach ($filmes as $filme): ?>
                        <a href="detalharFilme.php?id=<?= $filme['id'] ?>" class="link-filme" data-filme-id="<?= $filme['id'] ?>">
                        <div class="cartao-filme">
                            <?php if (!empty($filme['poster_url'])):?>
                                <img class="poster-imagem"
                                    src="<?= htmlspecialchars($filme['poster_url']) ?>"
                                    alt="Poster de <?= htmlspecialchars($filme['titulo']) ?>"
                                    loading="lazy">
                            <?php else: ?>
                                <div class="poster-falso">Sem Imagem</div>
                            <?php endif;?>

                            <div class="detalhes-filme">
                                <h3><?= htmlspecialchars($filme['titulo']) ?></h3>
                                <p>R$ <?= htmlspecialchars($filme['valor']) ?></p>
                            </div>
                        </div>
                        </a>
                    <?php endforeach; ?>
                </div>

                <!-- Navegação entre as páginas do catálogo. -->
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
        </div>
    </main>
    <div id="modal-filme" class="modal-overlay" hidden>
        <div class="modal-conteudo" role="dialog" aria-modal="true">
            <button type="button" id="modal-fechar" class="modal-fechar" aria-label="Fechar">&times;</button>
            <div id="modal-corpo"><p>Carregando...</p></div>
        </div>
    </div>
</body>
</html>
