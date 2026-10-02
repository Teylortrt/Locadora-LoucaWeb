<?php
// Front Controller — GET / redireciona para o login; demais rotas são a API JSON

// Carrega as variáveis de ambiente (.env) usadas por services como o TmdbClient.
require_once __DIR__ . '/../config/env.php';

// Normaliza os caminhos antes de compará-los. No Windows o Apache pode
// informar DOCUMENT_ROOT com barras diferentes das usadas pelo PHP.
$diretorioProjeto = str_replace('\\', '/', dirname(__DIR__));
$documentRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
$raizApp = str_replace($documentRoot, '', $diretorioProjeto);
$raizApp = '/' . trim($raizApp, '/');

$caminho = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (str_starts_with($caminho, $raizApp)) {
    $caminho = substr($caminho, strlen($raizApp));
}
if (str_starts_with($caminho, '/public')) {
    $caminho = substr($caminho, strlen('/public'));
}
if ($caminho === '' || $caminho === false) {
    $caminho = '/';
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET' && $caminho === '/') {
    header('Location: ' . $raizApp . '/templates/login.php');
    header('Location: ' . $raizApp . '/templates/catalogo.php');
    exit;
}

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: OPTIONS,GET,POST,PUT,DELETE");
header("Access-Control-Allow-Headers: Content-Type");

if ($method === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$exigirEquipeApi = static function (): void {
    global $conn;
    require_once __DIR__ . '/../config/conexao.php';
    require_once __DIR__ . '/../src/Models/Auth.php';
    $auth = new Auth();

    if (!$auth->logado()) {
        http_response_code(401);
        echo json_encode(['erro' => 'Autenticação necessária']);
        exit;
    }

    if (!in_array($auth->usuario()['perfil'] ?? '', ['funcionario', 'administrador'], true)) {
        http_response_code(403);
        echo json_encode(['erro' => 'Acesso restrito à equipe']);
        exit;
    }
};

spl_autoload_register(function ($classe) {
    $prefixo = 'App\\';
    $diretorioBase = __DIR__ . '/../src/';
    $tamanhoPrefixo = strlen($prefixo);

    if (strncmp($prefixo, $classe, $tamanhoPrefixo) !== 0) {
        return;
    }

    $classeRelativa = substr($classe, $tamanhoPrefixo);
    $arquivo = $diretorioBase . str_replace('\\', '/', $classeRelativa) . '.php';

    if (file_exists($arquivo)) {
        require $arquivo;
    }
});

if ($method === 'GET' && preg_match('#^/filmes/(\d+)$#', $caminho, $matches)) {
    require_once __DIR__ . '/../config/conexao.php';
    require_once __DIR__ . '/../src/Models/Filmes.php';
    require_once __DIR__ . '/../src/Models/Dvd.php';
    require_once __DIR__ . '/../src/Services/TmdbClient.php';

    $filmeModel = new Filmes($conn);
    $filme = $filmeModel->buscarPorId((int) $matches[1]);

    if (!$filme) {
        http_response_code(404);
        echo json_encode(["erro" => "Filme não encontrado"], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Sinopse ao vivo: falha silenciosa -> null
    $sinopse = null;
    try {
        $sinopse = (new TmdbClient())->buscarSinopse($filme['titulo']);
    } catch (Throwable $e) {
        $sinopse = null;
    }

    $dvdModel = new Dvd($conn);

    $filme['sinopse']        = $sinopse;
    $filme['atores']         = $filmeModel->listarAtores((int) $filme['id']);
    $filme['disponibilidade']= $dvdModel->verificarDisponibilidade((int) $filme['id']);

    echo json_encode($filme, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($method === 'GET' && $caminho === '/clientes') {
    // Busca por nome/sobrenome no autocomplete do painel (?q=).
    if (isset($_GET['q'])) {
        $exigirEquipeApi();
        require_once __DIR__ . '/../config/conexao.php';

        $termo = '%' . $_GET['q'] . '%';
        $stmt = $conn->prepare(
            "SELECT id, nome, sobrenome, telefone FROM clientes
              WHERE CONCAT(nome, ' ', sobrenome) LIKE :t
              ORDER BY nome, sobrenome LIMIT 12"
        );
        $stmt->bindValue(':t', $termo, PDO::PARAM_STR);
        $stmt->execute();

        http_response_code(200);
        echo json_encode(array_map(function (array $c) {
            return [
                'id'    => (int) $c['id'],
                'label' => $c['nome'] . ' ' . $c['sobrenome'],
                'sub'   => $c['telefone'],
            ];
        }, $stmt->fetchAll()), JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if ($method === 'POST' && $caminho === '/cadastro-cliente-web') {
    require_once __DIR__ . '/../src/Models/Auth.php';

    $auth = new Auth();
    if ($auth->logado()) {
        $destino = ($auth->usuario()['perfil'] ?? '') === 'cliente'
            ? '/templates/filmes/filmes.php'
            : '/templates/painel.php';
        header('Location: ' . $raizApp . $destino);
        exit;
    }

    $dados = [
        'nome' => trim($_POST['nome'] ?? ''),
        'sobrenome' => trim($_POST['sobrenome'] ?? ''),
        'telefone' => trim($_POST['telefone'] ?? ''),
        'endereco' => trim($_POST['endereco'] ?? ''),
        'email' => strtolower(trim($_POST['email'] ?? '')),
        'senha' => $_POST['senha'] ?? '',
    ];
    $confirmacao = $_POST['confirmacao_senha'] ?? '';
    $camposObrigatorios = ['nome', 'sobrenome', 'telefone', 'endereco', 'email', 'senha'];
    $camposInvalidos = false;

    foreach ($camposObrigatorios as $campo) {
        if ($dados[$campo] === '') {
            $camposInvalidos = true;
            break;
        }
    }

    if (
        $camposInvalidos
        || !filter_var($dados['email'], FILTER_VALIDATE_EMAIL)
        || strlen($dados['nome']) > 45
        || strlen($dados['sobrenome']) > 45
        || strlen($dados['telefone']) > 20
        || strlen($dados['endereco']) > 100
        || strlen($dados['email']) > 100
        || strlen($dados['senha']) < 8
        || $dados['senha'] !== $confirmacao
    ) {
        header('Location: ' . $raizApp . '/templates/cadastroCliente.php?erro=dados');
        exit;
    }

    try {
        $auth->cadastrarCliente($dados);
    } catch (PDOException $erro) {
        if ($erro->getCode() === '23000') {
            header('Location: ' . $raizApp . '/templates/cadastroCliente.php?erro=email');
            exit;
        }

        error_log($erro->getMessage());
        header('Location: ' . $raizApp . '/templates/cadastroCliente.php?erro=indisponivel');
        exit;
    }

    header('Location: ' . $raizApp . '/templates/filmes/filmes.php');
    exit;
}

if ($method === 'POST' && $caminho === '/login-web') {
    require_once __DIR__ . '/../src/Models/Auth.php';

    $auth  = new Auth();
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if ($email === '' || $senha === '' || !$auth->entrar($email, $senha)) {
        header('Location: ' . $raizApp . '/templates/login.php?erro=1');
        exit;
    }

    $destino = ($auth->usuario()['perfil'] ?? '') === 'cliente'
        ? '/templates/filmes/filmes.php'
        : '/templates/painel.php';
    header('Location: ' . $raizApp . $destino);
    exit;
}

if ($method === 'POST' && $caminho === '/logout-web') {
    require_once __DIR__ . '/../src/Models/Auth.php';

    $auth = new Auth();
    $auth->sair();

    header('Location: ' . $raizApp . '/templates/login.php');
    exit;
}

// --- ROTAS DE EMPRÉSTIMOS ---
if ($method === 'POST' && $caminho === '/emprestimos') {
    $exigirEquipeApi();
    require_once __DIR__ . '/../src/Controllers/EmprestimoController.php';
    (new EmprestimoController())->criarEmprestimo();
    exit;
}

// --- ROTAS DE DEVOLUÇÕES ---
if ($method === 'POST' && $caminho === '/devolucoes') {
    $exigirEquipeApi();
    require_once __DIR__ . '/../src/Controllers/DevolucaoController.php';
    (new DevolucaoController())->registrarDevolucao();
    exit;
}

http_response_code(404);
echo json_encode([
    "erro" => "Rota não encontrada",
    "caminho" => $caminho,
    "metodo" => $method
]);
exit;
