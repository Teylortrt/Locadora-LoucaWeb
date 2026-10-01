<?php

class EmprestimoModel
{
    private PDO $db;

    // Guarda a conexão usada para criar e consultar empréstimos.
    public function __construct(PDO $conexao)
    {
        $this->db = $conexao;
    }

    /**
    * Confere se cada DVD existe e tem quantidade cadastrada acima de zero,
    * cria o empréstimo e associa seus DVDs numa transação.
    * A checagem não desconta outros empréstimos ainda não devolvidos.
     * Retorna o identificador do empréstimo e a soma dos valores dos filmes.
     */
    public function criarEmprestimo(int $idCliente, array $dvdsIds, int $prazoDias = 1)
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

            // Registrar empréstimo com data_prevista
            $stmt = $this->db->prepare('INSERT INTO emprestimos (id_cliente, data, data_prevista) VALUES (:id_cliente, NOW(), DATE_ADD(NOW(), INTERVAL :prazo DAY))');
            $stmt->bindValue(':id_cliente', $idCliente, PDO::PARAM_INT);
            $stmt->bindValue(':prazo', $prazoDias, PDO::PARAM_INT);
            $stmt->execute();
            $idEmprestimo = $this->db->lastInsertId();

            foreach ($dvdsIds as $idDvd) {
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
     * Lista empréstimos que ainda possuem pelo menos um item não devolvido.
     */
    public function visualizarEmprestimos()
    {
        $sql = '
            SELECT 
                e.id, 
                e.data, 
                c.nome AS cliente_nome, 
                c.sobrenome AS cliente_sobrenome,
                GROUP_CONCAT(f.titulo SEPARATOR ", ") AS filmes,
                GROUP_CONCAT(d.id SEPARATOR ",") AS dvds_ids
            FROM emprestimos e
            JOIN clientes c ON e.id_cliente = c.id
            JOIN filmes_emprestimo fe ON e.id = fe.id_emprestimo
            JOIN dvds d ON fe.id_dvd = d.id
            JOIN filmes f ON d.id_filme = f.id
            LEFT JOIN filmes_devolucao fd ON fd.id_filme_emprestimo = fe.id
            WHERE fd.id IS NULL
            GROUP BY e.id
            ORDER BY e.data DESC
        ';
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lista somente os itens ainda pendentes dos empréstimos do cliente informado.
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
            LEFT JOIN filmes_devolucao fd ON fd.id_filme_emprestimo = fe.id
            WHERE e.id_cliente = :id_cliente AND fd.id IS NULL
            GROUP BY e.id
            ORDER BY e.data DESC
        ';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id_cliente' => $idCliente]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lista itens pendentes cujo prazo venceu e calcula os dias de atraso.
     * Usa a data prevista do empréstimo ou, se ausente, sete dias após a locação.
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
                CEIL(TIMESTAMPDIFF(SECOND, COALESCE(e.data_prevista, DATE_ADD(e.data, INTERVAL 7 DAY)), NOW()) / 86400) AS dias_atraso,
                c.nome AS cliente_nome, 
                c.sobrenome AS cliente_sobrenome,
                GROUP_CONCAT(f.titulo SEPARATOR ", ") AS filmes_pendentes,
                GROUP_CONCAT(d.id SEPARATOR ",") AS dvds_ids
            FROM emprestimos e
            JOIN clientes c ON e.id_cliente = c.id
            JOIN filmes_emprestimo fe ON e.id = fe.id_emprestimo
            JOIN dvds d ON fe.id_dvd = d.id
            JOIN filmes f ON d.id_filme = f.id
            LEFT JOIN filmes_devolucao fd ON fd.id_filme_emprestimo = fe.id
            WHERE COALESCE(e.data_prevista, DATE_ADD(e.data, INTERVAL 7 DAY)) < NOW()
              AND fd.id IS NULL
            GROUP BY e.id
            ORDER BY dias_atraso DESC
        ';
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}
