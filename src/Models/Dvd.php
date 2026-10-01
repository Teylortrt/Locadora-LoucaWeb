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

    // Busca o registro de DVD pelo id_filme (para saber o id do dvd e a quantidade atual).
    public function buscarPorFilme(int $idFilme): ?array
    {
        $sql = "SELECT d.id, d.id_filme, d.quantidade, " . self::COPIAS_DISPONIVEIS . " AS disponivel
                  FROM dvds d
                 WHERE d.id_filme = :idFilme";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':idFilme', $idFilme, PDO::PARAM_INT);
        $stmt->execute();

        $dvd = $stmt->fetch();

        return $dvd ?: null;
    }

    // Atualiza a quantidade (estoque físico) de um DVD.
    // Lança exceção se a nova quantidade for menor que as cópias emprestadas.
    public function atualizarQuantidade(int $idDvd, int $novaQuantidade): void
    {
        if ($novaQuantidade < 0) {
            throw new \InvalidArgumentException('A quantidade não pode ser negativa.');
        }

        // Busca quantas cópias estão emprestadas (sem devolução)
        $dvd = $this->buscarComDisponibilidade($idDvd);

        if (!$dvd) {
            throw new \RuntimeException('DVD não encontrado.');
        }

        $emprestadas = (int) $dvd['quantidade'] - (int) $dvd['disponivel'];

        if ($novaQuantidade < $emprestadas) {
            throw new \RuntimeException(
                "Não é possível reduzir para {$novaQuantidade}. "
                . "Existem {$emprestadas} cópia(s) emprestada(s) no momento."
            );
        }

        $stmt = $this->db->prepare("UPDATE dvds SET quantidade = :qtd WHERE id = :id");
        $stmt->execute(['qtd' => $novaQuantidade, 'id' => $idDvd]);
    }

    // Lista todos os DVDs com dados completos para a tela de gestão de estoque.
    // Suporta paginação e busca por título.
    public function listarEstoque(int $limit, int $offset, string $busca = ''): array
    {
        $where = '';
        $params = [];

        if ($busca !== '') {
            $where = " AND f.titulo LIKE :busca";
            $params['busca'] = '%' . $busca . '%';
        }

        $sql = "SELECT d.id AS id_dvd, d.id_filme, f.titulo, g.genero,
                       d.quantidade, " . self::COPIAS_DISPONIVEIS . " AS disponivel
                  FROM dvds d
                  JOIN filmes f ON f.id = d.id_filme
                  JOIN generos g ON g.id = f.id_genero
                 WHERE 1=1{$where}
                 ORDER BY f.titulo
                 LIMIT :lim OFFSET :off";

        $stmt = $this->db->prepare($sql);

        foreach ($params as $k => $v) {
            $stmt->bindValue(':' . $k, $v, PDO::PARAM_STR);
        }

        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    // Conta o total de DVDs (para paginação).
    public function contarEstoque(string $busca = ''): int
    {
        $where = '';
        $params = [];

        if ($busca !== '') {
            $where = " AND f.titulo LIKE :busca";
            $params['busca'] = '%' . $busca . '%';
        }

        $sql = "SELECT COUNT(*) FROM dvds d
                  JOIN filmes f ON f.id = d.id_filme
                 WHERE 1=1{$where}";

        $stmt = $this->db->prepare($sql);

        foreach ($params as $k => $v) {
            $stmt->bindValue(':' . $k, $v, PDO::PARAM_STR);
        }

        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }
}
