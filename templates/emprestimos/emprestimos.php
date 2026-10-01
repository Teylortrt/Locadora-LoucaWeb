<?php
require_once __DIR__ . '/../../src/Database/Connection.php';
require_once __DIR__ . '/../../src/Models/Auth.php';
require_once __DIR__ . '/../../src/Models/EmprestimoModel.php';
require_once __DIR__ . '/../../src/Models/DevolucaoModel.php';

$auth = new Auth();
$auth->exigirEquipe();

$emprestimoModel = new EmprestimoModel($conn);
$devolucaoModel = new DevolucaoModel($conn);

$apenasAtrasados = isset($_GET['atrasados']) && $_GET['atrasados'] == '1';
$termoBusca = isset($_GET['busca']) ? strtolower(trim($_GET['busca'])) : '';

// 1. Carregar lista base
if ($apenasAtrasados) {
    $todos = $emprestimoModel->calcularAtraso();
} else {
    $todos = $emprestimoModel->visualizarEmprestimos();
}

// 2. Filtrar
$filtrados = [];
foreach ($todos as $emp) {
    $nome = strtolower($emp['cliente_nome'] . ' ' . $emp['cliente_sobrenome']);
    $filmes = strtolower($emp['filmes'] ?? $emp['filmes_pendentes'] ?? '');
    if ($termoBusca === '' || strpos($nome, $termoBusca) !== false || strpos($filmes, $termoBusca) !== false) {
        $filtrados[] = $emp;
    }
}

// 3. Se pediu para calcular devolução
$calcularDevolucao = $_GET['calcular_devolucao'] ?? null;
$dvdsIdsDevolucao = $_GET['dvds_ids'] ?? null;
$dadosDevolucao = null;
$erroDevolucao = null;

if ($calcularDevolucao && $dvdsIdsDevolucao) {
    try {
        $ids = explode(',', $dvdsIdsDevolucao);
        $dadosDevolucao = $devolucaoModel->calcularValorDevolucao((int)$calcularDevolucao, $ids);
    } catch (Exception $e) {
        $erroDevolucao = $e->getMessage();
    }
}

$sucessoMsg = $_GET['sucesso'] ?? '';
$erroMsg = $_GET['erro'] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Empréstimos — Locadora LoucaWeb</title>
    <link rel="icon" href="../../image/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="../../public/css/normalize.css">
    <link rel="stylesheet" href="../../public/css/skeleton.css">
    <link rel="stylesheet" href="../../public/css/style.css">
</head>
<body>
    <header class="topo">
      <div class="container">
        <div class="marca">
            <h1>LoucaWeb</h1>
        </div>

        <form class="form-cadastro" method="GET" action="../relatorio/relatorioEmprestimos.php">
            <button type="submit" class="button">Relatório</button>
        </form>

        <div class="acoes-topo">    

            <form class="form-sair" method="GET" action="../painel.php">
                <button type="submit" class="button">Voltar</button>
            </form>
        </div> 
    </header>

    <main class="container conteudo">
        <header class="cabecalho-pagina">
            <h2>Gerenciar Empréstimos</h2>
            <p>Consulte locações, busque por clientes ou filmes e registre devoluções.</p>
        </header>

        <?php if ($sucessoMsg): ?>
            <div style="color: green; margin-bottom: 15px; font-weight: bold;"><?= htmlspecialchars($sucessoMsg) ?></div>
        <?php endif; ?>
        <?php if ($erroMsg): ?>
            <div style="color: red; margin-bottom: 15px; font-weight: bold;"><?= htmlspecialchars($erroMsg) ?></div>
        <?php endif; ?>

        <?php if ($calcularDevolucao && $dvdsIdsDevolucao): ?>
            <section class="painel-card" style="border: 2px solid #33C3F0;">
                <h3>Confirmar Devolução</h3>
                <?php if ($erroDevolucao): ?>
                    <p style="color:red;"><?= htmlspecialchars($erroDevolucao) ?></p>
                    <a href="emprestimos.php" class="button">Voltar</a>
                <?php else: ?>
                    <p><strong>Detalhes da Locação:</strong><br>Valor Original: R$ <?= number_format($dadosDevolucao['valor_original_filmes'], 2, ',', '.') ?></p>
                    <?php if ($dadosDevolucao['valor_multa'] > 0): ?>
                        <p style="color: red;"><strong>Atraso detectado!</strong><br>Multa: R$ <?= number_format($dadosDevolucao['valor_multa'], 2, ',', '.') ?></p>
                    <?php else: ?>
                        <p style="color: green;">Devolução no prazo. Nenhuma multa aplicada.</p>
                    <?php endif; ?>
                    <p><strong>Total a Pagar: R$ <?= number_format($dadosDevolucao['valor_total_pagar'], 2, ',', '.') ?></strong></p>
                    
                    <form method="POST" action="../../public/devolucoes" style="display:inline-block; margin-right: 10px;">
                        <input type="hidden" name="id_emprestimo" value="<?= htmlspecialchars($calcularDevolucao) ?>">
                        <input type="hidden" name="dvds_ids" value="<?= htmlspecialchars($dvdsIdsDevolucao) ?>">
                        <button type="submit" class="button-primary">Confirmar Devolução</button>
                    </form>
                    <a href="emprestimos.php" class="button">Cancelar</a>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <section class="painel-card">
            <form method="GET" action="emprestimos.php" style="margin-bottom: 20px;">
                <div class="row">
                    <div class="eight columns">
                        <input type="text" class="u-full-width" name="busca" placeholder="Buscar por cliente ou filme..." value="<?= htmlspecialchars($_GET['busca'] ?? '') ?>">
                    </div>
                    <div class="four columns" style="display: flex; align-items: center; height: 38px;">
                        <label style="margin-right: 15px; font-weight: normal;">
                            <input type="checkbox" name="atrasados" value="1" <?= $apenasAtrasados ? 'checked' : '' ?>> Apenas atrasados
                        </label>
                        <button type="submit" class="button-primary" style="margin: 0;">Filtrar</button>
                    </div>
                </div>
            </form>

            <table class="u-full-width">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Filmes</th>
                        <th>Data do Empréstimo</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($filtrados) === 0): ?>
                        <tr><td colspan="4">Nenhum empréstimo encontrado.</td></tr>
                    <?php else: ?>
                        <?php foreach ($filtrados as $emp): 
                            $titulosFilmes = $emp['filmes'] ?? $emp['filmes_pendentes'] ?? '';
                            $isAtrasado = isset($emp['dias_atraso']) && $emp['dias_atraso'] > 0;
                        ?>
                            <tr style="<?= $isAtrasado ? 'background-color: #ffeeee;' : '' ?>">
                                <td><?= htmlspecialchars($emp['cliente_nome'] . ' ' . $emp['cliente_sobrenome']) ?></td>
                                <td><?= htmlspecialchars($titulosFilmes) ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($emp['data'])) ?></td>
                                <td>
                                    <form method="GET" action="emprestimos.php" style="margin:0;">
                                        <input type="hidden" name="calcular_devolucao" value="<?= $emp['id'] ?>">
                                        <input type="hidden" name="dvds_ids" value="<?= $emp['dvds_ids'] ?>">
                                        <?php if ($termoBusca): ?><input type="hidden" name="busca" value="<?= htmlspecialchars($termoBusca) ?>"><?php endif; ?>
                                        <?php if ($apenasAtrasados): ?><input type="hidden" name="atrasados" value="1"><?php endif; ?>
                                        <button type="submit" class="button-primary">Devolver</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>

    </main>
</body>
</html>
