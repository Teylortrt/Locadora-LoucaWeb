<?php

class EmprestimoModel
{
    private PDO $db;

    public function __construct(PDO $conexao)
    {
        $this->db = $conexao;
    }

    /**
     * US22 - Realizar empréstimo
     */
    public function criarEmprestimo(int $idCliente, array $dvdsIds)
    {
        try {
            $this->db->beginTransaction();

            // Verificar disponibilidade
            $valorTotal = 0;
            foreach ($dvdsIds as $idDvd) {
                $stmt = $this->db->prepare('SELECT d.quantidade, f.valor FROM dvds d JOIN filmes f ON d.id_filme = f.id WHERE d.id = :id_dvd FOR UPDATE');
                $stmt->execute([':id_dvd' => $idDvd]);
                $dvd = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$dvd || $dvd['quantidade'] <= 0) {
                    throw new Exception("O DVD ID {$idDvd} não está disponível ou não existe.");
                }

                $valorTotal += (float)$dvd['valor'];
            }

            // Registrar empréstimo
            $stmt = $this->db->prepare('INSERT INTO emprestimos (id_cliente, data) VALUES (:id_cliente, NOW())');
            $stmt->execute([':id_cliente' => $idCliente]);
            $idEmprestimo = $this->db->lastInsertId();

            foreach ($dvdsIds as $idDvd) {
                // Diminuir disponibilidade
                $stmt = $this->db->prepare('UPDATE dvds SET quantidade = quantidade - 1 WHERE id = :id_dvd');
                $stmt->execute([':id_dvd' => $idDvd]);

                // Registrar filmes do empréstimo
                $stmt = $this->db->prepare('INSERT INTO filmes_emprestimo (id_dvd, id_emprestimo) VALUES (:id_dvd, :id_emprestimo)');
                $stmt->execute([
                    ':id_dvd' => $idDvd,
                    ':id_emprestimo' => $idEmprestimo
                ]);
            }

            $this->db->commit();
            return ['id_emprestimo' => $idEmprestimo, 'valor_total' => $valorTotal];
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * US23 - Consultar empréstimos
     */
    public function visualizarEmprestimos()
    {
        $sql = '
            SELECT 
                e.id, 
                e.data, 
                c.nome AS cliente_nome, 
                c.sobrenome AS cliente_sobrenome,
                GROUP_CONCAT(f.titulo SEPARATOR ", ") AS filmes
            FROM emprestimos e
            JOIN clientes c ON e.id_cliente = c.id
            JOIN filmes_emprestimo fe ON e.id = fe.id_emprestimo
            JOIN dvds d ON fe.id_dvd = d.id
            JOIN filmes f ON d.id_filme = f.id
            GROUP BY e.id
            ORDER BY e.data DESC
        ';
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * US24 - Consultar empréstimos de um cliente
     */
    public function consultarEmprestimosCliente(int $idCliente)
    {
        $sql = '
            SELECT 
                e.id, 
                e.data, 
                GROUP_CONCAT(f.titulo SEPARATOR ", ") AS filmes
            FROM emprestimos e
            JOIN filmes_emprestimo fe ON e.id = fe.id_emprestimo
            JOIN dvds d ON fe.id_dvd = d.id
            JOIN filmes f ON d.id_filme = f.id
            WHERE e.id_cliente = :id_cliente
            GROUP BY e.id
            ORDER BY e.data DESC
        ';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id_cliente' => $idCliente]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * US25 - Identificar empréstimos atrasados
     * Assumindo um prazo de 7 dias para devolução
     */
    public function calcularAtraso()
    {
        // Consideramos atrasado se a data do empréstimo for mais antiga que 7 dias
        // E o empréstimo não foi totalmente devolvido.
        // A lógica de devolução total pode ser complexa. Para simplificar,
        // checamos empréstimos cujos filmes não têm devolução associada correspondente
        $sql = '
            SELECT 
                e.id, 
                e.data,
                DATEDIFF(NOW(), DATE_ADD(e.data, INTERVAL 7 DAY)) AS dias_atraso,
                c.nome AS cliente_nome, 
                c.sobrenome AS cliente_sobrenome,
                GROUP_CONCAT(f.titulo SEPARATOR ", ") AS filmes_pendentes
            FROM emprestimos e
            JOIN clientes c ON e.id_cliente = c.id
            JOIN filmes_emprestimo fe ON e.id = fe.id_emprestimo
            JOIN dvds d ON fe.id_dvd = d.id
            JOIN filmes f ON d.id_filme = f.id
            LEFT JOIN filmes_devolucao fd ON fd.id_filme_emprestimo = fe.id
            WHERE DATE_ADD(e.data, INTERVAL 7 DAY) < NOW()
              AND fd.id IS NULL
            GROUP BY e.id
            ORDER BY dias_atraso DESC
        ';
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}
