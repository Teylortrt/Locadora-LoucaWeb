<?php
require_once __DIR__ . '/../src/Database/Connection.php';
require_once __DIR__ . '/../src/Models/Auth.php';
require_once __DIR__ . '/../src/Services/RelatorioService.php';

$auth = new Auth();
$auth->exigirPerfis(['administrador']);

$relatorio = new \App\Services\RelatorioService($conn);
$filmes = $relatorio->filmesMaisAlugados();
$totalAlugueis = array_sum(array_map(
    static fn (array $filme): int => (int) $filme['total_alugueis'],
    $filmes
));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Filmes mais alugados — Locadora LoucaWeb</title>
    <link rel="icon" href="../image/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="../public/css/normalize.css">
    <link rel="stylesheet" href="../public/css/skeleton.css">
    <link rel="stylesheet" href="../public/css/style.css">
    <style>
        .resumo-alugueis { display: flex; flex-wrap: wrap; gap: 3rem; margin: 0 0 2rem; }
        .resumo-alugueis strong { display: block; font-size: 2.2rem; }
        .tabela-ranking { overflow-x: auto; }
        .tabela-ranking table { min-width: 480px; }
        @media print {
            .topo, .acoes-impressao { display: none !important; }
            body { background: #fff; }
            .conteudo { padding: 0; }
            .painel-card { border-color: #bbb; }
        }
    </style>
</head>
<body>
    <header class="topo">
        <div class="container">
            <div class="marca"><h1>LoucaWeb</h1></div>
            <div class="acoes-topo">
                <a class="button" href="filmes/filmes.php">Catálogo</a>
                <a class="button" href="painel.php">Voltar ao painel</a>
            </div>
        </div>
    </header>

    <main class="container conteudo">
        <header class="cabecalho-pagina">
            <h2>Filmes mais alugados</h2>
            <p class="descricao">Os dez títulos com maior número de aluguéis registrados.</p>
        </header>

        <section class="painel-card">
            <div class="resumo-alugueis" aria-label="Resumo dos títulos mais alugados">
                <div><span>Títulos no ranking</span><strong><?= count($filmes) ?></strong></div>
                <div><span>Aluguéis nesses títulos</span><strong><?= $totalAlugueis ?></strong></div>
            </div>

            <div class="cabecalho-pagina acoes-impressao">
                <button class="button" type="button" onclick="window.print()">Imprimir ranking</button>
            </div>

            <div class="tabela-ranking">
                <table class="u-full-width">
                    <thead>
                        <tr>
                            <th>Posição</th>
                            <th>Filme</th>
                            <th>Aluguéis</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$filmes): ?>
                            <tr><td colspan="3">Ainda não há aluguéis registrados para os filmes do catálogo.</td></tr>
                        <?php else: ?>
                            <?php foreach ($filmes as $posicao => $filme): ?>
                                <tr>
                                    <td><?= $posicao + 1 ?></td>
                                    <td><?= htmlspecialchars($filme['titulo']) ?></td>
                                    <td><?= (int) $filme['total_alugueis'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>