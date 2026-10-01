<?php

namespace App\Services;

use App\Database\Connection;
use PDO;

class RelatorioService
{
    private PDO $db;

<<<<<<< HEAD
    // Usa a conexão informada ou obtém a instância compartilhada do banco.
=======
>>>>>>> b0a8314cc2d9710fe875e1523ce48ae794c99259
    public function __construct(?PDO $conexao = null)
    {
        $this->db = $conexao ?? Connection::getInstance();
    }

<<<<<<< HEAD
    // Conta quantos clientes existem na tabela de cadastro.
=======
>>>>>>> b0a8314cc2d9710fe875e1523ce48ae794c99259
    public function totalClientesCadastrados(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) FROM clientes");

        return (int) $stmt->fetchColumn();
    }

<<<<<<< HEAD
    // Lista clientes que possuem empréstimo sem registro de devolução.
=======
    // Clientes com empréstimos ainda sem devolução registrada
>>>>>>> b0a8314cc2d9710fe875e1523ce48ae794c99259
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

<<<<<<< HEAD
    // Agrupa por cliente os empréstimos vencidos que ainda têm filmes pendentes.
=======
>>>>>>> b0a8314cc2d9710fe875e1523ce48ae794c99259
    public function clientesComEmprestimosAtrasados(): array
    {
        $temDataPrevista = $this->db->query("SHOW COLUMNS FROM emprestimos LIKE 'data_prevista'")->fetch() !== false;
        $expressaoPrazo = $temDataPrevista
            ? 'COALESCE(e.data_prevista, DATE_ADD(e.data, INTERVAL 7 DAY))'
            : 'DATE_ADD(e.data, INTERVAL 7 DAY)';

        $sql = "SELECT c.id, c.nome, c.sobrenome, c.telefone,
                       COUNT(DISTINCT e.id) AS total_emprestimos_atrasados,
                       GROUP_CONCAT(DISTINCT f.titulo ORDER BY f.titulo SEPARATOR ', ') AS filmes_pendentes,
                       MIN({$expressaoPrazo}) AS prazo_mais_antigo
                  FROM clientes c
                  JOIN emprestimos e ON e.id_cliente = c.id
                  JOIN filmes_emprestimo fe ON fe.id_emprestimo = e.id
                  JOIN dvds d ON d.id = fe.id_dvd
                  JOIN filmes f ON f.id = d.id_filme
                  LEFT JOIN filmes_devolucao fd ON fd.id_filme_emprestimo = fe.id
                 WHERE fd.id IS NULL
                   AND {$expressaoPrazo} < NOW()
                 GROUP BY c.id, c.nome, c.sobrenome, c.telefone
                 ORDER BY prazo_mais_antigo ASC, c.nome, c.sobrenome";

        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

<<<<<<< HEAD
    // Retorna os clientes com mais empréstimos, limitados à quantidade pedida.
=======
    // Clientes ordenados pela quantidade de empréstimos realizados
>>>>>>> b0a8314cc2d9710fe875e1523ce48ae794c99259
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

<<<<<<< HEAD
    // Lista os filmes mais alugados, do maior para o menor número de locações.
=======
>>>>>>> b0a8314cc2d9710fe875e1523ce48ae794c99259
    public function filmesMaisAlugados(int $limite = 10): array
    {
        $sql = "SELECT f.id, f.titulo, COUNT(fe.id) AS total_alugueis
                  FROM filmes f
                  JOIN dvds d ON d.id_filme = f.id
                  JOIN filmes_emprestimo fe ON fe.id_dvd = d.id
                 GROUP BY f.id, f.titulo
                 ORDER BY total_alugueis DESC, f.titulo ASC
                 LIMIT :limite";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

<<<<<<< HEAD
    // Conta os empréstimos cujas datas estão entre o início e o fim informados.
=======
    // Total de empréstimos realizados dentro de um intervalo de datas
>>>>>>> b0a8314cc2d9710fe875e1523ce48ae794c99259
    public function totalEmprestimosPorPeriodo(string $dataInicio, string $dataFim): int
    {
        $sql = "SELECT COUNT(*) FROM emprestimos WHERE data BETWEEN :inicio AND :fim";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':inicio', $dataInicio);
        $stmt->bindParam(':fim', $dataFim);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

<<<<<<< HEAD
    /**
     * Lista empréstimos com dados de cliente, filmes, prazo e itens pendentes.
     * Aplica filtros opcionais de período e situação (aberto, devolvido ou atrasado).
     */
=======
>>>>>>> b0a8314cc2d9710fe875e1523ce48ae794c99259
    public function emprestimos(?string $dataInicio = null, ?string $dataFim = null, string $situacao = 'todos'): array
    {
        $filtros = [];
        $parametros = [];
        $temDataPrevista = $this->db->query("SHOW COLUMNS FROM emprestimos LIKE 'data_prevista'")->fetch() !== false;
        $expressaoPrazo = $temDataPrevista
            ? 'COALESCE(e.data_prevista, DATE_ADD(e.data, INTERVAL 7 DAY))'
            : 'DATE_ADD(e.data, INTERVAL 7 DAY)';

        if ($dataInicio !== null) {
            $filtros[] = 'e.data >= :data_inicio';
            $parametros[':data_inicio'] = $dataInicio . ' 00:00:00';
        }

        if ($dataFim !== null) {
            $filtros[] = 'e.data <= :data_fim';
            $parametros[':data_fim'] = $dataFim . ' 23:59:59';
        }

        $sql = "SELECT e.id, e.data, {$expressaoPrazo} AS data_prevista,
                       c.nome AS cliente_nome, c.sobrenome AS cliente_sobrenome,
                       GROUP_CONCAT(DISTINCT f.titulo ORDER BY f.titulo SEPARATOR ', ') AS filmes,
                       COUNT(DISTINCT fe.id) AS total_filmes,
                       COUNT(DISTINCT CASE WHEN fd.id IS NULL THEN fe.id END) AS filmes_pendentes,
                       MAX(dev.data) AS data_devolucao,
                       CASE
                           WHEN COUNT(DISTINCT CASE WHEN fd.id IS NULL THEN fe.id END) > 0
                            AND {$expressaoPrazo} < NOW()
                           THEN 1 ELSE 0
                       END AS atrasado
                  FROM emprestimos e
                  JOIN clientes c ON c.id = e.id_cliente
                  JOIN filmes_emprestimo fe ON fe.id_emprestimo = e.id
                  JOIN dvds d ON d.id = fe.id_dvd
                  JOIN filmes f ON f.id = d.id_filme
                  LEFT JOIN filmes_devolucao fd ON fd.id_filme_emprestimo = fe.id
                  LEFT JOIN devolucoes dev ON dev.id = fd.id_devolucao";

        if ($filtros) {
            $sql .= ' WHERE ' . implode(' AND ', $filtros);
        }

        $sql .= ' GROUP BY e.id, e.data, c.nome, c.sobrenome';
        if ($temDataPrevista) {
            $sql .= ', e.data_prevista';
        }

        if ($situacao === 'aberto') {
            $sql .= ' HAVING filmes_pendentes > 0';
        } elseif ($situacao === 'devolvido') {
            $sql .= ' HAVING filmes_pendentes = 0';
        } elseif ($situacao === 'atrasado') {
            $sql .= ' HAVING atrasado = 1';
        }

        $sql .= ' ORDER BY e.data DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($parametros);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

<<<<<<< HEAD
    // Soma o valor cadastrado dos filmes associados a todos os empréstimos.
=======
>>>>>>> b0a8314cc2d9710fe875e1523ce48ae794c99259
    public function totalFaturamento(): float
    {
        $sql = "SELECT COALESCE(SUM(f.valor), 0)
                  FROM filmes_emprestimo fe
                  JOIN dvds d ON d.id = fe.id_dvd
                  JOIN filmes f ON f.id = d.id_filme";

        return (float) $this->db->query($sql)->fetchColumn();
    }
}
