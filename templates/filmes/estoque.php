<?php
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/conexao.php';
require_once __DIR__ . '/../../src/Models/Auth.php';
require_once __DIR__ . '/../../src/Models/Dvd.php';

$auth = new Auth();
$auth->exigirLogin();

$dvdModel = new Dvd($conn);

// --- Ação POST: atualizar quantidade ---
$mensagem     = '';
$mensagemTipo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_quantidade'])) {
    $idDvd          = (int) ($_POST['id_dvd'] ?? 0);
    $novaQuantidade = (int) ($_POST['quantidade'] ?? 0);

    try {
        $dvdModel->atualizarQuantidade($idDvd, $novaQuantidade);
        $mensagem     = "Estoque do DVD #{$idDvd} atualizado para {$novaQuantidade} cópia(s).";
        $mensagemTipo = 'sucesso';
    } catch (\Throwable $e) {
        $mensagem     = $e->getMessage();
        $mensagemTipo = 'erro';
    }
}

// --- Paginação e busca ---
$busca       = trim($_GET['busca'] ?? '');
$paginaAtual = max(1, (int) ($_GET['pagina'] ?? 1));
$porPagina   = 25;
$offset      = ($paginaAtual - 1) * $porPagina;

$totalDvds   = $dvdModel->contarEstoque($busca);
$totalPaginas = max(1, (int) ceil($totalDvds / $porPagina));
$estoque     = $dvdModel->listarEstoque($porPagina, $offset, $busca);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Estoque — Locadora LoucaWeb</title>
    <link rel="icon" href="../../image/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="../../public/css/normalize.css">
    <link rel="stylesheet" href="../../public/css/skeleton.css">
    <link rel="stylesheet" href="../../public/css/style.css?v=<?= filemtime(__DIR__ . '/../../public/css/style.css') ?>">
</head>
<body>
    <header class="topo">
        <div class="container">
            <div class="marca">
                <span>Locadora</span>
                <h1>LoucaWeb</h1>
            </div>
            <form class="form-cadastro" method="GET" action="filmes.php">
                <button type="submit" class="button">Catálogo</button>
            </form>
            <form class="form-sair" method="GET" action="../painel.php">
                <button type="submit" class="button">Painel</button>
            </form>
        </div>
    </header>

    <main class="container conteudo">
        <header class="cabecalho-pagina">
            <h2>Gestão de Estoque de DVDs</h2>
            <p>Controle a quantidade de cópias disponíveis para cada filme.</p>
        </header>

        <?php if ($mensagem): ?>
            <p class="mensagem <?= $mensagemTipo ?>"><?= htmlspecialchars($mensagem) ?></p>
        <?php endif; ?>

        <!-- Barra de busca -->
        <section class="barra-pesquisa">
            <form method="GET" action="">
                <input type="text" name="busca" placeholder="Buscar por título..."
                       value="<?= htmlspecialchars($busca) ?>">
                <button type="submit" class="button-primary">Buscar</button>
                <?php if ($busca): ?>
                    <a href="estoque.php" class="button">Limpar</a>
                <?php endif; ?>
            </form>
        </section>

        <!-- Tabela de estoque -->
        <section class="painel-card">
            <table class="u-full-width">
                <thead>
                    <tr>
                        <th>Título</th>
                        <th>Gênero</th>
                        <th style="text-align: center;">Estoque</th>
                        <th style="text-align: center;">Emprestadas</th>
                        <th style="text-align: center;">Disponível</th>
                        <th class="acao-coluna">Ação</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($estoque)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; color: #888;">
                            Nenhum DVD encontrado<?= $busca ? ' para "' . htmlspecialchars($busca) . '"' : '' ?>.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($estoque as $dvd):
                        $emprestadas = (int) $dvd['quantidade'] - (int) $dvd['disponivel'];
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($dvd['titulo']) ?></td>
                            <td><?= htmlspecialchars($dvd['genero']) ?></td>
                            <td style="text-align: center;">
                                <form method="POST" action="estoque.php<?= $busca ? '?busca=' . urlencode($busca) . '&pagina=' . $paginaAtual : '?pagina=' . $paginaAtual ?>"
                                      style="display: inline-flex; align-items: center; gap: .5rem; margin: 0;">
                                    <input type="hidden" name="id_dvd" value="<?= (int) $dvd['id_dvd'] ?>">
                                    <input type="number" name="quantidade" value="<?= (int) $dvd['quantidade'] ?>"
                                           min="<?= $emprestadas ?>" step="1"
                                           style="width: 70px; margin: 0; text-align: center;">
                            </td>
                            <td style="text-align: center;"><?= $emprestadas ?></td>
                            <td style="text-align: center;">
                                <?php if ((int) $dvd['disponivel'] > 0): ?>
                                    <span style="color: #1e7e34; font-weight: 600;"><?= (int) $dvd['disponivel'] ?></span>
                                <?php else: ?>
                                    <span style="color: #a94442; font-weight: 600;">0</span>
                                <?php endif; ?>
                            </td>
                            <td class="acao-coluna">
                                    <button type="submit" name="salvar_quantidade" value="1" class="button" style="margin: 0;">Salvar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </section>

        <!-- Paginação -->
        <?php if ($totalPaginas > 1): ?>
            <nav class="paginacao">
                <?php
                $queryBase = $busca ? 'busca=' . urlencode($busca) . '&' : '';
                for ($p = 1; $p <= $totalPaginas; $p++): ?>
                    <a href="estoque.php?<?= $queryBase ?>pagina=<?= $p ?>"
                       class="<?= $p === $paginaAtual ? 'ativo' : '' ?>"><?= $p ?></a>
                <?php endfor; ?>
            </nav>
        <?php endif; ?>

        <p style="margin-top: 1rem; color: #888;">
            Exibindo <?= count($estoque) ?> de <?= $totalDvds ?> DVD(s)
            <?= $busca ? ' para "' . htmlspecialchars($busca) . '"' : '' ?>.
        </p>
    </main>
</body>
</html>
