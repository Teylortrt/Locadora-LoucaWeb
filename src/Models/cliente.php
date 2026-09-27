<?php

namespace App\Models;

use App\Database\Connection;
use PDO;

class Cliente
{
    private PDO $db;

    // Aceita uma conexão própria ou usa a instância compartilhada (padrão dos demais models namespaced)
    public function __construct(?PDO $conexao = null)
    {
        $this->db = $conexao ?? Connection::getInstance();
    }

    // Retorna os dados para quem chamou o método
    public function listar(): array
    {
        $sql = "SELECT * FROM clientes ORDER BY id";
        $stmt = $this->db->query($sql);
        
        return $stmt->fetchAll(); // PDO::FETCH_ASSOC já foi definido no arquivo de conexão
    }

    public function buscarPorId(int $id): ?array
    {
        $sql = "SELECT * FROM clientes WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $cliente = $stmt->fetch();

        return $cliente ?: null;
    }

    public function existe(int $id): bool
    {
        $sql = "SELECT 1 FROM clientes WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return (bool) $stmt->fetchColumn();
    }

    public function criar(string $nome, string $sobrenome, string $telefone, string $endereco): int
    {
        $sql = "INSERT INTO clientes (nome, sobrenome, telefone, endereco) VALUES (:nome, :sobrenome, :telefone, :endereco)";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':nome', $nome);
        $stmt->bindParam(':sobrenome', $sobrenome);
        $stmt->bindParam(':telefone', $telefone);
        $stmt->bindParam(':endereco', $endereco);
        $stmt->execute();

        return (int) $this->db->lastInsertId();
    }

    public function atualizar(int $id, string $nome, string $sobrenome, string $telefone, string $endereco): bool
    {
        $sql = "UPDATE clientes SET nome = :nome, sobrenome = :sobrenome, telefone = :telefone, endereco = :endereco WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':nome', $nome);
        $stmt->bindParam(':sobrenome', $sobrenome);
        $stmt->bindParam(':telefone', $telefone);
        $stmt->bindParam(':endereco', $endereco);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function deletar(int $id): bool
    {
        $sql = "DELETE FROM clientes WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }
}