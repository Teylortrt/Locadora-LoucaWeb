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
        try {
            $this->db->beginTransaction();

            // Confere se o cliente existe e impede que outra operação altere o registro durante a exclusão.
            $stmt = $this->db->prepare('SELECT id FROM clientes WHERE id = :id FOR UPDATE');
            $stmt->execute([':id' => $id]);
            if (!$stmt->fetchColumn()) {
                $this->db->rollBack();
                return false;
            }

            // Um item sem vínculo em filmes_devolucao ainda está emprestado.
            $stmt = $this->db->prepare(
                'SELECT COUNT(*)
                   FROM emprestimos e
                   JOIN filmes_emprestimo fe ON fe.id_emprestimo = e.id
                   LEFT JOIN filmes_devolucao fd ON fd.id_filme_emprestimo = fe.id
                  WHERE e.id_cliente = :id_cliente AND fd.id IS NULL'
            );
            $stmt->execute([':id_cliente' => $id]);
            if ((int) $stmt->fetchColumn() > 0) {
                throw new \RuntimeException('Não é possível excluir o cliente porque existem itens emprestados sem devolução.');
            }

            // Remove o histórico na ordem exigida pelas chaves estrangeiras.
            $stmt = $this->db->prepare(
                'DELETE fd FROM filmes_devolucao fd
                   JOIN filmes_emprestimo fe ON fe.id = fd.id_filme_emprestimo
                   JOIN emprestimos e ON e.id = fe.id_emprestimo
                  WHERE e.id_cliente = :id_cliente'
            );
            $stmt->execute([':id_cliente' => $id]);

            $stmt = $this->db->prepare(
                'DELETE d FROM devolucoes d
                   JOIN emprestimos e ON e.id = d.id_emprestimo
                  WHERE e.id_cliente = :id_cliente'
            );
            $stmt->execute([':id_cliente' => $id]);

            $stmt = $this->db->prepare(
                'DELETE fe FROM filmes_emprestimo fe
                   JOIN emprestimos e ON e.id = fe.id_emprestimo
                  WHERE e.id_cliente = :id_cliente'
            );
            $stmt->execute([':id_cliente' => $id]);

            $stmt = $this->db->prepare('DELETE FROM emprestimos WHERE id_cliente = :id_cliente');
            $stmt->execute([':id_cliente' => $id]);

            $stmt = $this->db->prepare('DELETE FROM clientes WHERE id = :id');
            $stmt->execute([':id' => $id]);

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }
}
