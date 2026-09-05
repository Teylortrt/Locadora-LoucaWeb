<?php

require_once __DIR__ . '/../config/conexao.php';

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
        $_SESSION['usuario'] = $usuario;

        return true;
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
            header('Location: login.php');
            exit;
        }
    }
}
