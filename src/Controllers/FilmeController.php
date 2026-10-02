<?php

class FilmesController
{
    private PDO $db;

    // Recebe a conexão pronta pelo construtor
    public function __construct(PDO $conexao)
    {
        $this->db = $conexao;
    }

    public function salvar(array $dados): int 
    {
        $sql = "INSERT INTO filmes (titulo, id_genero, valor, poster_url) 
                VALUES (:titulo, :id_genero, :valor, :poster_url)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':titulo'     => $dados['titulo'] ?? '',
            ':id_genero'  => $dados['id_genero'] ?? null,
            ':valor'      => $dados['valor'] ?? 0,
            ':poster_url' => $dados['poster_url'] ?? ''
        ]);

        return (int) $this->db->lastInsertId();
    }

    // Exclusão de filme e dvd do BD
    // Ordem: filmes_devolucao → filmes_emprestimo → dvds → atores_filme → filmes
    public function excluirFilme(int $idFilme): void
    {
        // 1. Busca o DVD vinculado ao filme
        $stmt = $this->db->prepare('SELECT id FROM dvds WHERE id_filme = :id');
        $stmt->execute(['id' => $idFilme]);
        $dvd = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->db->beginTransaction();

        try {
            if ($dvd) {
                $idDvd = (int) $dvd['id'];

                // 2. Verifica empréstimos ativos (sem devolução)
                $stmt = $this->db->prepare(
                    'SELECT COUNT(*) FROM filmes_emprestimo fe
                       LEFT JOIN filmes_devolucao fd ON fd.id_filme_emprestimo = fe.id
                      WHERE fe.id_dvd = :id_dvd
                        AND fd.id IS NULL'
                );
                $stmt->execute(['id_dvd' => $idDvd]);

                if ((int) $stmt->fetchColumn() > 0) {
                    throw new \RuntimeException(
                        'Não é possível excluir: existem empréstimos ativos (sem devolução) para este filme.'
                    );
                }

                // 3. Apaga devoluções vinculadas aos empréstimos deste DVD
                $this->db->prepare(
                    'DELETE fd FROM filmes_devolucao fd
                       JOIN filmes_emprestimo fe ON fe.id = fd.id_filme_emprestimo
                      WHERE fe.id_dvd = :id_dvd'
                )->execute(['id_dvd' => $idDvd]);

                // 4. Apaga registros de empréstimo deste DVD
                $this->db->prepare(
                    'DELETE FROM filmes_emprestimo WHERE id_dvd = :id_dvd'
                )->execute(['id_dvd' => $idDvd]);

                // 5. Apaga o DVD
                $this->db->prepare(
                    'DELETE FROM dvds WHERE id = :id'
                )->execute(['id' => $idDvd]);
            }

            // 6. Apaga elenco (atores_filme)
            $this->db->prepare(
                'DELETE FROM atores_filme WHERE id_filme = :id'
            )->execute(['id' => $idFilme]);

            // 7. Apaga o filme
            $this->db->prepare(
                'DELETE FROM filmes WHERE id = :id'
            )->execute(['id' => $idFilme]);

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}