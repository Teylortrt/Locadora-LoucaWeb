<?php
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../src/Models/Auth.php';
require_once __DIR__ . '/../src/Models/Cliente.php';
require_once __DIR__ . '/../src/Models/Dvd.php';
require_once __DIR__ . '/../src/Models/PainelEmprestimo.php';

$auth = new Auth();
$auth->exigirLogin();
$usuario = $auth->usuario();

// Caminho base do app (usado no data-raiz do body p/ o autocomplete.js).
$diretorioProjeto = str_replace('\\', '/', dirname(__DIR__));
$documentRoot     = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
$raizApp          = '/' . trim(str_replace($documentRoot, '', $diretorioProjeto), '/');
$urlPainel        = $raizApp . '/templates/painel.php';

// Toda a lógica do empréstimo rápido (filmes disponíveis, DVDs escolhidos,
// cliente e limpeza após a confirmação) fica no model; aqui só o formulário.
$painel = new PainelEmprestimo(new Dvd($conn), new \App\Models\Cliente($conn));

// Ações self-POST (Adicionar/Remover DVD) — nenhum JS envolvido.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['adicionar_dvd'])) {
        $painel->adicionarDvd((int) ($_POST['dvd_id'] ?? 0));
    }

    if (isset($_POST['remover_dvd'])) {
        $painel->removerDvd((int) $_POST['remover_dvd']);
    }

    if (isset($_POST['id_cliente'])) {
        $painel->definirCliente((int) $_POST['id_cliente']);
    }

    header('Location: ' . $urlPainel);
    exit;
}

// Empréstimo confirmado com sucesso: limpa o rascunho.
if (isset($_GET['sucesso'])) {
    $painel->limpar();
}

$dvdOpcoes        = $painel->opcoesDeFilmes();
$dvdsSelecionados = $painel->dvds();
$clienteId        = $painel->clienteId();
$clienteLabel     = $painel->clienteLabel();

$sucessoEmprestimo = $_GET['sucesso'] ?? '';
$erroEmprestimo    = $_GET['erro'] ?? '';

// Texto de disponibilidade, usado no select e na lista de selecionados.
$rotuloCopias = static fn (int $copias): string => $copias > 0
    ? $copias . ($copias === 1 ? ' cópia disponível' : ' cópias disponíveis')
    : 'sem cópias disponíveis';
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
        <form class="form-cliente" method="GET" action="/Locadora-LoucaWeb/templates/clientes/clientes.php">
            <button type="submit" class="button botao-cliente">Cliente</button>
        </form>

        <form class="form-catalogo" method="GET" action="filmes/filmes.php">
            <button type="submit" class="botao botao-catalogo">Catálogo</button>
        </form>

        <form class="form-cadastro" method="GET" action="filmes/estoque.php">
            <button type="submit" class="button">Estoque</button>
        </form>

        <form class="form-cadastro" method="GET" action="emprestimos/emprestimos.php">
            <button type="submit" class="botao botao-cadastro">Emprestimos</button>
        </form>

        <div class="acoes-topo">
            <form class="form-sair" method="POST" action="../public/logout-web">
                <button type="submit" class="button botao-sair">Sair</button>
            </form>
        </nav>
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
                <h2>Realizar um Empréstimo</h2>
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

                <div class="row">
                    <div class="nine columns autocomplete">
                        <label for="busca_dvd">Filme</label>
                        <input class="u-full-width" type="text" id="busca_dvd" placeholder="Digite para buscar um filme..." autocomplete="off">
                        <input type="hidden" id="dvd_id" name="dvd_id" value="">
                        <div id="autocomplete-lista-filme" class="autocomplete-lista" style="max-height: 250px; overflow-y: auto;"></div>
                    </div>
                    <div class="three columns" style="margin-top: 2.9rem;">
                        <button type="submit" class="button u-full-width" name="adicionar_dvd" value="1"
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
                                <?php foreach ($dvdsSelecionados as $dvdId => $dvd): ?>
                                    <li>
                                        <span>
                                            <?= htmlspecialchars($dvd['titulo']) ?>
                                            <em class="copias-disponiveis"><?= $rotuloCopias((int) $dvd['disponivel']) ?></em>
                                        </span>
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

    <?php
    $filmesPainel = [];
    foreach ($dvdOpcoes as $dvd) {
        $filmesPainel[] = [
            'id' => (int) $dvd['id'],
            'titulo' => $dvd['titulo'],
            'disponivel' => (int) $dvd['disponivel'],
            'rotulo' => $rotuloCopias((int) $dvd['disponivel'])
        ];
    }
    ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const filmesPainel = <?= json_encode($filmesPainel) ?>;
            const inputDvdBusca = document.getElementById('busca_dvd');
            const inputDvdHidden = document.getElementById('dvd_id');
            const listaDvdDiv = document.getElementById('autocomplete-lista-filme');
            const btnAdicionarDvd = document.querySelector('button[name="adicionar_dvd"]');

            inputDvdBusca.addEventListener('input', function() {
                const val = this.value.toLowerCase();
                listaDvdDiv.innerHTML = '';
                inputDvdHidden.value = '';

                if (!val) {
                    listaDvdDiv.style.display = 'none';
                    return;
                }

                const filtrados = filmesPainel.filter(f => f.titulo.toLowerCase().includes(val)).slice(0, 50);

                if (filtrados.length > 0) {
                    listaDvdDiv.style.display = 'block';
                    filtrados.forEach(f => {
                        const item = document.createElement('div');
                        item.className = 'autocomplete-item';
                        
                        if (f.disponivel <= 0) {
                            item.style.color = '#999';
                            item.style.cursor = 'not-allowed';
                            item.innerHTML = f.titulo + ' &mdash; <em>' + f.rotulo + '</em>';
                        } else {
                            item.innerHTML = '<strong>' + f.titulo + '</strong> &mdash; <em style="color: #1e7e34;">' + f.rotulo + '</em>';
                            item.addEventListener('click', function() {
                                inputDvdBusca.value = f.titulo;
                                inputDvdHidden.value = f.id;
                                listaDvdDiv.style.display = 'none';
                                btnAdicionarDvd.focus();
                            });
                        }
                        
                        listaDvdDiv.appendChild(item);
                    });
                } else {
                    listaDvdDiv.style.display = 'none';
                }
            });

            // Fecha a lista ao clicar fora
            document.addEventListener('click', function(e) {
                if (e.target !== inputDvdBusca && e.target !== listaDvdDiv) {
                    listaDvdDiv.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>
