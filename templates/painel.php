<?php
require_once __DIR__ . '/../src/Database/Connection.php';
require_once __DIR__ . '/../src/Models/Auth.php';
require_once __DIR__ . '/../src/Models/Cliente.php';

$auth = new Auth();
$auth->exigirLogin();
$usuario = $auth->usuario();

// Caminho base do app (usado no data-raiz do body p/ o autocomplete.js).
$diretorioProjeto = str_replace('\\', '/', dirname(__DIR__));
$documentRoot     = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
$raizApp          = '/' . trim(str_replace($documentRoot, '', $diretorioProjeto), '/');
$urlPainel        = $raizApp . '/templates/painel.php';

global $conn;

// Empréstimo rápido: o cliente é escolhido por autocomplete; os DVDs são
// adicionados um a um (select + botão "Adicionar") e ficam na sessão até a
// confirmação. Confirmação POSTa para /public/emprestimos (EmprestimoController),
// que valida `id_cliente`, `dvds_ids[]` e `prazo_dias` e redireciona de volta
// com ?sucesso= ou ?erro=.

// Estado em andamento.
if (!isset($_SESSION['emprestimo_dvds']) || !is_array($_SESSION['emprestimo_dvds'])) {
    $_SESSION['emprestimo_dvds'] = [];
}
$clienteId = isset($_SESSION['emprestimo_cliente']) ? (int) $_SESSION['emprestimo_cliente'] : 0;

// Ações self-POST (Adicionar/Remover DVD) — nenhum JS envolvido.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['adicionar_dvd'])) {
        $dvdId = (int) ($_POST['dvd_id'] ?? 0);
        if ($dvdId > 0) {
            $stmt = $conn->prepare(
                'SELECT d.id, f.titulo FROM dvds d JOIN filmes f ON f.id = d.id_filme WHERE d.id = :id'
            );
            $stmt->execute(['id' => $dvdId]);
            $dvd = $stmt->fetch();
            if ($dvd) {
                $_SESSION['emprestimo_dvds'][$dvd['id']] = $dvd['titulo'];
            }
        }
    }

    if (isset($_POST['remover_dvd'])) {
        unset($_SESSION['emprestimo_dvds'][(int) $_POST['remover_dvd']]);
    }

    if (isset($_POST['id_cliente'])) {
        $clienteId = (int) $_POST['id_cliente'];
        $_SESSION['emprestimo_cliente'] = $clienteId;
    }

    header('Location: ' . $urlPainel);
    exit;
}

// Empréstimo confirmado com sucesso: limpa o estado em andamento.
if (isset($_GET['sucesso'])) {
    $_SESSION['emprestimo_dvds']   = [];
    $_SESSION['emprestimo_cliente'] = 0;
    $clienteId = 0;
}

// Nome do cliente escolhido (para repopular o campo do autocomplete ao
// recarregar a página após Adicionar/Remover um DVD).
$clienteLabel = '';
if ($clienteId > 0) {
    $stmt = $conn->prepare('SELECT nome, sobrenome FROM clientes WHERE id = :id');
    $stmt->execute(['id' => $clienteId]);
    $cliente = $stmt->fetch();
    if ($cliente) {
        $clienteLabel = $cliente['nome'] . ' ' . $cliente['sobrenome'];
    } else {
        $clienteId = 0;
        unset($_SESSION['emprestimo_cliente']);
    }
}

// Lista de DVDs disponíveis para o select.
$stmt = $conn->prepare(
    'SELECT d.id, f.titulo FROM dvds d JOIN filmes f ON f.id = d.id_filme
      WHERE d.quantidade > 0 ORDER BY f.titulo'
);
$stmt->execute();
$dvdOpcoes = $stmt->fetchAll();

$dvdsSelecionados = $_SESSION['emprestimo_dvds'];
$sucessoEmprestimo = $_GET['sucesso'] ?? '';
$erroEmprestimo    = $_GET['erro'] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel — Locadora LoucaWeb</title>
    <link rel="icon" href="../image/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="../public/css/normalize.css">
    <link rel="stylesheet" href="../public/css/skeleton.css">
    <link rel="stylesheet" href="../public/css/style.css?v=<?= filemtime(__DIR__ . '/../public/css/style.css') ?>">
</head>
<body data-raiz="<?= htmlspecialchars($raizApp) ?>">
    <header class="topo">
      <div class="container">
        <div class="marca">
            <h1>LoucaWeb</h1>
        </div>
        <form class="form-cadastro" method="GET" action="cadastro.php">
            <button type="submit" class="botao botao-cadastro">Cadastro</button>
        </form>

        <form class="form-catalogo" method="GET" action="filmes/filmes.php">
            <button type="submit" class="botao botao-catalogo">Catálogo</button>
        </form>

        <form class="form-cadastro" method="GET" action="emprestimos/emprestimos.php">
            <button type="submit" class="botao botao-cadastro">Emprestimos</button>
        </form>

        <div class="acoes-topo">
            <form class="form-sair" method="POST" action="../public/logout-web">
                <button type="submit" class="button botao-sair">Sair</button>
            </form>
        </div>
      </div>
    </header>

    <main class="container conteudo">
        <section class="painel-card">
            <p class="perfil"><?= htmlspecialchars($usuario['perfil']) ?></p>
            <h2>Olá, <?= htmlspecialchars($usuario['nome']) ?></h2>
            <p class="descricao">Use o menu para acessar as operações da locadora.</p>
        </section>

        <!-- Empréstimo rápido: cliente por autocomplete, filmes por select + Adicionar -->
        <section class="painel-card">
            <header class="cabecalho-pagina">
                <h2>Realizar um Empréstimo Rápido</h2>
                <p>Escolha o cliente, selecione os filmes e clique em Adicionar.</p>
            </header>

            <?php if ($sucessoEmprestimo): ?>
                <p class="mensagem sucesso"><?= htmlspecialchars($sucessoEmprestimo) ?></p>
            <?php endif; ?>
            <?php if ($erroEmprestimo): ?>
                <p class="mensagem erro"><?= htmlspecialchars($erroEmprestimo) ?></p>
            <?php endif; ?>

            <form method="POST" action="<?= $raizApp ?>/public/emprestimos" id="formEmprestimo">
                <div class="row">
                    <div class="six columns">
                        <label for="busca_cliente">Cliente</label>
                        <div class="autocomplete">
                            <input type="hidden" id="id_cliente" name="id_cliente" value="<?= (int) $clienteId ?>">
                            <input type="text" class="autocomplete-input u-full-width" data-ac
                                   data-target="id_cliente" data-url="/clientes"
                                   data-min="2" id="busca_cliente"
                                   value="<?= htmlspecialchars($clienteLabel) ?>"
                                   placeholder="Digite o nome do cliente..." autocomplete="off">
                            <div class="autocomplete-lista"></div>
                        </div>
                    </div>
                    <div class="six columns">
                        <label for="prazo_dias">Prazo de Locação</label>
                        <select class="u-full-width" id="prazo_dias" name="prazo_dias" required>
                            <option value="1">1 dia (Rápido)</option>
                            <option value="2" selected>2 dias (Normal)</option>
                            <option value="3">Final de semana (3 dias)</option>
                        </select>
                    </div>
                </div>

                <div class="row linha-filme">
                    <div class="nine columns">
                        <label for="dvd_id">Filme</label>
                        <select class="u-full-width" id="dvd_id" name="dvd_id">
                            <option value="">Selecione um filme...</option>
                            <?php foreach ($dvdOpcoes as $dvd): ?>
                                <option value="<?= (int) $dvd['id'] ?>"><?= htmlspecialchars($dvd['titulo']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="three columns botao-adicionar-col">
                        <button type="submit" class="button botao-adicionar" name="adicionar_dvd" value="1"
                                formaction="<?= $urlPainel ?>">Adicionar</button>
                    </div>
                </div>

                <div class="row">
                    <div class="twelve columns">
                        <label>DVDs selecionados:</label>
                        <ul id="listaDvds" class="lista-selecionados">
                            <?php if (!$dvdsSelecionados): ?>
                                <li class="vazio">Nenhum filme selecionado.</li>
                            <?php else: ?>
                                <?php foreach ($dvdsSelecionados as $dvdId => $titulo): ?>
                                    <li>
                                        <span><?= htmlspecialchars($titulo) ?></span>
                                        <input type="hidden" name="dvds_ids[]" value="<?= (int) $dvdId ?>">
                                        <button type="submit" class="button botao-excluir remover-dvd"
                                                name="remover_dvd" value="<?= (int) $dvdId ?>"
                                                formaction="<?= $urlPainel ?>">Remover</button>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>

                <button type="submit" class="button-primary">Confirmar Empréstimo</button>
            </form>
        </section>
    </main>

    <script src="emprestimos/autocomplete.js"></script>
</body>
</html>