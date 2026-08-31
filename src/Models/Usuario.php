<?php

namespace App\Models;

use App\Database\Connection;
use PDO;

class Usuario
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Connection::getInstance();
    }

    public function buscarPorEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM usuarios WHERE email = :email AND ativo = 1");
        $stmt->execute(['email' => $email]);
        $usuario = $stmt->fetch();

        return $usuario ?: null;
    }

    public function verificarSenha(string $senhaDigitada, string $senhaBanco): bool
    {
        // Verifica se a senha no banco foi criptografada com password_hash
        if (password_verify($senhaDigitada, $senhaBanco)) {
            return true;
        }

        // Fallback: compara direto (para senhas em texto puro durante testes)
        return $senhaDigitada === $senhaBanco;
    }

    public function criar(array $dados): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO usuarios (nome, email, senha, perfil) 
            VALUES (:nome, :email, :senha, :perfil)
        ");

        $stmt->execute([
            'nome'   => $dados['nome'],
            'email'  => $dados['email'],
            'senha'  => password_hash($dados['senha'], PASSWORD_DEFAULT),
            'perfil' => $dados['perfil'] ?? 'funcionario',
        ]);

        return (int) $this->db->lastInsertId();
    }
}
