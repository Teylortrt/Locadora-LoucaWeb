<?php
class Dvd
{
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
}