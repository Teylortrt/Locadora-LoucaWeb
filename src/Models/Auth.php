<?php

require_once __DIR__ . '/../../config/conexao.php';

class Auth
{
    private PDO $db;

    public function __construct()
    {
        global $conn;

        if (isset($conn) && $conn instanceof PDO) {
            $this->db = $conn;
        } else {
            $this->db = new PDO(
                'mysql:host=localhost;port=3306;dbname=locadora;charset=utf8mb4',
                'root',
                '',
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    public function entrar(string $email, string $senha): bool
    {
        $stmt = $this->db->prepare('SELECT * FROM usuarios WHERE email = :email AND ativo = 1');
        $stmt->execute(['email' => $email]);
        $usuario = $stmt->fetch();

        if (!$usuario) {
            return false;
        }

        $valida = password_verify($senha, $usuario['senha']) || $senha === $usuario['senha'];

        if (!$valida) {
            return false;
        }

        unset($usuario['senha']);
        session_regenerate_id(true);
        $_SESSION['usuario'] = $usuario;

        return true;
    }

    public function cadastrarCliente(array $dados): void
    {
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare(
                'INSERT INTO clientes (nome, sobrenome, telefone, endereco)
                 VALUES (:nome, :sobrenome, :telefone, :endereco)'
            );
            $stmt->execute([
                'nome' => $dados['nome'],
                'sobrenome' => $dados['sobrenome'],
                'telefone' => $dados['telefone'],
                'endereco' => $dados['endereco'],
            ]);
            $idCliente = (int) $this->db->lastInsertId();

            $stmt = $this->db->prepare(
                "INSERT INTO usuarios (nome, email, senha, perfil, id_cliente)
                 VALUES (:nome, :email, :senha, 'cliente', :id_cliente)"
            );
            $stmt->execute([
                'nome' => $dados['nome'] . ' ' . $dados['sobrenome'],
                'email' => $dados['email'],
                'senha' => password_hash($dados['senha'], PASSWORD_DEFAULT),
                'id_cliente' => $idCliente,
            ]);
            $idUsuario = (int) $this->db->lastInsertId();

            $this->db->commit();
            session_regenerate_id(true);
            $_SESSION['usuario'] = [
                'id' => $idUsuario,
                'id_cliente' => $idCliente,
                'nome' => $dados['nome'] . ' ' . $dados['sobrenome'],
                'email' => $dados['email'],
                'perfil' => 'cliente',
                'ativo' => 1,
            ];
        } catch (\Throwable $erro) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $erro;
        }
    }

    public function sair(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }

        session_destroy();
    }

    public function logado(): bool
    {
        return isset($_SESSION['usuario']);
    }

    public function usuario(): ?array
    {
        return $_SESSION['usuario'] ?? null;
    }

    public function exigirLogin(): void
    {
        if (!$this->logado()) {
            $diretorioProjeto = str_replace('\\', '/', dirname(__DIR__, 2));
            $documentRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
            $raizApp = '/' . trim(str_replace($documentRoot, '', $diretorioProjeto), '/');

            header('Location: ' . $raizApp . '/templates/login.php');
            exit;
        }
    }

    public function exigirPerfis(array $perfis): void
    {
        $this->exigirLogin();

        if (!in_array($this->usuario()['perfil'] ?? '', $perfis, true)) {
            http_response_code(403);
            exit('Acesso não autorizado.');
        }
    }

    public function exigirEquipe(): void
    {
        $this->exigirPerfis(['funcionario', 'administrador']);
    }
}
