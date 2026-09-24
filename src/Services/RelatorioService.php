<?php

namespace App\Services;

use App\Database\Connection;
use PDO;

class RelatorioService
{
    private PDO $db;

    public function __construct(?PDO $conexao = null)
    {
        $this->db = $conexao ?? Connection::getInstance();
    }

    public function totalClientesCadastrados(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) FROM clientes");

        return (int) $stmt->fetchColumn();
    }

    // Clientes com empréstimos ainda sem devolução registrada
    public function clientesComEmprestimosAtivos(): array
    {
        $sql = "SELECT DISTINCT c.*
                  FROM clientes c
                  JOIN emprestimos e ON e.id_cliente = c.id
                 WHERE e.id NOT IN (SELECT id_emprestimo FROM devolucoes)
                 ORDER BY c.nome";

        $stmt = $this->db->query($sql);

        return $stmt->fetchAll();
    }

    // Clientes ordenados pela quantidade de empréstimos realizados
    public function clientesMaisAtivos(int $limite = 10): array
    {
        $sql = "SELECT c.*, COUNT(e.id) AS total_emprestimos
                  FROM clientes c
                  JOIN emprestimos e ON e.id_cliente = c.id
                 GROUP BY c.id
                 ORDER BY total_emprestimos DESC
                 LIMIT :limite";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    // Total de empréstimos realizados dentro de um intervalo de datas
    public function totalEmprestimosPorPeriodo(string $dataInicio, string $dataFim): int
    {
        $sql = "SELECT COUNT(*) FROM emprestimos WHERE data BETWEEN :inicio AND :fim";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':inicio', $dataInicio);
        $stmt->bindParam(':fim', $dataFim);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    public function totalFaturamento(): float
    {
        $sql = "SELECT COALESCE(SUM(f.valor), 0)
                  FROM filmes_emprestimo fe
                  JOIN dvds d ON d.id = fe.id_dvd
                  JOIN filmes f ON f.id = d.id_filme";

        return (float) $this->db->query($sql)->fetchColumn();
    }
}
