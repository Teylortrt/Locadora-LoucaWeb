<?php

// Carrega as dependências necessárias para autenticação e acesso aos filmes.
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/Filmes.php';

// Garante que somente usuários autenticados acessem o catálogo.
$auth = new Auth($conn);
$auth->exigirLogin();

$filmeModel = new Filmes($conn);

// Obtém a quantidade total para calcular o número de páginas.
$totalRegistros = $filmeModel->contarTotal();

// Usa uma string vazia quando não há pesquisa informada na URL.
$filtrarFilmes = $filmeModel->filtrarFilmes($_GET['pesquisa'] ?? '');

// Define quantos filmes serão exibidos por página.
$registrosPorPagina = 10;

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
    <link rel="stylesheet" href="../../public/css/style.css?v=<?= filemtime(__DIR__ . '/../../public/css/style.css') ?>">
    <style>
        
        .paginacao { margin-top: 20px; }
        .paginacao a { padding: 5px 10px; border: 1px solid #ccc; text-decoration: none; color: #333; margin-right: 5px;}
        .paginacao a.ativo { background-color: #007BFF; color: white; border-color: #007BFF; }
    </style>
</head>
<body>

    <header class="topo">
        <div class="marca">
            <span>Locadora</span>
            <h1>LoucaWeb</h1>
        </div>

        <div class="acoes-topo">
            <form class="acoes-topo" method="GET" action="create.php">
                <button type="submit" class="botao botao-painel">Adicionar Filmes</button>
            </form>

            <form class="acoes-topo" method="GET" action="../painel.php">
                <button type="submit" class="botao botao-painel">Voltar</button>
            </form>
        </div>

    </header>

    <main class="conteudo catalogo-pagina">
        <h1>Catálogo</h1>
        
        <div class="barra-pesquisa">
            
            <form method="GET" action="">
                <input type="text" name="pesquisa" placeholder="Pesquisar por título..." value="<?= htmlspecialchars($_GET['pesquisa'] ?? '') ?>">
                <button type="submit">Pesquisar</button>
            </form>

            <?php if (!empty($filtrarFilmes)): ?>
                <div class="resultado-pesquisa">
                    <h2>Resultados da pesquisa:</h2>
                    <?php foreach ($filtrarFilmes as $filme): ?>
                        <p><?= htmlspecialchars($filme['titulo']) ?> - R$ <?= htmlspecialchars($filme['valor']) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Listagem de Dados em Grid -->
        <div class="catalogo">
            <?php foreach ($filmes as $filme): ?>
                
                <div class="cartao-filme">
                    <!-- Área reservada para a imagem do filme. -->
                    <div class="poster-falso">
                        Sem Imagem
                    </div>
                    
                    <div class="detalhes-filme">
                        <!-- Escapa os valores antes de inseri-los no HTML. -->
                        <h3><?= htmlspecialchars($filme['titulo']) ?></h3>
                        <p>R$ <?= htmlspecialchars($filme['valor']) ?></p>
                    </div>
                </div>

            <?php endforeach; ?>
        </div>

        <!-- Navegação entre as páginas do catálogo. -->
        <div class="paginacao">

            <!-- Botão para ir para a primeira página -->
            <?php if ($paginaAtual > 1): ?>
                <a href="?pagina=1">Primeira</a>
            <?php endif; ?>
            
            <!-- Botão Anterior -->
            <?php if ($paginaAtual > 1): ?>
                <a href="?pagina=<?= $paginaAtual - 1 ?>">Anterior</a>
            <?php endif; ?>

            <!-- Números das Páginas limitado para não ter excesso de links -->
             <?php
                $inicio = max(1, $paginaAtual - 6);
                $fim = min($totalPaginas, $paginaAtual + 6);

                for ($i = $inicio; $i <= $fim; $i++): ?>
                    <a href="?pagina=<?= $i ?>" class="<?= ($i == $paginaAtual) ? 'ativo' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
           

            <!-- Botão Próximo -->
            <?php if ($paginaAtual < $totalPaginas): ?>
                <a href="?pagina=<?= $paginaAtual + 1 ?>">Próximo</a>
            <?php endif; ?>

            <!-- Botão para ir para a última página -->
            <?php if ($paginaAtual < $totalPaginas): ?>
                <a href="?pagina=<?= $totalPaginas ?>">Última</a>
            <?php endif; ?>

            <p>Total de Páginas: <?= $totalPaginas ?></p>

        </div>
    </main>            
</body>
</html>