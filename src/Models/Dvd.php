<?php

class Dvd
{
    // Cópias ainda livres de um DVD: estoque físico menos as cópias que estão em
    // empréstimo sem devolução registrada. O alias `d` é o da tabela `dvds`.
    private const COPIAS_DISPONIVEIS = 'd.quantidade - (SELECT COUNT(*)
                                                    FROM filmes_emprestimo fe
                                                    LEFT JOIN filmes_devolucao fd ON fd.id_filme_emprestimo = fe.id
                                                   WHERE fe.id_dvd = d.id
                                                     AND fd.id IS NULL)';

    private PDO $db;

    // Recebe a conexão pronta pelo construtor
    public function __construct(PDO $conexao)
    {
        $this->db = $conexao;
    }

    // Número de cópias livres daquele filme:
    // soma de `quantidade` dos dvds do filme - cópias já emprestadas
    // em empréstimos sem devolução registrada.
    public function verificarDisponibilidade(int $idFilme): int
    {
        $sql = "SELECT
                    (SELECT COALESCE(SUM(d.quantidade), 0)
                       FROM dvds d
                      WHERE d.id_filme = :idA)
                  - (SELECT COUNT(*)
                       FROM filmes_emprestimo fe
                       JOIN dvds d ON d.id = fe.id_dvd
                      WHERE d.id_filme = :idB
                        AND fe.id_emprestimo
                            NOT IN (SELECT id_emprestimo FROM devolucoes)) AS disponivel";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':idA', $idFilme, PDO::PARAM_INT);
        $stmt->bindValue(':idB', $idFilme, PDO::PARAM_INT);
        $stmt->execute();

        return (int) ($stmt->fetchColumn() ?? 0);
    }

    // Todos os DVDs com estoque, já com a contagem de cópias livres para o select
    // de filmes do painel de empréstimo.
    public function listarComDisponibilidade(): array
    {
        $sql = "SELECT d.id, f.titulo, d.quantidade, " . self::COPIAS_DISPONIVEIS . " AS disponivel
                  FROM dvds d
                  JOIN filmes f ON f.id = d.id_filme
                 WHERE d.quantidade > 0
                 ORDER BY f.titulo";

        return $this->db->query($sql)->fetchAll();
    }

    // Um DVD pelo id, com a contagem de cópias livres (null se não existir).
    public function buscarComDisponibilidade(int $idDvd): ?array
    {
        $sql = "SELECT d.id, f.titulo, d.quantidade, " . self::COPIAS_DISPONIVEIS . " AS disponivel
                  FROM dvds d
                  JOIN filmes f ON f.id = d.id_filme
                 WHERE d.id = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $idDvd, PDO::PARAM_INT);
        $stmt->execute();

        $dvd = $stmt->fetch();

        return $dvd ?: null;
    }
}
