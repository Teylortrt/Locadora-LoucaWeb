<?php
require_once __DIR__ . '/../../src/Database/Connection.php';
require_once __DIR__ . '/../../src/Models/Auth.php';
require_once __DIR__ . '/../../src/Services/RelatorioService.php';

$auth = new Auth();
$auth->exigirEquipe();

$relatorio = new \App\Services\RelatorioService($conn);
$clientes = $relatorio->clientesComEmprestimosAtrasados();
$totalEmprestimosAtrasados = array_sum(array_map(
    static fn (array $cliente): int => (int) $cliente['total_emprestimos_atrasados'],
    $clientes
));
$agora = new DateTimeImmutable();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes com empréstimos atrasados — Locadora LoucaWeb</title>
    <link rel="icon" href="../image/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="../../public/css/normalize.css">
    <link rel="stylesheet" href="../../public/css/skeleton.css">
    <link rel="stylesheet" href="../../public/css/style.css">
    <style>
        .resumo-atrasos { display: flex; flex-wrap: wrap; gap: 3rem; margin: 0 0 2rem; }
        .resumo-atrasos strong { display: block; font-size: 2.2rem; }
        .tabela-atrasos { overflow-x: auto; }
        .tabela-atrasos table { min-width: 760px; }
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
                <a class="button" href="emprestimos/emprestimos.php">Empréstimos</a>
                <a class="button" href="relatorioEmprestimos.php">Relatório</a>
                <a class="button" href="../painel.php">Voltar ao painel</a>
            </div>
        </div>
    </header>

    <main class="container conteudo">
        <header class="cabecalho-pagina">
            <h2>Clientes com empréstimos atrasados</h2>
            <p class="descricao">Clientes com filmes ainda pendentes após o prazo de devolução.</p>
        </header>

        <section class="painel-card">
            <div class="resumo-atrasos" aria-label="Resumo dos atrasos">
                <div><span>Clientes</span><strong><?= count($clientes) ?></strong></div>
                <div><span>Empréstimos atrasados</span><strong><?= $totalEmprestimosAtrasados ?></strong></div>
            </div>

            <div class="cabecalho-pagina acoes-impressao">
                <button class="button" type="button" onclick="window.print()">Imprimir lista</button>
            </div>

            <div class="tabela-atrasos">
                <table class="u-full-width">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Telefone</th>
                            <th>Empréstimos atrasados</th>
                            <th>Filmes pendentes</th>
                            <th>Prazo mais antigo</th>
                            <th>Dias de atraso</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$clientes): ?>
                            <tr><td colspan="6">Nenhum cliente possui empréstimos atrasados.</td></tr>
                        <?php else: ?>
                            <?php foreach ($clientes as $cliente): ?>
                                <?php
                                $prazo = new DateTimeImmutable($cliente['prazo_mais_antigo']);
                                $diasAtraso = $prazo->diff($agora)->days;
                                $telefoneLink = preg_replace('/\D+/', '', $cliente['telefone']);
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($cliente['nome'] . ' ' . $cliente['sobrenome']) ?></td>
                                    <td><a href="tel:<?= htmlspecialchars($telefoneLink) ?>"><?= htmlspecialchars($cliente['telefone']) ?></a></td>
                                    <td><?= (int) $cliente['total_emprestimos_atrasados'] ?></td>
                                    <td><?= htmlspecialchars($cliente['filmes_pendentes'] ?? '') ?></td>
                                    <td><?= $prazo->format('d/m/Y H:i') ?></td>
                                    <td><?= (int) $diasAtraso ?></td>
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