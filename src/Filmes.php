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
}