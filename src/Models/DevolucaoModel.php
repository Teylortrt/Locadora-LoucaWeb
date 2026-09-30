<?php

class DevolucaoModel
{
    private PDO $db;

    public function __construct(PDO $conexao)
    {
        $this->db = $conexao;
    }

    /**
     * US28 & US27 - Calcular valor e atraso antes da devolução
     * Dado o id_emprestimo e uma lista de ids de dvds sendo devolvidos,
     * retorna o valor original do aluguel, os dias de atraso e o valor da multa (ex: R$ 2 por dia).
     */
    public function calcularValorDevolucao(int $idEmprestimo, array $dvdIds): array
    {
        if (empty($dvdIds)) {
            throw new Exception("Nenhum DVD selecionado para devolução.");
        }

        $placeholders = str_repeat('?,', count($dvdIds) - 1) . '?';
        
        $sql = "
            SELECT 
                fe.id AS id_filme_emprestimo,
                d.id AS id_dvd,
                f.valor AS valor_filme,
                e.data AS data_emprestimo,
                CEIL(TIMESTAMPDIFF(SECOND, COALESCE(e.data_prevista, DATE_ADD(e.data, INTERVAL 7 DAY)), NOW()) / 86400) AS dias_atraso
            FROM filmes_emprestimo fe
            JOIN emprestimos e ON fe.id_emprestimo = e.id
            JOIN dvds d ON fe.id_dvd = d.id
            JOIN filmes f ON d.id_filme = f.id
            LEFT JOIN filmes_devolucao fd ON fd.id_filme_emprestimo = fe.id
            WHERE fe.id_emprestimo = ? AND d.id IN ($placeholders)
              AND fd.id IS NULL -- Apenas os não devolvidos
        ";

        $params = array_merge([$idEmprestimo], $dvdIds);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $itens = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($itens) !== count($dvdIds)) {
            throw new Exception("Algum dos DVDs informados não pertence a este empréstimo ou já foi devolvido.");
        }

        $valorOriginal = 0;
        $diasAtrasoMax = 0;

        $multaPorDia = 2.00; // Regra de negócio: R$ 2,00 por dia de atraso (geral, e não por filme)

        foreach ($itens as $item) {
            $valorOriginal += (float) $item['valor_filme'];
            $atraso = (int) $item['dias_atraso'];
            if ($atraso > $diasAtrasoMax) {
                $diasAtrasoMax = $atraso;
            }
        }

        $multaTotal = $diasAtrasoMax * $multaPorDia;

        return [
            'itens_validos' => $itens,
            'valor_original_filmes' => $valorOriginal,
            'dias_atraso' => $diasAtrasoMax,
            'valor_multa' => $multaTotal,
            'valor_total_pagar' => $multaTotal // O original já foi pago na retirada
        ];
    }

    /**
     * US26 - Registrar devolução
     */
    public function registrarDevolucao(int $idEmprestimo, array $dvdIds)
    {
        try {
            $this->db->beginTransaction();

            // 1. Calcula os valores e garante que os filmes são válidos para devolução
            $calculo = $this->calcularValorDevolucao($idEmprestimo, $dvdIds);
            $itens = $calculo['itens_validos'];

            // 2. Registrar na tabela devolucoes
            $stmt = $this->db->prepare('INSERT INTO devolucoes (id_emprestimo, data) VALUES (?, NOW())');
            $stmt->execute([$idEmprestimo]);
            $idDevolucao = $this->db->lastInsertId();

            foreach ($itens as $item) {
                // 3. Registrar na tabela filmes_devolucao
                $stmt = $this->db->prepare('INSERT INTO filmes_devolucao (id_devolucao, id_filme_emprestimo) VALUES (?, ?)');
                $stmt->execute([$idDevolucao, $item['id_filme_emprestimo']]);
            }

            $this->db->commit();

            return [
                'id_devolucao' => $idDevolucao,
                'resumo_valores' => [
                    'dias_atraso' => $calculo['dias_atraso'],
                    'multa_paga' => $calculo['valor_multa']
                ]
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
