<?php
require_once __DIR__ . '/../src/Database/Connection.php';
require_once __DIR__ . '/../src/Models/Auth.php';
require_once __DIR__ . '/../src/Services/RelatorioService.php';

$auth = new Auth();
$auth->exigirEquipe();

$inicio = trim($_GET['inicio'] ?? '');
$fim = trim($_GET['fim'] ?? '');
$situacao = $_GET['situacao'] ?? 'todos';
$situacoesValidas = ['todos', 'aberto', 'devolvido', 'atrasado'];
$erro = '';
$emprestimos = [];

// Confere o formato ISO da data e se o dia existe no calendário.
$dataValida = static function (string $data): bool {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
        return false;
    }

    [$ano, $mes, $dia] = array_map('intval', explode('-', $data));

    return checkdate($mes, $dia, $ano);
};

if (($inicio !== '' && !$dataValida($inicio)) || ($fim !== '' && !$dataValida($fim))) {
    $erro = 'Informe datas válidas para filtrar o relatório.';
} elseif ($inicio !== '' && $fim !== '' && $inicio > $fim) {
    $erro = 'A data inicial não pode ser posterior à data final.';
} else {
    if (!in_array($situacao, $situacoesValidas, true)) {
        $situacao = 'todos';
    }

    $relatorio = new \App\Services\RelatorioService($conn);
    $emprestimos = $relatorio->emprestimos(
        $inicio !== '' ? $inicio : null,
        $fim !== '' ? $fim : null,
        $situacao
    );
}

$totalAbertos = 0;
$totalDevolvidos = 0;
$totalAtrasados = 0;
foreach ($emprestimos as $emprestimo) {
    if ((int) $emprestimo['filmes_pendentes'] > 0) {
        $totalAbertos++;
    } else {
        $totalDevolvidos++;
    }
    $totalAtrasados += (int) $emprestimo['atrasado'];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Empréstimos — Locadora LoucaWeb</title>
    <link rel="icon" href="../image/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="../public/css/normalize.css">
    <link rel="stylesheet" href="../public/css/skeleton.css">
    <link rel="stylesheet" href="../public/css/style.css">
    <style>
        .resumo-relatorio { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .resumo-relatorio div { padding: 1.5rem; background: #fff; border: 1px solid #e1e1e1; border-radius: 4px; }
        .resumo-relatorio strong { display: block; font-size: 2.2rem; }
        .tabela-relatorio { overflow-x: auto; }
        .tabela-relatorio table { min-width: 760px; }
        @media (max-width: 700px) { .resumo-relatorio { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media print {
            .topo, .filtros-relatorio, .acoes-impressao { display: none !important; }
            body { background: #fff; }
            .conteudo { padding: 0; }
            .painel-card, .resumo-relatorio div { border-color: #bbb; }
        }
    </style>
</head>
<body>
    <header class="topo">
        <div class="container">
            <div class="marca"><h1>LoucaWeb</h1></div>
            <div class="acoes-topo">
                <a class="button" href="emprestimos/emprestimos.php">Empréstimos</a>
                <a class="button" href="painel.php">Voltar ao painel</a>
            </div>
        </div>
    </header>

    <main class="container conteudo">
        <header class="cabecalho-pagina">
            <h2>Relatório de Empréstimos</h2>
            <p class="descricao">Consulte o histórico por período e situação.</p>
        </header>

        <?php if ($erro !== ''): ?>
            <p class="mensagem erro"><?= htmlspecialchars($erro) ?></p>
        <?php endif; ?>

        <section class="painel-card filtros-relatorio">
            <form method="GET" action="relatorioEmprestimos.php">
                <div class="row">
                    <div class="three columns">
                        <label for="inicio">Data inicial</label>
                        <input class="u-full-width" type="date" id="inicio" name="inicio" value="<?= htmlspecialchars($inicio) ?>">
                    </div>
                    <div class="three columns">
                        <label for="fim">Data final</label>
                        <input class="u-full-width" type="date" id="fim" name="fim" value="<?= htmlspecialchars($fim) ?>">
                    </div>
                    <div class="four columns">
                        <label for="situacao">Situação</label>
                        <select class="u-full-width" id="situacao" name="situacao">
                            <option value="todos" <?= $situacao === 'todos' ? 'selected' : '' ?>>Todas</option>
                            <option value="aberto" <?= $situacao === 'aberto' ? 'selected' : '' ?>>Em aberto</option>
                            <option value="devolvido" <?= $situacao === 'devolvido' ? 'selected' : '' ?>>Devolvidos</option>
                            <option value="atrasado" <?= $situacao === 'atrasado' ? 'selected' : '' ?>>Atrasados</option>
                        </select>
                    </div>
                    <div class="two columns">
                        <label>&nbsp;</label>
                        <button class="button-primary u-full-width" type="submit">Filtrar</button>
                    </div>
                </div>
            </form>
        </section>

        <section class="resumo-relatorio" aria-label="Resumo dos empréstimos filtrados">
            <div><span>Empréstimos</span><strong><?= count($emprestimos) ?></strong></div>
            <div><span>Em aberto</span><strong><?= $totalAbertos ?></strong></div>
            <div><span>Devolvidos</span><strong><?= $totalDevolvidos ?></strong></div>
            <div><span>Atrasados</span><strong><?= $totalAtrasados ?></strong></div>
        </section>

        <section class="painel-card">
            <div class="cabecalho-pagina acoes-impressao">
                <button class="button" type="button" onclick="window.print()">Imprimir relatório</button>
            </div>
            <div class="tabela-relatorio">
                <table class="u-full-width">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Cliente</th>
                            <th>Filmes</th>
                            <th>Empréstimo</th>
                            <th>Prazo</th>
                            <th>Devolução</th>
                            <th>Situação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$emprestimos): ?>
                            <tr><td colspan="7">Nenhum empréstimo encontrado para os filtros informados.</td></tr>
                        <?php else: ?>
                            <?php foreach ($emprestimos as $emprestimo): ?>
                                <?php
                                $pendentes = (int) $emprestimo['filmes_pendentes'];
                                $status = $pendentes === 0 ? 'Devolvido' : ((int) $emprestimo['atrasado'] === 1 ? 'Atrasado' : 'Em aberto');
                                ?>
                                <tr>
                                    <td><?= (int) $emprestimo['id'] ?></td>
                                    <td><?= htmlspecialchars($emprestimo['cliente_nome'] . ' ' . $emprestimo['cliente_sobrenome']) ?></td>
                                    <td><?= htmlspecialchars($emprestimo['filmes'] ?? '') ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($emprestimo['data'])) ?></td>
                                    <td><?= $emprestimo['data_prevista'] ? date('d/m/Y H:i', strtotime($emprestimo['data_prevista'])) : '—' ?></td>
                                    <td><?= $emprestimo['data_devolucao'] ? date('d/m/Y H:i', strtotime($emprestimo['data_devolucao'])) : '—' ?></td>
                                    <td><?= $status ?><?= $pendentes > 0 && $pendentes < (int) $emprestimo['total_filmes'] ? ' (parcial)' : '' ?></td>
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