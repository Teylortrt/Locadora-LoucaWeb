<?php
require_once __DIR__ . '/../../config/conexao.php';
require_once __DIR__ . '/../../src/Models/Auth.php';

$auth = new Auth();
$auth->exigirLogin();

// Lê o ID da URL
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header('Location: tabelaclientes.php');
    exit;
}

// Busca os dados do cliente
$stmt = $conn->prepare('SELECT id, nome, sobrenome, telefone FROM clientes WHERE id = :id');
$stmt->execute([':id' => $id]);
$cliente = $stmt->fetch();
// se nao achar o mane
if (!$cliente) {
    http_response_code(404);
    exit('Cliente não encontrado.');
}

// Monta o histórico com uma linha por filme/cópia alugada.

$stmt = $conn->prepare('
    SELECT e.id AS emprestimo_id, e.data AS data_emprestimo,
           DATE_ADD(e.data, INTERVAL 7 DAY) AS data_prevista,
           f.titulo, d.id AS dvd_id, dev.data AS data_devolucao
      FROM emprestimos e
      JOIN filmes_emprestimo fe ON fe.id_emprestimo = e.id
      JOIN dvds d ON d.id = fe.id_dvd
      JOIN filmes f ON f.id = d.id_filme
      LEFT JOIN filmes_devolucao fd ON fd.id_filme_emprestimo = fe.id
      LEFT JOIN devolucoes dev ON dev.id = fd.id_devolucao
     WHERE e.id_cliente = :id
     ORDER BY e.data DESC, e.id DESC, f.titulo ASC
');
$stmt->execute([':id' => $id]);
$historico = $stmt->fetchAll();

// Calcula os números exibidos no resumo acima da tabela.
$totalItens = count($historico);
$itensAbertos = count(array_filter($historico, static fn($item) => $item['data_devolucao'] === null));
// Vários filmes podem pertencer ao mesmo empréstimo; conta cada ID só uma vez.
$totalEmprestimos = count(array_unique(array_column($historico, 'emprestimo_id')));

// Funções pequenas para exibir datas em formato brasileiro e escapar texto HTML.
$fmtData = static fn($data) => $data ? date('d/m/Y H:i', strtotime($data)) : '—';
$esc = static fn($valor) => htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
?>
<!-- O front da pagina -->
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico de <?= $esc($cliente['nome']) ?> — LoucaWeb</title>
    <link rel="icon" href="../../image/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="../../public/css/normalize.css">
    <link rel="stylesheet" href="../../public/css/skeleton.css">
    <link rel="stylesheet" href="../../public/css/style.css?v=<?= filemtime(__DIR__ . '/../../public/css/style.css') ?>">
</head>
<body>
    <header class="topo"><div class="container"><div class="marca"><h1>LoucaWeb</h1></div><a class="button" href="tabelaclientes.php">Voltar aos clientes</a></div></header>
    <main class="container conteudo pagina-cliente">
        <header class="cabecalho-pagina"><span class="cliente-identificador">Histórico do cliente #<?= (int) $cliente['id'] ?></span><h2><?= $esc($cliente['nome'] . ' ' . $cliente['sobrenome']) ?></h2><p>Locações e devoluções registradas para este cliente.</p></header>
        <section class="painel-card historico-cliente" aria-labelledby="titulo-historico">
            <div class="historico-cabecalho">
                <div><span class="sobretitulo">ATIVIDADE</span><h3 id="titulo-historico">Histórico de locações</h3><p>Filmes alugados, datas e situação de cada cópia.</p></div>
                <div class="historico-resumo"><span><strong><?= $totalEmprestimos ?></strong> locações</span><span><strong><?= $itensAbertos ?></strong> em aberto</span></div>
            </div>
            <!-- Estado vazio: aparece quando ainda não há empréstimos para este cliente. -->
            <?php if (!$historico): ?>
                <div class="historico-vazio"><strong>Nenhuma locação ainda</strong><p>registros aparecerão aqui.</p></div>
            <?php else: ?>
                <!-- Cada linha da tabela representa um filme alugado. -->
                <div class="tabela-responsiva"><table class="tabela-historico">
                    <thead><tr><th>Filme</th><th>Locação</th><th>Devolução prevista</th><th>Devolvido em</th><th>Situação</th></tr></thead>
                    <tbody><?php foreach ($historico as $item):
                        // A presença da data de devolução determina o status do item.
                        $devolvido = $item['data_devolucao'] !== null;
                    ?>
                        <tr>
                            <td><strong><?= $esc($item['titulo']) ?></strong><small>Empréstimo #<?= (int) $item['emprestimo_id'] ?> · Cópia #<?= (int) $item['dvd_id'] ?></small></td>
                            <td><?= $esc($fmtData($item['data_emprestimo'])) ?></td><td><?= $esc($fmtData($item['data_prevista'])) ?></td>
                            <td><?= $esc($fmtData($item['data_devolucao'])) ?></td>
                            <td><span class="status-emprestimo <?= $devolvido ? 'devolvido' : 'aberto' ?>"><?= $devolvido ? 'Devolvido' : 'Em aberto' ?></span></td>
                        </tr>
                    <?php endforeach; ?></tbody>
                </table></div>
                <p class="nota-historico">Exibindo <?= $totalItens ?> <?= $totalItens === 1 ? 'filme' : 'filmes' ?> em <?= $totalEmprestimos ?> <?= $totalEmprestimos === 1 ? 'locação' : 'locações' ?>.</p>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
