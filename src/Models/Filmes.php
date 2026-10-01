<?php
class Filmes
{
        private const FILME_DISPONIVEL = "EXISTS (
                SELECT 1
                    FROM dvds d
                 WHERE d.id_filme = f.id
                     AND d.quantidade > (
                             SELECT COUNT(*)
                                 FROM filmes_emprestimo fe
                                 LEFT JOIN filmes_devolucao fd ON fd.id_filme_emprestimo = fe.id
                                WHERE fe.id_dvd = d.id AND fd.id IS NULL
                     )
        )";

    private PDO $db;

    // Recebe a conexão pronta pelo construtor
    public function __construct(PDO $conexao)
    {
        $this->db = $conexao;
    }

    // Retorna os dados para quem chamou o método
    public function listarTodos(): array
    {
        $sql = "SELECT f.* FROM filmes f WHERE " . self::FILME_DISPONIVEL . " ORDER BY f.id";
        $stmt = $this->db->query($sql);

        return $stmt->fetchAll(); // PDO::FETCH_ASSOC já foi definido no arquivo de conexão
    }

    public function listarPorPagina(int $limit, int $offset): array
    {
        $sql = "SELECT f.id, f.titulo, f.valor, f.poster_url
                  FROM filmes f
                 WHERE " . self::FILME_DISPONIVEL . "
                 ORDER BY f.id
                 LIMIT :limit OFFSET :offset";
        // BindValue com PARAM_INT é obrigatório para LIMIT/OFFSET no MySQL
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function contarTotal(): int
    {
        $sql = "SELECT COUNT(f.id) FROM filmes f WHERE " . self::FILME_DISPONIVEL;
        $stmt = $this->db->query($sql);

        // fetchColumn() pega direto o valor da primeira coluna (o resultado do COUNT)
        return (int) $stmt->fetchColumn();
    }

    // MÉTODO OBRIGATÓRIO PARA INSERÇÃO DE DADOS
    public function criar(array $dados): bool
    {
        $sql = "INSERT INTO filmes (id_genero, titulo, valor, poster_url) VALUES (:id_genero, :titulo, :valor, :poster_url)";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            'id_genero'  => $dados['id_genero'],
            'titulo'     => $dados['titulo'],
            'valor'      => $dados['valor'],
            'poster_url' => $dados['poster_url'] ?? null
        ]);
    }

    // Método para pesquisar filmes por título na barra de pesquisa
    public function filtrarFilmes(string $termo): array
    {
        if (empty(trim($termo))) {
            return []; // Retorna uma lista vazia sem consultar o banco
        }
                $sql = "SELECT f.id, f.titulo, f.valor, f.poster_url
                                    FROM filmes f
                                 WHERE " . self::FILME_DISPONIVEL . "
                                     AND f.titulo LIKE :termo
                                 ORDER BY f.titulo
                                 LIMIT 10";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':termo', '%' . $termo . '%', PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    // Conta quantos filmes existem para um determinado gênero (usado na paginação)
    public function contarPorGenero(int $genero): int
    {
                $sql = "SELECT COUNT(f.id)
                                    FROM filmes f
                                 WHERE f.id_genero = :genero
                                     AND " . self::FILME_DISPONIVEL;
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':genero', $genero, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    // Busca os filmes de um gênero específico, já com paginação (igual listarPorPagina)
    public function filtrarPorGenero(int $genero, int $limit, int $offset): array
    {
                $sql = "SELECT f.id, f.titulo, f.valor, f.poster_url
                                    FROM filmes f
                                 WHERE f.id_genero = :genero
                                     AND " . self::FILME_DISPONIVEL . "
                                 ORDER BY f.titulo, f.id
                                 LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':genero', $genero, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function buscarPorId(int $id): ?array
    {
        $sql = "SELECT f.id, f.id_genero, f.titulo, f.valor, f.poster_url, g.genero
                FROM filmes f
                INNER JOIN generos g ON g.id = f.id_genero
                WHERE f.id = :id";
        $sql .= ' AND ' . self::FILME_DISPONIVEL;
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $filme = $stmt->fetch();
        return $filme ?: null; // Retorna null se não encontrar o filme
    }

    public function listarAtores(int $idFilme): array
    {
        $sql = "SELECT a.nome, fa.personagem
                FROM atores a
                JOIN atores_filme fa ON a.id = fa.id_ator
                WHERE fa.id_filme = :idFilme
                ORDER BY a.nome";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':idFilme', $idFilme, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
    public function filtrarPorAtor(string $nome): array
    {
        $sql = "SELECT DISTINCT f.id, f.titulo, f.valor, f.poster_url
                FROM filmes f
                INNER JOIN atores_filme fa ON fa.id_filme = f.id
                INNER JOIN atores a ON a.id = fa.id_ator
                WHERE a.nome LIKE :termo
                ORDER BY f.titulo";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':termo', '%' . $nome . '%', PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}

