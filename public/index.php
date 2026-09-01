<?php
// Front Controller — GET / redireciona para o login; demais rotas são a API JSON

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

if ($method === 'GET' && $caminho === '/filmes') {
    $controller = new \App\Controllers\FilmeController();
    $controller->index();
    exit;
}

if ($method === 'POST' && $caminho === '/login') {
    $controller = new \App\Controllers\AuthController();
    $controller->login();
    exit;
}

if ($method === 'POST' && $caminho === '/cadastrar') {
    $controller = new \App\Controllers\AuthController();
    $controller->cadastrar();
    exit;
}

if ($method === 'POST' && $caminho === '/login-web') {
    require_once __DIR__ . '/../src/Auth.php';

    $auth  = new Auth();
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if ($email === '' || $senha === '' || !$auth->entrar($email, $senha)) {
        header('Location: ' . $raizApp . '/templates/login.php?erro=1');
        exit;
    }

    header('Location: ' . $raizApp . '/templates/painel.php');
    exit;
}

if ($method === 'POST' && $caminho === '/logout-web') {
    require_once __DIR__ . '/../src/Auth.php';

    $auth = new Auth();
    $auth->sair();

    header('Location: ' . $raizApp . '/templates/login.php');
    exit;
}

http_response_code(404);
echo json_encode([
    "erro" => "Rota não encontrada",
    "caminho" => $caminho,
    "metodo" => $method
]);
exit;
