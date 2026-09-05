<?php
class Filmes
{
    private PDO $db;

    // Recebe a conexão pronta pelo construtor
    public function __construct(PDO $conexao)
    {
        $this->db = $conexao;
    }

    // Retorna os dados para quem chamou o método
    public function listarTodos(): array
    {
        $sql = "SELECT * FROM filmes ORDER BY id";
        $stmt = $this->db->query($sql);
        
        return $stmt->fetchAll(); // PDO::FETCH_ASSOC já foi definido no arquivo de conexão
    }

    public function listarPorPagina(int $limit, int $offset): array    {
        $sql = "SELECT titulo, valor FROM filmes LIMIT :limit OFFSET :offset";
        // BindValue com PARAM_INT é obrigatório para LIMIT/OFFSET no MySQL
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    public function contarTotal(): int
    {
        $sql = "SELECT COUNT(id) FROM filmes";
        $stmt = $this->db->query($sql);

        // fetchColumn() pega direto o valor da primeira coluna (o resultado do COUNT)
        return (int) $stmt->fetchColumn();
    }

    // MÉTODO OBRIGATÓRIO PARA INSERÇÃO DE DADOS
    public function criar(array $dados): bool
    {
        $sql = "INSERT INTO filmes (id_genero, titulo, valor) VALUES (:id_genero, :titulo, :valor)";
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([
            'id_genero' => $dados['id_genero'],
            'titulo'    => $dados['titulo'],
            'valor'     => $dados['valor']
        ]);
    }
}
